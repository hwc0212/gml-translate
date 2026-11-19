"""Exercise actual frontend link rewriting with initial and changing DOM nodes."""
import json
import os
import sys
from pathlib import Path
from urllib.parse import unquote

from playwright.sync_api import sync_playwright

root = Path(__file__).resolve().parents[2]
cases = json.loads((root / 'tests/fixtures/navigation-links.json').read_text())
results = {'checks': 0, 'failures': [], 'errors': []}
script = (root / 'assets/js/language-switcher.js').read_text()


def check_page(browser, base):
    origin = 'https://example.test'
    home = origin + base
    def expand(value):
        return value.replace('{home}', home).replace('{origin}', origin).replace('{base}', base)

    values = []
    for case in cases:
        expected = case['url'] if case.get('keep') else case.get('subdirExpected', case['expected']) if base else case['expected']
        values.append(dict(name=case['name'], url=expand(case['url']), expected=expand(expected), download=case.get('download', False)))
    page = browser.new_page()
    page.on('pageerror', lambda error: results['errors'].append(str(error)))
    page.route('**/*', lambda route: route.fulfill(body='<html><body></body></html>', content_type='text/html'))
    page.goto(home + '/de/product/sample/')
    page.evaluate("""() => {
        const NativeObserver = window.MutationObserver;
        window.navigationObserverOverflow = false;
        window.MutationObserver = class extends NativeObserver {
            constructor(callback) {
                let count = 0;
                super((mutations, observer) => {
                    if (++count > 12) {
                        observer.disconnect();
                        window.navigationObserverOverflow = true;
                        return;
                    }
                    callback(mutations, observer);
                });
            }
        };
    }""")
    page.evaluate("""config => { window.gmlData = config; }""", {
        'homeUrl': home + '/', 'homePath': base, 'currentLang': 'de', 'sourceLang': 'en',
        'languages': ['en', 'de'], 'knownLanguages': ['en', 'de', 'fr'],
        'excludedPaths': [unquote(base) + '/assets', unquote(base) + '/media', unquote(base) + '/api'],
        'filePattern': r'\.(?:avif|bmp|gif|ico|jpe?g|png|svg|tiff?|webp|pdf|zip|7z|gz|rar|tar|css|m?js|woff2?|ttf|otf|eot|mp[34]|m4a|mov|webm|ogg|wav|csv|xlsx?|docx?|pptx?)$',
    })

    def insert(mode):
        page.evaluate("""({values, mode}) => {
            const container = document.createElement('section');
            for (const value of values) {
                for (const tag of ['a', 'form']) {
                    const el = document.createElement(tag);
                    el.dataset.testName = value.name;
                    el.dataset.expected = tag === 'a' && value.download ? value.url : value.expected;
                    if (tag === 'a' && value.download) el.setAttribute('download', '');
                    el.dataset.mode = mode;
                    el.setAttribute(tag === 'a' ? 'href' : 'action', mode === 'changed' ? '#initial' : value.url);
                    if (mode === 'direct') document.body.append(el);
                    else container.append(el);
                }
            }
            if (mode !== 'direct') document.body.append(container);
            if (mode === 'changed') {
                for (const el of container.children) {
                    const value = values.find(v => v.name === el.dataset.testName);
                    el.setAttribute(el.tagName === 'A' ? 'href' : 'action', value.url);
                }
            }
        }""", dict(values=values, mode=mode))

    insert('initial')
    page.add_script_tag(content=script)
    for mode in ('container', 'direct', 'changed'):
        insert(mode)
    page.wait_for_timeout(200)
    if page.evaluate('window.navigationObserverOverflow'):
        results['errors'].append('Repeated navigation mutations at ' + base)
    observed = page.evaluate("""() => [...document.querySelectorAll('[data-test-name]')].map(el => ({
        name: el.dataset.testName, mode: el.dataset.mode, tag: el.tagName,
        expected: el.dataset.expected, actual: el.getAttribute(el.tagName === 'A' ? 'href' : 'action')
    }))""")
    for row in observed:
        results['checks'] += 1
        if row['actual'] != row['expected']:
            results['failures'].append(dict(base=base, **row))
    # Changes made by another script after the initial observer delivery also use the policy.
    page.evaluate("""home => {
        const el = document.querySelector('a[data-test-name="local-page"]');
        el.dataset.expected = home + '/de/contact/';
        el.setAttribute('href', home + '/contact/');
    }""", home)
    page.wait_for_timeout(100)
    el = page.locator('a[data-test-name="local-page"]').first
    results['checks'] += 1
    if el.get_attribute('href') != home + '/de/contact/':
        results['failures'].append(dict(base=base, name='changed-attribute', actual=el.get_attribute('href')))
    page.close()


with sync_playwright() as pw:
    browser = pw.chromium.launch(executable_path=os.getenv('CHROMIUM_PATH'))
    for path in ('', '/staging', '/shop%20one', '/caf%C3%A9'):
        check_page(browser, path)
    browser.close()
print(json.dumps(results, ensure_ascii=False))
if '--audit' not in sys.argv:
    assert not results['failures'] and not results['errors']

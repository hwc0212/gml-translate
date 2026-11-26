"""Browser regression for upload anchors, including galleries inserted after load."""
import os
from pathlib import Path

from playwright.sync_api import sync_playwright


def check_links(browser, script, home_path):
    home = 'https://example.test' + home_path
    page = browser.new_page()
    page.route('**/*', lambda route: route.fulfill(body='<html><body></body></html>', content_type='text/html'))
    page.goto(home + '/de/product/sample/')
    page.evaluate("""home => {
        window.gmlData = {
            currentLang: 'de', sourceLang: 'en', languages: ['en', 'de'],
            homeUrl: home + '/', homePath: new URL(home).pathname
        };
    }""", home)
    upload = home_path + '/wp-content/uploads/original.jpg?size=full&x=%2B#photo'
    links = [
        (upload, upload),
        (home + '/wp-content/uploads/original.jpg', home + '/wp-content/uploads/original.jpg'),
        ('//example.test' + upload, '//example.test' + upload),
        (home_path + '/wp-content-guide/', home_path + '/de/wp-content-guide/'),
        (home_path + '/product/sample/', home_path + '/de/product/sample/'),
    ]

    def insert_links():
        page.evaluate("""links => {
            const gallery = document.createElement('div');
            for (const [url] of links) {
                const a = document.createElement('a');
                a.setAttribute('href', url);
                gallery.append(a);
            }
            document.body.append(gallery);
        }""", links)

    insert_links()
    page.add_script_tag(content=script)
    insert_links()
    expected = [result for _, result in links] * 2
    page.wait_for_function(
        "urls => JSON.stringify([...document.links].map(a => a.getAttribute('href'))) === JSON.stringify(urls)",
        arg=expected, timeout=3000,
    )
    page.close()


if __name__ == '__main__':
    script = (Path(__file__).resolve().parents[2] / 'assets/js/language-switcher.js').read_text()
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(executable_path=os.getenv('CHROMIUM_PATH'))
        for path in ('', '/staging'):
            check_links(browser, script, path)
        browser.close()
    print('PASS initial and dynamic media and navigation links at root and /staging')

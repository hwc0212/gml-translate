"""Real isolated HTTP and admin rendering. Never submits forms."""
import json,sys,pathlib
from playwright.sync_api import sync_playwright
stage=sys.argv[1];base='http://gmlstate1123-php:8080'
results=[]
out=pathlib.Path('/evidence/browser');out.mkdir(parents=True,exist_ok=True)
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/browser/chrome/chrome',headless=True,args=['--no-sandbox','--disable-dev-shm-usage'])
 page=browser.new_page(viewport={'width':1440,'height':1000})
 errors=[];page.on('pageerror',lambda error:errors.append(str(error)))
 for lang in ['en','de','es','fr','ru']:
  path=('/' if lang=='en' else '/'+lang+'/')+'synthetic-inquiry/'
  response=page.goto(base+path,wait_until='networkidle')
  assert response.status==200
  noindex='noindex' in response.headers.get('x-robots-tag','')
  html_noindex='noindex' in (page.locator('meta[name="robots"]').get_attribute('content') or '')
  count=page.locator('link[rel="alternate"][hreflang]').count()
  expected=(6 if stage=='ready' else 2) if lang=='en' else (6 if stage=='ready' else 0)
  assert count==expected,(lang,stage,count)
  assert noindex==(lang!='en' and stage!='ready'),(lang,response.headers)
  assert html_noindex==noindex
  canonical=page.locator('link[rel="canonical"]').get_attribute('href')
  assert canonical=='http://gml-state.test'+path,canonical
  results.append(dict(language=lang,http=200,noindex_header=noindex,noindex_html=html_noindex,hreflang=count,canonical=canonical))
 for locale in ['en','zh_CN']:
  page.goto(base+'/__state_admin?locale='+locale,wait_until='networkidle')
  text=page.locator('body').inner_text()
  assert ('Translation item coverage' if locale=='en' else '译文条目完整度') in text
  rows=page.locator('table.widefat tbody tr')
  key=page.locator('body').get_attribute('data-gml-main-resource')
  row=next(rows.nth(n) for n in range(rows.count()) if rows.nth(n).locator('td').first.inner_text()==key+'\nFR')
  if stage=='stale':
   assert '163 / 163' in row.inner_text()
   assert ('Pending current-version evaluation' if locale=='en' else '待评估当前版本') in row.inner_text()
   assert ('Not evaluated for this version' if locale=='en' else '当前版本尚未评估') in row.inner_text()
   assert 'being refreshed' not in row.inner_text()
  elif stage=='missing':
   assert '162 / 163' in row.inner_text()
   assert ('Incomplete measurement: 1' if locale=='en' else '测量不完整：1') in row.inner_text()
  elif stage=='failure':
   assert ('Current-version readiness needs evaluation' if locale=='en' else '当前版本的就绪状态待评估') in row.inner_text()
  row.locator('summary').click()
  if stage=='stale':assert ('previous snapshot' if locale=='en' else '先前版本') in row.inner_text()
  row.screenshot(path=str(out/(stage+'-'+locale+'-desktop.png')))
  page.set_viewport_size({'width':390,'height':844})
  assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth+1'),'mobile overflow'
  row.screenshot(path=str(out/(stage+'-'+locale+'-mobile.png')))
  page.set_viewport_size({'width':1440,'height':1000})
 page.goto(base+'/__state_admin?anonymous=1',wait_until='networkidle')
 assert page.locator('.gml-page-workflow').count()==0,'anonymous admin data leak'
 assert not errors,errors
 browser.close()
print(json.dumps(dict(stage=stage,frontend=results,page_errors=errors,admin_locales=['en','zh_CN'],mobile_overflow=False)))

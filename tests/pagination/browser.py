import json,sys,pathlib
from playwright.sync_api import sync_playwright
label=sys.argv[1];home='http://gml-pagination.test:8080'
languages=['en','es','de','ru'];base='/product-category/equipment/'
data={'label':label,'pages':[],'transitions':[],'errors':[]}
def path(lang,page=2,base=base):
 return ('' if lang=='en' else '/'+lang)+base+('page/'+str(page)+'/' if page>1 else '')
def inspect(page,response):
 return dict(status=response.status,url=page.url,x_robots_tag=response.headers.get('x-robots-tag',''),
  buttons=page.locator('a.gml-lang-button').evaluate_all("(a)=>a.map(x=>({lang:x.getAttribute('hreflang'),url:x.href,active:x.getAttribute('aria-current')}))"),
  alternates=page.locator('head link[rel="alternate"][hreflang]').evaluate_all("(a)=>a.map(x=>({lang:x.hreflang,url:x.href}))"),
  robots=page.locator('head meta[name="robots"]').evaluate_all("(a)=>a.map(x=>x.content)"),
  canonical=page.locator('head link[rel="canonical"]').evaluate_all("(a)=>a.map(x=>x.href)"),
  items=page.locator('main article[data-item-id]').evaluate_all("(a)=>a.map(x=>Number(x.dataset.itemId))"),
  paged=page.locator('main').get_attribute('data-paged'),
  resource=page.locator('main').get_attribute('data-resource'))
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/browser/chrome/chrome',headless=True,args=['--no-sandbox','--disable-dev-shm-usage'])
 context=browser.new_context(viewport={'width':1280,'height':900})
 page=context.new_page();page.on('pageerror',lambda e:data['errors'].append(str(e)))
 for source in languages:
  response=page.goto(home+path(source),wait_until='networkidle');row=inspect(page,response);row['source']=source;data['pages'].append(row)
  for button in row['buttons']:
   response=page.goto(button['url'],wait_until='domcontentloaded')
   data['transitions'].append(dict(source=source,target=button['lang'],expected=home+path(button['lang']),actual=page.url,status=response.status,items=page.locator('main article[data-item-id]').evaluate_all("(a)=>a.map(x=>Number(x.dataset.itemId))"),paged=page.locator('main').get_attribute('data-paged')))
 if label!='baseline':
  assert len(data['transitions'])==16
  expected_items=data['pages'][0]['items']
  for row in data['pages']:
   assert row['status']==200 and row['paged']=='2',row
   assert 'index' not in [v.strip().lower() for v in row['x_robots_tag'].split(',')],row
   assert len(row['buttons'])==4 and len(row['alternates'])==0,row
   assert any('noindex' in v for v in row['robots']) and not row['canonical'],row
   for b in row['buttons']:
    assert b['url']==home+path(b['lang']),b
    assert (b['active']=='page')==(b['lang']==row['source']),b
  for row in data['transitions']:assert row['actual']==row['expected'] and row['items']==expected_items and row['paged']=='2',row
 assert not data['errors'],data['errors']
 browser.close()
print(json.dumps(data))

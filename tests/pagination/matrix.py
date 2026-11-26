import json,sys
from playwright.sync_api import sync_playwright
cfg=json.loads(sys.stdin.read());home=cfg['home'];source=cfg.get('source','en');languages=[source]+[l for l in ['en','es','de','ru'] if l!=source]
results={'mode':cfg['mode'],'cases':[],'errors':[],'checks':0}
def require(ok,label):
 results['checks']+=1
 assert ok,label
def url(lang,base,n=1):
 return home+('' if lang==source else '/'+lang)+base+(cfg.get('pagination_base','page')+'/'+str(n)+'/' if n>1 else '')
def read(page,response):
 return {'status':response.status,'url':page.url,'robots':page.locator('head meta[name="robots"]').evaluate_all("(x)=>x.map(a=>a.content)"),
 'canonicals':page.locator('head link[rel="canonical"]').evaluate_all("(x)=>x.map(a=>a.href)"),
 'alternates':page.locator('head link[rel="alternate"][hreflang]').evaluate_all("(x)=>x.map(a=>({lang:a.hreflang,url:a.href}))"),
 'buttons':page.locator('a.gml-lang-button').evaluate_all("(x)=>x.map(a=>({lang:a.hreflang,url:a.href,active:a.getAttribute('aria-current')}))"),
 'items':page.locator('main article[data-item-id]').evaluate_all("(x)=>x.map(a=>Number(a.dataset.itemId))"),
 'paged':page.locator('main').get_attribute('data-paged'),'resource':page.locator('main').get_attribute('data-resource'),'admin_bar':page.locator('#wpadminbar').count()}
def verify(row,base,n,noindex):
 require(row['status']==200,'valid native archive response '+json.dumps(row))
 require(int(row['paged'] or 0)==(n if n>1 else 0),'native query page preserved')
 require(len(row['buttons'])==4,'navigation remains available')
 for b in row['buttons']:require(b['url']==url(b['lang'],base,n),'same page navigation')
 if noindex:
  require(any('noindex' in v for v in row['robots']),'SEO owner keeps noindex')
  require(not row['canonicals'],'SEO owner keeps canonical absent')
  require(not row['alternates'],'noindex pages advertise no alternates')
 else:
  require(not any('noindex' in v for v in row['robots']),'fixture index policy respected')
  require(row['canonicals']==[row['url']],'one self canonical')
  require(len(row['alternates'])==5 and len({a['lang'] for a in row['alternates']})==5,'exact equivalent language set')
  for a in row['alternates']:require(a['url']==url(source if a['lang']=='x-default' else a['lang'],base,n),'same-page hreflang correspondence')
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/browser/chrome/chrome',headless=True,args=['--no-sandbox','--disable-dev-shm-usage'])
 anon=browser.new_context();page=anon.new_page();page.on('pageerror',lambda e:results['errors'].append(str(e)))
 if cfg['mode']=='noindex':
  for base in ['/product-category/equipment/','/product-category/components/']:
   for lang in languages:
    r=page.goto(url(lang,base),wait_until='domcontentloaded');row=read(page,r);verify(row,base,1,False);results['cases'].append(row)
  product='/product/synthetic-equipment-1/'
  for lang in languages:
   r=page.goto(url(lang,product),wait_until='domcontentloaded');row=read(page,r)
   require(row['status']==200 and row['items']==[cfg['product_ids'][0]],'native product regression')
   require(row['canonicals']==[url(lang,product)] and len(row['alternates'])==5,'product SEO regression');results['cases'].append(row)
  base='/product-category/equipment/'
  for n in [2,3,4]:
   for lang in languages:
    r=page.goto(url(lang,base,n),wait_until='domcontentloaded');row=read(page,r);verify(row,base,n,True)
    require(row['items']==cfg['product_ids'][(n-1)*2:n*2],'correct real page items');results['cases'].append(row)
  for lang in languages:
   r=page.goto(url(lang,base,999),wait_until='domcontentloaded');row=read(page,r)
   require(row['status']==404 and not row['items'] and not row['alternates'] and not row['canonicals'],'out-of-range is not a first-page language variant');results['cases'].append(row)
  signed=browser.new_context();signed.add_cookies([cfg['cookie']]);login=signed.new_page();login.on('pageerror',lambda e:results['errors'].append(str(e)))
  for lang in languages:
   r=login.goto(url(lang,base,2),wait_until='domcontentloaded');row=read(login,r);verify(row,base,2,True)
   require(row['admin_bar']==1 and row['items']==cfg['product_ids'][2:4],'real authenticated pagination');results['cases'].append(row)
  for lang in languages:
   for repeat in range(2):
    r=page.goto(url(lang,base,2),wait_until='domcontentloaded');row=read(page,r);verify(row,base,2,True)
    require(row['admin_bar']==0 and row['items']==cfg['product_ids'][2:4],'anonymous and repeated cache paths preserve content');results['cases'].append(row)
 else:
  for base in ['/product-category/components/','/sector/industrial/','/industry/']:
   for n in [2,3,4]:
    for lang in languages:
     r=page.goto(url(lang,base,n),wait_until='domcontentloaded');row=read(page,r);verify(row,base,n,False)
     require(len(row['items'])==(1 if n==4 else 2) and all(row['items']),'real paginated items exist');results['cases'].append(row)
  for lang in languages:
   r=page.goto(url(lang,'/industry/',999),wait_until='domcontentloaded');row=read(page,r)
   require(row['status']==404 and not row['alternates'],'invalid index fixture cannot inherit base equivalence');results['cases'].append(row)
 require(not results['errors'],'browser has no page errors')
 browser.close()
print(json.dumps(results))

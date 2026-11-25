import json,sys,base64,html
from playwright.sync_api import sync_playwright
cfg=json.load(sys.stdin);home='http://gml-pagination.test:8080';languages=['en','es','de','ru']
paths=['/product/synthetic-equipment-1/','/product-category/equipment/','/product-category/equipment/page/2/']
titles={'en':'Synthetic equipment & test','de':'Manuell geprüfter Gerätetitel','es':'Título del equipo revisado manualmente','ru':'Название оборудования проверено вручную'}
labels={'en':{'Home','Equipment & Parts','Components','Synthetic equipment & test','Page 2'},'de':{'Startseite','Ausrüstung & Teile','Bauteile',titles['de'],'Seite 2'},'es':{'Inicio','Equipos y repuestos','Componentes',titles['es'],'Página 2'},'ru':{'Главная','Оборудование и запчасти','Компоненты',titles['ru'],'Страница 2'}}
results={'pages':[],'transitions':[],'authenticated':[],'checks':0,'errors':[]}
def check(value,label):
 results['checks']+=1
 assert value,label
def breadcrumbs(node):
 if isinstance(node,dict):
  kinds=node.get('@type',[]);kinds=[kinds] if isinstance(kinds,str) else kinds
  if 'BreadcrumbList' in kinds:yield node
  for value in node.values():yield from breadcrumbs(value)
 elif isinstance(node,list):
  for value in node:yield from breadcrumbs(value)
def inspect(page,response,lang,path):
 row={'language':lang,'path':path,'url':page.url,'status':response.status,'h1':page.locator('h1').inner_text(),'conditions':page.locator('#condition').all_text_contents(),'robots':page.locator('head meta[name="robots"]').evaluate_all('(a)=>a.map(x=>x.content)'),'canonical':page.locator('head link[rel="canonical"]').evaluate_all('(a)=>a.map(x=>x.href)'),'hreflang':page.locator('head link[hreflang]').count(),'x_robots_tag':response.headers.get('x-robots-tag',''),'items':page.locator('main article').evaluate_all('(a)=>a.map(x=>x.dataset.itemId)'),'breadcrumbs':[]}
 scripts=page.locator('script[type="application/ld+json"]').all_text_contents()
 check(len(scripts)==2,'exactly the two original fixture/schema-owner scripts; no injected duplicate')
 for raw in scripts:
  for node in breadcrumbs(json.loads(raw)):
   parts=[]
   for item in node['itemListElement']:
    nested=item.get('item')
    name=nested.get('name',item.get('name')) if isinstance(nested,dict) else item.get('name')
    url=(nested.get('url') or nested.get('@id') or nested.get('id')) if isinstance(nested,dict) else nested
    parts.append({'name':name,'url':url,'position':item['position']})
    compared=html.unescape(html.unescape(name)) if lang=='en' else name
    check(compared in labels[lang],str(('breadcrumb label',lang,name)))
    check((lang=='en' and url.split('#')[0]==home) or url.split('#')[0].startswith(home+('/' if lang=='en' else '/'+lang+'/')),str(('breadcrumb URL',lang,url)))
   check(parts[-1]['url'].split('#')[0]==home+('' if lang=='en' else '/'+lang)+path,'last breadcrumb current page including pagination')
   check([v['position'] for v in parts]==list(range(1,len(parts)+1)),'positions unchanged')
   row['breadcrumbs'].append(parts)
 check(len(row['breadcrumbs'])==2,'native Woo breadcrumb and SEOPress-shape contract both present')
 sentinel='{"@type":"Product","name":"Synthetic equipment & test","url":"https://example.test/untouched/","price":123.4500,"big":123456789012345678901234567890,"empty":{},"arr":[]}'
 check(sentinel in page.locator('#contract-jsonld').text_content(),'Product sibling is byte-identical including decimal and huge integer')
 check(row['status']==200,'native response success')
 if path.startswith('/product/'):
  check(row['h1']==titles[lang],'manual title matches visible and schema pipeline')
  for value in row['conditions']:
   for token in ['5–8 ppm','15°C','20°C','<500 µS/cm']:check(token in value,('technical token',lang,token))
   if lang!='en':check('confirm sampling method' not in value and 'inlet water' not in value,'entire source sentence replaced')
 if '/page/2/' in path:
  check(any('noindex' in v for v in row['robots']) and row['hreflang']==0 and not row['canonical'],'existing noindex pagination SEO unchanged')
 else:
  check(not any('noindex' in v for v in row['robots']) and row['hreflang']==5 and len(row['canonical'])==1,'base and product indexing unchanged')
 return row
with sync_playwright() as p:
 browser=p.chromium.launch(executable_path='/browser/chrome/chrome',headless=True,args=['--no-sandbox','--disable-dev-shm-usage'])
 context=browser.new_context(viewport={'width':1300,'height':900})
 page=context.new_page();page.on('pageerror',lambda e:results['errors'].append(str(e)))
 for path in paths:
  for lang in languages:
   response=page.goto(home+('' if lang=='en' else '/'+lang)+path,wait_until='networkidle')
   results['pages'].append(inspect(page,response,lang,path))
   if lang=='de' and path.startswith('/product/'):results['screenshot_base64']=base64.b64encode(page.screenshot(full_page=True)).decode()
 for source in languages:
  path=paths[2];page.goto(home+('' if source=='en' else '/'+source)+path,wait_until='domcontentloaded')
  links=page.locator('a.gml-lang-button').evaluate_all('(a)=>a.map(x=>({lang:x.hreflang,url:x.href}))')
  for link in links:
   response=page.goto(link['url'],wait_until='domcontentloaded')
   expected=home+('' if link['lang']=='en' else '/'+link['lang'])+path
   check(page.url==expected,'language switch still retains page 2')
   results['transitions'].append({'source':source,'target':link['lang'],'actual':page.url,'status':response.status})
 auth=browser.new_context();auth.add_cookies([cfg['cookie']]);ap=auth.new_page()
 for lang in ['de','es','ru']:
  response=ap.goto(home+'/'+lang+paths[0],wait_until='networkidle')
  check(ap.locator('#wpadminbar').count()==1,'real logged-in WordPress session')
  results['authenticated'].append(inspect(ap,response,lang,paths[0]))
 check(not results['errors'],'no browser JavaScript errors')
 browser.close()
print(json.dumps(results,ensure_ascii=False))

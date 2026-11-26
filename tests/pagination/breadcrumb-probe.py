import json,sys,base64
from playwright.sync_api import sync_playwright
mode=sys.argv[1];home='http://gml-pagination.test:8080';rows=[]
with sync_playwright() as p:
 b=p.chromium.launch(executable_path='/browser/chrome/chrome',headless=True,args=['--no-sandbox','--disable-dev-shm-usage'])
 page=b.new_page(viewport={'width':1300,'height':600});errors=[];page.on('pageerror',lambda e:errors.append(str(e)))
 for repeat in range(2):
  response=page.goto(home+'/de/product/synthetic-equipment-1/',wait_until='networkidle')
  title=page.locator('h1').inner_text();value=page.locator('#condition').inner_text();robots=page.locator('head meta[name=robots]').evaluate_all('(a)=>a.map(x=>x.content)');hreflang=page.locator('head link[hreflang]').count()
  raw=page.locator('#contract-jsonld').text_content();names=[x['item']['name'] for x in json.loads(raw)['@graph'][1]['itemListElement']]
  assert response.status==200
  expected='Zweite manuelle Fassung des Gerätetitels' if mode=='manual' else 'Manuell geprüfter Gerätetitel'
  assert title==expected and names[-1]==expected,(mode,title,names)
  for token in ['5–8 ppm','15°C','20°C','<500 µS/cm']:assert token in value,token
  if mode=='missing':assert 'inlet water' in value and any('noindex' in r for r in robots) and hreflang==0
  else:assert 'inlet water' not in value and not any('noindex' in r for r in robots) and hreflang==5
  rows.append({'repeat':repeat,'status':response.status,'h1':title,'condition':value,'schema_names':names,'robots':robots,'hreflang':hreflang})
 if mode=='manual':shot=base64.b64encode(page.locator('main').screenshot()).decode()
 assert not errors
 b.close()
result={'mode':mode,'rows':rows,'errors':errors}
if mode=='manual':result['screenshot_base64']=shot
print(json.dumps(result,ensure_ascii=False))

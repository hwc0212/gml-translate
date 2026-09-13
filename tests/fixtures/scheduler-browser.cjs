// Run against generated fixtures; no production navigation or saved profile.
const fs = require('fs');
const path = require('path');
const {pathToFileURL} = require('url');
const {chromium} = require(process.env.GML_PLAYWRIGHT_MODULE || 'playwright');
const root = process.argv[2];
const assert = (ok, message) => { if (!ok) throw new Error(message); };
(async () => {
  const browser = await chromium.launch({channel:process.env.GML_BROWSER_CHANNEL || 'msedge',headless:true});
  const results = [];
  try {
    for (const file of fs.readdirSync(root).filter(f => f.endsWith('.html'))) {
      for (const width of [1440,768,390]) {
        const page = await browser.newPage({viewport:{width,height:900}});
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        await page.goto(pathToFileURL(path.join(root,file)).href);
        const button = page.locator('.gml-dropdown-btn');
        const menu = page.locator('.gml-dropdown-menu');
        assert(await button.getAttribute('aria-expanded') === 'false','collapsed state');
        await button.focus();
        await page.keyboard.press('Enter');
        assert(await button.getAttribute('aria-expanded') === 'true','keyboard expanded state');
        assert(await menu.locator('a').count() === 5,'all four other local codes plus external link');
        const text = await menu.innerText();
        assert(!text.includes('%') && !/incomplete|not ready/i.test(text),'no progress in menu');
        const box = await menu.boundingBox();
        assert(box && box.height > 100 && box.x >= -1 && box.x + box.width <= width+1,'full panel visible in viewport');
        const hrefs = await menu.locator('a').evaluateAll(a => a.map(x => x.href));
        assert(hrefs.some(x => x === 'https://external.example/'),'external mapping preserved');
        assert(hrefs.filter(x => x.includes('/staging/')).length === (file.includes('subdirectory')?4:0),'base path present exactly once');
        assert(!hrefs.some(x => /staging.*staging/.test(x)),'no duplicated base path');
        await page.keyboard.press('Escape');
        assert(await button.getAttribute('aria-expanded') === 'false','Escape closes');
        await button.click();
        await page.locator('#outside').click();
        assert(await button.getAttribute('aria-expanded') === 'false','outside click closes');
        const geometry = await page.evaluate(() => {
          const q=document.querySelector('.quote').getBoundingClientRect();
          const b=document.querySelector('.gml-dropdown-btn').getBoundingClientRect();
          return {overflow:document.documentElement.scrollWidth>innerWidth,overlap:q.right>b.left && b.right>q.left};
        });
        assert(!geometry.overflow && !geometry.overlap,'no horizontal overflow or quote overlap');
        assert(errors.length===0,'no JavaScript errors');
        await button.click();
        await page.screenshot({path:path.join(root,file.replace('.html','')+'-'+width+'.png')});
        results.push({file,width,links:hrefs,errors,result:'PASS'});
        await page.close();
      }
    }
    fs.writeFileSync(path.join(root,'browser-results.json'),JSON.stringify(results,null,2));
    console.log('PASS browser cases='+results.length);
  } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});

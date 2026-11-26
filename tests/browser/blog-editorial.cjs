const { chromium }=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs');
const path=require('node:path');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'../..');
const html=execFileSync('php',[path.join(root,'tests/fixtures/editorial-view.php')],{encoding:'utf8',env:{...process.env,BAPX_TEST_MODE:'1'}});
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  for(const width of [1440,375,390]){
   const page=await browser.newPage({viewport:{width,height:900}});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   let calls=0,delay=false;
   await page.route('http://nebo-fixture.test/**',async route=>{
    const url=new URL(route.request().url());
    if(url.pathname==='/admin/blog')return route.fulfill({contentType:'text/html',body:html});
    if(url.pathname==='/assets/css/band.css')return route.fulfill({contentType:'text/css',body:fs.readFileSync(root+url.pathname,'utf8')});
    if(url.pathname==='/assets/blog-editorial.js')return route.fulfill({contentType:'application/javascript',body:fs.readFileSync(root+url.pathname,'utf8')});
    if(url.pathname.startsWith('/admin/blog/')){
     calls++;
     if(delay)await new Promise(r=>setTimeout(r,600));
     return route.fulfill({contentType:'application/json',body:JSON.stringify({content:'## Reviewed suggestion\n\nA useful fictional article.',title:'Reviewed title',source:'provider',mode:'review_required'})});
    }
    return route.fulfill({status:404,body:''});
   });
   await page.goto('http://nebo-fixture.test/admin/blog');
   await page.evaluate(()=>{window.beLoadMarkdown('');document.getElementById('blog-form').hidden=false});
   await page.locator('#edit-title').fill('Fictional article');
   await page.locator('#generate-draft').click();
   await page.locator('#blog-ai-review').waitFor({state:'visible'});
   assert.equal(await page.locator('#edit-content').inputValue(),'');
   assert.match(await page.locator('#blog-ai-suggestion').inputValue(),/Reviewed suggestion/);
   await page.locator('#blog-ai-apply').click();
   assert.match(await page.locator('#edit-content').inputValue(),/Reviewed suggestion/);
   await page.locator('#generate-draft').click();
   assert.equal(calls,1);
   assert.match(await page.locator('#blog-ai-status').innerText(),/preserve the existing/);
   delay=true;
   await page.locator('#enhance-content').click();
   await page.locator('#be-document').fill('Owner edited while AI was working');
   await page.waitForFunction(()=>document.getElementById('blog-ai-status').textContent.includes('editor changed'));
   assert.equal(await page.locator('#be-document').innerText(),'Owner edited while AI was working');
   delay=false;
   await page.locator('#enhance-title').click();
   await page.locator('#blog-ai-review').waitFor({state:'visible'});
   await page.locator('#blog-ai-discard').click();
   assert.equal(await page.locator('#edit-title').inputValue(),'Fictional article');
   await page.evaluate(()=>window.beLoadMarkdown('![bad" onerror="window.bad=1](/assets/fake.png)\n\n[bad](javascript:alert(1))'));
   assert.equal(await page.locator('#be-document [onerror], #be-document a[href^="javascript:"]').count(),0);
   assert.equal(await page.evaluate(()=>!!window.bad),false);
   assert.equal(errors.length,0,errors.join('; '));
   const dimensions=await page.evaluate(()=>({viewport:innerWidth,scroll:document.documentElement.scrollWidth}));
   console.log(JSON.stringify({width,calls,review:'pass',stale:'pass',safeMarkup:'pass',errors,dimensions}));
   await page.close();
  }
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

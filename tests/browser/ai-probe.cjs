const {chromium}=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'../..');
const html=execFileSync('php',[path.join(root,'tests/fixtures/ai-probe-view.php')],{encoding:'utf8',env:{...process.env,BAPX_TEST_MODE:'1'}});
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try {
  for(const width of [1440,375,390]){
   const page=await browser.newPage({viewport:{width,height:900}});
   let payload;const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.route('http://nebo-fixture.test/**',async route=>{
    const url=new URL(route.request().url());
    if(url.pathname==='/admin/integrations')return route.fulfill({contentType:'text/html',body:html});
    if(url.pathname==='/assets/css/band.css')return route.fulfill({contentType:'text/css',body:fs.readFileSync(root+url.pathname,'utf8')});
    if(url.pathname==='/admin/integrations/test-ai'){
     payload=new URLSearchParams(route.request().postData());
     return route.fulfill({status:302,headers:{Location:'/admin/integrations'},body:''});
    }
    return route.fulfill({status:404,body:''});
   });
   await page.goto('http://nebo-fixture.test/admin/integrations');
   const card=page.locator('.ai-probe-card');
   assert.match(await card.innerText(),/Diagnostic not verified/);
   assert.match(await card.innerText(),/503/);
   await card.locator('[name=probe_model]').selectOption('gemma-4-26b-a4b-it');
   await card.locator('[name=probe_mode]').selectOption('tools');
   await card.getByRole('button',{name:'Run diagnostic'}).click();
   assert.equal(payload.get('probe_model'),'gemma-4-26b-a4b-it');
   assert.equal(payload.get('probe_mode'),'tools');
   assert.equal(payload.get('_csrf'),'fixture-token');
   const geometry=await card.evaluate(el=>({width:el.clientWidth,scroll:el.scrollWidth}));
   assert.ok(geometry.scroll<=geometry.width+2,JSON.stringify(geometry));
   assert.equal(errors.length,0,errors.join('; '));
   console.log(JSON.stringify({width,form:'pass',failureEvidence:'pass',geometry,errors}));
   await page.close();
  }
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

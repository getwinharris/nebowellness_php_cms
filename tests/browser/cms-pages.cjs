const {chromium}=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'../..');
(async()=>{
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try{
  for(const p of ['/admin/pages','/pages/fixture']){
   const html=execFileSync('php',[path.join(root,'tests/fixtures/cms-page-view.php'),p],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1',APP_URL:'http://cms-fixture.test'},encoding:'utf8'});
   for(const width of [1440,375,390]){
    const page=await browser.newPage({viewport:{width,height:900},serviceWorkers:'block',reducedMotion:'reduce'});
    const errors=[];page.on('pageerror',e=>errors.push(e.message));
    await page.route('**/*',async route=>{
      const u=new URL(route.request().url());
      if(u.hostname!=='cms-fixture.test')return route.abort();
      if(u.pathname===p)return route.fulfill({contentType:'text/html',body:html});
      const f=path.join(root,u.pathname);
      if(f.startsWith(root+'/assets/')&&fs.existsSync(f)&&fs.statSync(f).isFile())return route.fulfill({path:f});
      return route.fulfill({status:404,body:''});
    });
    await page.goto('http://cms-fixture.test'+p);
    const doc=page.locator('.cms-document');
    assert.equal(await doc.locator('.cms-section').count(),2);
    if(p.startsWith('/admin')){
      assert.match(await doc.textContent(),/Private draft body/);
      assert.ok(await page.getByRole('button',{name:'Publish saved draft'}).isVisible());
      const serialized=await page.locator('button[value="add:hero"]').evaluate(btn=>Array.from(new FormData(btn.form,btn).entries()));
      assert.ok(serialized.some(([k,v])=>k==='page_action'&&v==='add:hero'));
      assert.ok(serialized.some(([k,v])=>k==='revision'&&v.length===64));
    }else{
      assert.match(await doc.textContent(),/Initial public body/);assert.ok(!(await doc.textContent()).includes('Private draft body'));
      await doc.locator('summary').click();assert.ok(await doc.locator('details').evaluate(e=>e.open));
      assert.ok(await doc.getByText('Fixture answer',{exact:true}).isVisible());
    }
    const dim=await page.evaluate(()=>({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
    assert.ok(dim.scroll<=dim.client+2,JSON.stringify({width,p,...dim}));assert.deepEqual(errors,[]);
    console.log(JSON.stringify({page:p,width,overflow:false,errors}));await page.close();
   }
  }
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

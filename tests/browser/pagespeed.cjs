const {chromium}=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'../..');
(async()=>{
 const html=execFileSync('php',['tests/fixtures/pagespeed-view.php'],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://psi-fixture.test'},encoding:'utf8'});
 const browser=await chromium.launch({channel:'chrome',headless:true});
 try{
  for(const width of [1440,375,390]){
   const page=await browser.newPage({viewport:{width,height:900},serviceWorkers:'block',reducedMotion:'reduce'});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>{
     const u=new URL(route.request().url());
     if(u.hostname!=='psi-fixture.test')return route.abort();
     if(u.pathname==='/admin/pagespeed')return route.fulfill({contentType:'text/html',body:html});
     const file=path.join(root,u.pathname);
     if(file.startsWith(root+'/assets/')&&fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({path:file});
     return route.fulfill({status:404,body:''});
   });
   await page.goto('https://psi-fixture.test/admin/pagespeed');
   assert.ok(await page.getByRole('button',{name:'Run PageSpeed report'}).isVisible());
   assert.ok(await page.getByText('83 / 100',{exact:true}).isVisible());
   assert.ok(await page.getByText('No data available for this scope.',{exact:true}).isVisible());
   assert.ok(await page.getByText('Google quota exceeded; try later',{exact:true}).isVisible());
   const dim=await page.evaluate(()=>({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
   assert.ok(dim.scroll<=dim.client+2,JSON.stringify({width,...dim}));assert.deepEqual(errors,[]);
   console.log(JSON.stringify({width,page:'PageSpeed',overflow:false,errors}));await page.close();
  }
 }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

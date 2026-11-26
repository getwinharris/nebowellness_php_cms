const {chromium}=require('playwright');
const {execFileSync}=require('node:child_process');
const assert=require('node:assert/strict'),fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const root=path.resolve(__dirname,'../..');
(async()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'bapxcms-blog-browser-'));
 const posts=path.join(dir,'posts');fs.mkdirSync(posts);
 const env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://blog-fixture.test'};
 let browser;
 try{
  const seed=JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php";$b=new App\\Services\\BlogService($argv[1]);$a=$b->save(["slug"=>"fixture","title"=>"Original title","content"=>"Original Markdown body","published"=>false],"new");$c=$b->save(["slug"=>"fixture","title"=>"Current title","content"=>"Current Markdown body"],$a["_revision"]);echo json_encode([$a["_revision"],$c["_revision"]]);',posts],{cwd:root,env,encoding:'utf8'}));
  const p='/admin/blog?history=fixture&version='+seed[0];
  const html=JSON.parse(execFileSync('php',['tests/fixtures/blog-revision-route.php',dir,posts],{cwd:root,env,input:JSON.stringify({path:p,method:'GET',role:'admin'}),encoding:'utf8'})).body;
  browser=await chromium.launch({channel:'chrome',headless:true});
  for(const width of [1440,375,390]){
   const page=await browser.newPage({viewport:{width,height:900},serviceWorkers:'block',reducedMotion:'reduce'});
   const errors=[];page.on('pageerror',e=>errors.push(e.message));
   await page.route('**/*',route=>{
    const u=new URL(route.request().url());
    if(u.hostname!=='blog-fixture.test')return route.abort();
    if(u.pathname==='/admin/blog'&&route.request().method()==='GET')return route.fulfill({contentType:'text/html',body:html});
    const f=path.join(root,u.pathname);
    if(f.startsWith(root+'/assets/')&&fs.existsSync(f)&&fs.statSync(f).isFile())return route.fulfill({path:f});
    return route.fulfill({status:404,body:''});
   });
   await page.goto('https://blog-fixture.test'+p);
   const panel=page.getByRole('region',{name:'Article revision history'});
   assert.ok(await panel.getByText('Original Markdown body',{exact:true}).isVisible());
   assert.ok(await panel.getByText('Current Markdown body',{exact:true}).isVisible());
   assert.ok(await panel.getByRole('button',{name:'Restore content'}).isVisible());
   const fields=await panel.locator('form').evaluate(f=>Object.fromEntries(new FormData(f)));
   assert.equal(fields.revision,seed[1]);assert.equal(fields.restore_revision,seed[0]);assert.equal(fields.confirm,undefined);
   await page.getByRole('button',{name:'Edit',exact:true}).click();
   assert.equal(await page.locator('#edit-revision').inputValue(),seed[1]);
   assert.equal(await page.locator('#edit-published').inputValue(),'false');
   assert.equal(await page.locator('#edit-slug-display').evaluate(e=>e.readOnly),true);
   const dim=await page.evaluate(()=>({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
   assert.ok(dim.scroll<=dim.client+2,JSON.stringify({width,...dim}));
   assert.deepEqual(errors,[]);
   console.log(JSON.stringify({width,page:'blog revisions',overflow:false,errors}));
   await page.close();
  }
 }finally{if(browser)await browser.close();fs.rmSync(dir,{recursive:true,force:true});}
})().catch(e=>{console.error(e);process.exit(1)});

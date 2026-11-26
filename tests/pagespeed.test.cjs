const assert=require('node:assert/strict');
const {test}=require('node:test');
const {execFileSync}=require('node:child_process');
const {mkdtemp,rm}=require('node:fs/promises');
const path=require('node:path'),os=require('node:os');
const root=path.resolve(__dirname,'..'),env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://fixture.example.test'};
const php=code=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code],{cwd:root,env,encoding:'utf8'}));
test('PageSpeed uses explicit device strategy and truthful bounded lab/page/origin results',()=>{
  const result=php([
    '$c=new App\\Integrations\\GoogleSiteKit\\PageSpeedClient();$cache=[];$now=strtotime("2026-11-27T00:00:00Z");$calls=[];$mode="ready";',
    '$get=function($endpoint,$params)use(&$calls,&$mode,$now){$calls[]=[$endpoint,$params];if($mode==="quota")throw new RuntimeException("private provider text",429);if($mode==="access")throw new RuntimeException("denied",403);',
    'return ["analysisUTCTimestamp"=>gmdate("Y-m-d\\TH:i:s\\Z",$now),"lighthouseResult"=>["requestedUrl"=>$params["url"],"finalUrl"=>$mode==="wrong"?"https://different.example.test/":$params["url"],"configSettings"=>["formFactor"=>$params["strategy"]],"categories"=>["performance"=>["score"=>0.83]],"audits"=>["largest-contentful-paint"=>["numericValue"=>2210],"cumulative-layout-shift"=>["numericValue"=>0.08]]],"loadingExperience"=>["id"=>$params["url"],"origin_fallback"=>$mode==="fallback","metrics"=>["LARGEST_CONTENTFUL_PAINT_MS"=>["percentile"=>2350,"category"=>"FAST"]]],"originLoadingExperience"=>["id"=>"https://fixture.example.test","metrics"=>["INTERACTION_TO_NEXT_PAINT"=>["percentile"=>180,"category"=>"FAST"]]]];};',
    '$mobile=$c->run("https://fixture.example.test","/","mobile","",$cache,$get,$now);$c->run("https://fixture.example.test","/","mobile","",$cache,$get,$now+5);',
    '$desktop=$c->run("https://fixture.example.test","/","desktop","",$cache,$get,$now);',
    '$mode="fallback";$fallback=$c->run("https://fixture.example.test","/about","mobile","",$cache,$get,$now);',
    '$mode="wrong";$wrong=$c->run("https://fixture.example.test","/about","desktop","",$cache,$get,$now);',
    '$mode="quota";$stale=$c->run("https://fixture.example.test","/","mobile","",$cache,$get,$now+2000);',
    '$mode="access";$denied=$c->run("https://fixture.example.test","/","mobile","",$cache,$get,$now+3000);',
    '$mode="quota";$quota=$c->run("https://fixture.example.test","/","desktop","synthetic-key-123456789012",$cache,$get,$now+3000);',
    '$bad=[];foreach([["https://fixture.example.test","/admin"],["https://fixture.example.test","/?email=private"],["https://user:pass@fixture.example.test","/"],["http://127.0.0.1","/"]] as [$origin,$path]){try{$c::target($origin,$path);$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}}',
    'echo json_encode(compact("mobile","desktop","fallback","wrong","stale","denied","quota","bad","cache","calls"),JSON_THROW_ON_ERROR);'
  ].join(' '));
  assert.equal(result.mobile.state,'ready');assert.equal(result.mobile.data.score,83);
  assert.equal(result.mobile.data.lab[0].value,2210);
  assert.equal(result.mobile.data.page_field[0].value,2350);assert.equal(result.mobile.data.origin_field[0].value,180);
  assert.equal(result.desktop.data.strategy,'desktop');assert.equal(result.calls[1][1].strategy,'desktop');
  assert.equal(result.calls[0][0],'https://www.googleapis.com/pagespeedonline/v5/runPagespeed');
  assert.equal(result.calls.length,7);assert.deepEqual(result.fallback.data.page_field,[]);
  assert.equal(result.wrong.state,'unavailable');assert.equal(result.wrong.data,null);
  assert.equal(result.stale.state,'stale');assert.equal(result.stale.data.score,83);
  assert.equal(result.denied.state,'access');assert.equal(result.denied.data,null);
  assert.equal(result.quota.state,'quota');assert.equal(result.quota.data,null);assert.ok(result.bad.every(Boolean));
  assert.doesNotMatch(JSON.stringify(result.cache),/synthetic-key|private provider text/);
});
test('real owner PageSpeed route rejects guests, customers, bad CSRF and arbitrary targets',async()=>{
  const dir=await mkdtemp(path.join(os.tmpdir(),'bapxcms-psi-'));
  try{
    const ask=(role,fields)=>JSON.parse(execFileSync('php',['tests/fixtures/pagespeed-route.php',dir],{cwd:root,env,input:JSON.stringify({role,fields}),encoding:'utf8'}));
    for(const [role,fields] of [['guest',{path:'/',strategy:'mobile'}],['customer',{path:'/',strategy:'mobile'}],['admin',{path:'/',strategy:'desktop',_csrf:'bad'}]]){
      const r=ask(role,fields);assert.equal(r.status,302);assert.equal(r.reports,null);
    }
    for(const fields of [{path:'/account',strategy:'mobile'},{path:'/',strategy:'tablet'},{path:['/'],strategy:'mobile'}]){
      const r=ask('admin',fields);assert.equal(r.status,302);assert.equal(r.flash.type,'error');
      assert.ok(!r.reports||Object.keys(r.reports).length===0);
    }
  }finally{await rm(dir,{recursive:true,force:true});}
});

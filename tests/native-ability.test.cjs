const assert=require('node:assert/strict');
const {test}=require('node:test');
const {execFileSync}=require('node:child_process');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
function php(code){
 const stdout=execFileSync('php',['-r','require "app/bootstrap.php"; '+code],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1'},encoding:'utf8'});
 return JSON.parse(stdout);
}
test('missing canonical fee settings are unavailable, not a successful empty tool result',()=>{
 const data=php(String.raw`
 $registry=new App\Services\AgentToolRegistry(modules:['shop'=>false,'blog'=>false,'campaigns'=>false],scope:'support');
 $trace=new App\Services\AgentRunTrace();
 $result=$registry->executeModelCall('published_consultation_fees',[],$trace,'fixture-call');
 echo json_encode(['result'=>$result,'trace'=>$trace->metadata(),'evidence'=>$registry->modelResults()]);
 `);
 assert.equal(data.result.code,'ability_unavailable');
 assert.equal(data.trace.steps[0].outcome,'error');
 assert.deepEqual(data.evidence,[]);
});
test('native ability catalogue exposes strict typed metadata and denies unknown scopes and names',()=>{
 const data=php(String.raw`
 $registry=new App\Services\NativeAbilityRegistry(new App\Services\DatabaseService(),'support',['shop'=>false,'blog'=>false,'campaigns'=>false]);
 $fee=$registry->inspect('published_consultation_fees');
 $invalid=$registry->invoke('published_consultation_fees',['extra'=>true]);
 $unknown=$registry->invoke('owner_dashboard_summary',[]);
 $scopeDenied=false;try{new App\Services\NativeAbilityRegistry(new App\Services\DatabaseService(),'external',[]);}catch(InvalidArgumentException){$scopeDenied=true;}
 $schema=$fee['output_schema'];
 $bad=['fees'=>[],'inclusions'=>[],'source'=>'/contact#consultation-fees','scope'=>'Published consultation display fees only. An enquiry does not confirm an appointment or initiate payment.','private'=>'forbidden'];
 $good=array_diff_key($bad,['private'=>true]);
 echo json_encode(['fee'=>$fee,'invalid'=>$invalid,'unknown'=>$unknown,'scopeDenied'=>$scopeDenied,'rejectExtra'=>!App\Services\AbilitySchema::valid($bad,$schema),'validEmpty'=>App\Services\AbilitySchema::valid($good,$schema),'invalidSchema'=>!App\Services\AbilitySchema::valid('x',['type'=>'string','unknown'=>true])]);
 `);
 assert.equal(data.fee.version,1);
 assert.deepEqual(data.fee.annotations,{readonly:true,destructive:false,idempotent:true});
 assert.equal(data.invalid.code,'ability_invalid_input');
 assert.equal(data.unknown.code,'ability_not_available');
 assert.equal(data.scopeDenied,true);
 assert.equal(data.rejectExtra,true);assert.equal(data.validEmpty,true);assert.equal(data.invalidSchema,true);
});
test('published clinic abilities use the native catalogue and recheck scoped invocation',()=>{
 const data=php(String.raw`
 $db=new App\Services\DatabaseService();
 $registry=new App\Services\NativeAbilityRegistry($db,'support',['campaigns'=>true]);
 $disabled=new App\Services\NativeAbilityRegistry($db,'support',['campaigns'=>false]);
 $tools=new App\Services\AgentToolRegistry($db,modules:['shop'=>false,'blog'=>false,'campaigns'=>true],scope:'support');
 $trace=new App\Services\AgentRunTrace();
 $bad=$tools->executeModelCall('published_programme_detail',['slug'=>'../private'],$trace,'bad-slug');
 echo json_encode([
   'names'=>array_column($registry->discover(),'name'),
   'disabled'=>array_column($disabled->discover(),'name'),
   'invalid'=>$bad,'trace'=>$trace->metadata(),
   'missing'=>$registry->invoke('published_programme_detail',['slug'=>'missing']),
   'empty'=>$registry->invoke('published_practitioners',[]),
   'deny'=>$disabled->invoke('published_programmes',[]),
   'google'=>$tools->googleDeclarations(),
   'compatible'=>$tools->completionDeclarations(),
 ]);
 `);
 assert.deepEqual(data.names,['published_consultation_fees','published_practitioners','published_programmes','published_programme_detail','search_clinic_knowledge']);
 assert.deepEqual(data.disabled,['published_consultation_fees','published_practitioners','search_clinic_knowledge']);
 assert.equal(data.invalid.code,'ability_invalid_input');
 assert.equal(data.trace.steps[0].outcome,'error');
 assert.equal(data.missing.code,'ability_not_found');
 assert.deepEqual(data.empty,{practitioners:[]});
 assert.equal(data.deny.code,'ability_not_available');
 const google=data.google.find(t=>t.name==='published_programme_detail');
 assert.equal(google.parameters.properties.slug.type,'STRING');
 assert.deepEqual(google.parameters.required,['slug']);
 const compatible=data.compatible.find(t=>t.function.name==='published_programme_detail');
 assert.equal(compatible.function.parameters.additionalProperties,false);
 assert.deepEqual(compatible.function.parameters.required,['slug']);
});
test('public search contracts distinguish an empty result from unavailable knowledge and reject invalid input',()=>{
 const data=php(String.raw`
 $r=new App\Services\NativeAbilityRegistry(new App\Services\DatabaseService(),'support',['shop'=>false,'blog'=>true,'campaigns'=>false]);
 $disabled=new App\Services\NativeAbilityRegistry(new App\Services\DatabaseService(),'support',['shop'=>false,'blog'=>false,'campaigns'=>false]);
 echo json_encode([
  'names'=>array_column($r->discover(),'name'),
  'empty'=>$r->invoke('search_articles',['query'=>'no-such-fixture-8dec9f']),
  'unavailable'=>$r->invoke('search_clinic_knowledge',['query'=>'consultation']),
  'invalid'=>$r->invoke('search_clinic_knowledge',['query'=>[]]),
  'blank'=>$r->invoke('search_articles',['query'=>'   ']),
  'disabled'=>$disabled->invoke('search_articles',['query'=>'clinic']),
 ]);
 `);
 assert.ok(data.names.includes('search_clinic_knowledge')&&data.names.includes('search_articles'));
 assert.deepEqual(data.empty,{found:0,articles:[]});
 assert.equal(data.unavailable.code,'ability_unavailable');
 assert.equal(data.invalid.code,'ability_invalid_input');
 assert.equal(data.blank.code,'ability_invalid_input');
 assert.equal(data.disabled.code,'ability_not_available');
});

test('strict article scan is read only, filters draft and disabled categories and rejects unreadable or aliased sources',()=>{
 const fs=require('node:fs'),os=require('node:os');
 const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'nebo-article-fixture-'));
 try {
  const write=(name,published,category,title)=>fs.writeFileSync(path.join(tmp,name+'.md'),'---\ntitle: '+title+'\nslug: '+name+'\ncategory: '+category+'\npublished: '+published+'\nsummary: தமிழ் wellness\n---\n\nPublic fixture body');
  write('visible',true,'wellness','தமிழ் wellness');
  write('draft',false,'wellness','Draft private');
  write('shop-only',true,'products','Hidden module');
  const p=JSON.stringify(tmp);
  const result=php('$blog=new App\\Services\\BlogService('+p+'); $m=["blog"=>true,"shop"=>false]; echo json_encode(["slugs"=>array_column($blog->all(false,$m),"slug"),"disabled"=>$blog->all(false,["blog"=>false]),"missing"=>is_dir('+JSON.stringify(tmp+'/absent')+')]);');
  assert.deepEqual(result.slugs,['visible']);assert.deepEqual(result.disabled,[]);
  const unknown=php('$p='+JSON.stringify(tmp+'/absent')+'; $b=new App\\Services\\BlogService($p);$failed=false;try{$b->all(false,["blog"=>true]);}catch(Throwable){$failed=true;}echo json_encode(["failed"=>$failed,"created"=>is_dir($p)]);');
  assert.deepEqual(unknown,{failed:true,created:false});
  fs.symlinkSync(path.join(tmp,'visible.md'),path.join(tmp,'aliased.md'));
  const unsafe=php('$b=new App\\Services\\BlogService('+p+'); $failed=false;try{$b->all(false,["blog"=>true]);}catch(Throwable){$failed=true;}echo json_encode($failed);');
  assert.equal(unsafe,true);
 }finally{fs.rmSync(tmp,{recursive:true,force:true});}
});

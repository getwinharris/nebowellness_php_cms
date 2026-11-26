const assert=require('node:assert/strict');
const {test}=require('node:test');
const {execFileSync}=require('node:child_process');
const {root,env,alpha,beta,state}=require('./fixtures/site-profile-data.cjs');
function php(code,input={}){
  return JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; $in=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR); '+code],{cwd:root,env,input:JSON.stringify(input),encoding:'utf8'}));
}
test('published identity projection and A2UI exclude drafts, previous identity and private settings',()=>{
  const out=php(String.raw`
  $record=['id'=>'app_settings','site_profile'=>$in['state'],'clinic_phones'=>['+12025550101','bad'],'clinic_whatsapp'=>['+12025550102'],'private'=>'fixture secret'];
  $identity=App\Services\SiteProfileService::publicIdentity($record);
  $db=new App\Services\DatabaseService();
  $r=new App\Services\NativeAbilityRegistry($db,'support',['blog'=>false,'campaigns'=>false]);
  $schema=$r->inspect('published_site_identity')['output_schema'];
  $ui=new App\Services\A2uiCatalogService($r);
  $bad=[];foreach ([array_replace($record,['id'=>'legacy']),array_replace($record,['site_profile'=>['version'=>99,'published'=>$in['state']['published']]]),array_replace($record,['site_profile'=>['version'=>1,'published'=>null]])] as $candidate){
      try{App\Services\SiteProfileService::publicIdentity($candidate);$bad[]=false;}catch(DomainException){$bad[]=true;}
  }
  echo json_encode(['identity'=>$identity,'valid'=>App\Services\AbilitySchema::valid($identity,$schema),
    'invalid'=>App\Services\AbilitySchema::valid($identity+['draft'=>'bad'],$schema),
    'card'=>$ui->observedSurface(['published_site_identity'=>$identity],'0123456789abcdef'),
    'badCard'=>$ui->observedSurface(['published_site_identity'=>$identity+['private'=>'bad']],'0123456789abcdef'),
    'bad'=>$bad,'missing'=>$r->invoke('published_site_identity',[]),
    'invalidArgs'=>$r->invoke('published_site_identity',['include_draft'=>true])]);
  `,{state:state(alpha,beta,{...beta,name:'Previous fixture'},true)});
  assert.equal(out.identity.name,alpha.name);
  assert.deepEqual(out.identity.phones,['+12025550101']);
  assert.equal(out.valid,true);assert.equal(out.invalid,false);
  assert.equal(out.badCard,null);
  assert.deepEqual(out.bad,[true,true,true]);
  assert.equal(out.missing.code,'ability_unavailable');assert.equal(out.invalidArgs.code,'ability_invalid_input');
  for(const value of [out.identity,out.card]){
    const json=JSON.stringify(value);assert.match(json,/Orchid Studio/);
    assert.doesNotMatch(json,/Cedar Services|Previous fixture|fixture secret|Orchid Studio Admin|cedar@example\.test/);
  }
  assert.match(JSON.stringify(out.card),/orchid@example\.test/);
});
test('identity questions require actual evidence and reject invented concrete contact values',()=>{
  const out=php(String.raw`
  $r=new App\Services\AgentToolRegistry(modules:['shop'=>false,'blog'=>true,'campaigns'=>true],scope:'support');
  $trace=new App\Services\AgentRunTrace();
  $trace->provider('google','fixture',200,0,microtime(true),true);
  $q='What is the public email address?';
  $direct=$r->acceptsEvidence($q,'wrong@example.test',$trace);
  $trace->tool('search_articles','ok',microtime(true),'model','unrelated');
  $trace->provider('google','fixture',200,0,microtime(true),true);
  $unrelated=$r->acceptsEvidence($q,'wrong@example.test',$trace);
  $identity=['public_email'=>'public@example.test','phones'=>['+12025550101'],'whatsapp'=>['+12025550102']];
  echo json_encode(['direct'=>$direct,'unrelated'=>$unrelated,'required'=>$r->requiredEvidence($q),
    'ordinary'=>$r->requiredEvidence('Draft a general guide about contact details'),
    'private'=>$r->requiredEvidence('What is my email address?'),
    'validEmail'=>$r::matchesIdentityFacts($q,'Public email: public@example.test.',$identity),
    'badEmail'=>$r::matchesIdentityFacts($q,'Email wrong@example.test',$identity),
    'validPhone'=>$r::matchesIdentityFacts('What is the phone number?','Call +1 (202) 555-0101.',$identity),
    'wrongPhone'=>$r::matchesIdentityFacts('What is the phone number?','Call +1 202 555 0102.',$identity),
    'missing'=>$r::matchesIdentityFacts('What is the email address?','Not published.',['public_email'=>'','phones'=>[],'whatsapp'=>[]])]);
  `);
  assert.equal(out.direct,false);assert.equal(out.unrelated,false);
  assert.deepEqual(out.required,[['published_site_identity']]);
  assert.deepEqual(out.ordinary,[]);assert.deepEqual(out.private,[]);
  assert.equal(out.validEmail,true);assert.equal(out.badEmail,false);
  assert.equal(out.validPhone,true);assert.equal(out.wrongPhone,false);assert.equal(out.missing,true);
});

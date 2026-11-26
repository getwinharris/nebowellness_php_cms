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

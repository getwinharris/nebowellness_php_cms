const assert=require('node:assert/strict');
const {test}=require('node:test');
const {execFileSync}=require('node:child_process');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
function php(code) {
  return JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code],{
    cwd:root,env:{...process.env,BAPX_TEST_MODE:'1'},encoding:'utf8'
  }));
}
test('versioned literal-only catalog rejects unsupported components and unsafe properties',()=>{
  const result=php(String.raw`
    $c=App\Services\A2uiCatalogService::catalog();
    $valid=['id'=>'root','component'=>'Stack','direction'=>'vertical','children'=>['first','second']];
    $cases=[
      $valid,
      ['id'=>'first','component'=>'Text','text'=>'<script>alert(1)</script>'],
      ['id'=>'second','component'=>'Metric','label'=>'Enquiries','value'=>'12'],
      ['id'=>'first','component'=>'Text','text'=>'safe','html'=>'<script>'],
      ['id'=>'first','component'=>'Text','text'=>['path'=>'/private']],
      ['id'=>'bad-id','component'=>'Text','text'=>'safe'],
      array_replace($valid,['children'=>['first','first']]),
      array_replace($valid,['children'=>['root']]),
      array_replace($valid,['children'=>array_fill(0,25,'x')]),
      ['id'=>'root','component'=>'Button','action'=>'delete'],
      ['id'=>'root','component'=>'Text','text'=>str_repeat('x',4001)],
      ['id'=>'root','component'=>'Text','text'=>'safe','@catalogId'=>'untrusted'],
    ];
    echo json_encode(['catalog'=>$c,'valid'=>array_map([App\Services\A2uiCatalogService::class,'validComponent'],$cases)]);
  `);
  assert.equal(result.catalog.protocolVersion,'1.0');
  assert.equal(result.catalog.catalogId,result.catalog.$id);
  assert.deepEqual(Object.keys(result.catalog.components),['Text','Stack','Metric','Card']);
  assert.deepEqual(result.catalog.functions,{});
  assert.deepEqual(result.valid,[true,true,true,false,false,false,false,false,false,false,false,false]);
});
test('server ability metadata follows trusted native scope and enabled modules, not renderer functions',()=>{
  const data=php(String.raw`
    $db=new App\Services\DatabaseService();
    $scopes=[];
    foreach (['support','admin'] as $scope) {
      foreach ([false,true] as $enabled) {
        $registry=new App\Services\NativeAbilityRegistry($db,$scope,['shop'=>true,'blog'=>$enabled,'campaigns'=>$enabled]);
        $ui=new App\Services\A2uiCatalogService($registry);
        $scopes[$scope.($enabled?'On':'Off')]=$ui->serverAbilities();
      }
    }
    echo json_encode($scopes);
  `);
  const names=xs=>xs.map(x=>x.name);
  assert.ok(names(data.supportOn).includes('published_programmes'));
  assert.ok(!names(data.supportOff).includes('published_programmes'));
  assert.ok(!names(data.supportOn).includes('appointment_summary'));
  assert.ok(names(data.adminOn).includes('appointment_summary'));
  assert.ok(!names(data.adminOff).includes('therapy_enquiry_summary'));
  for(const scope of Object.values(data)) {
    assert.ok(!names(scope).includes('recent_orders'));
    assert.ok(!names(scope).includes('clinic_context'));
    assert.ok(scope.every(x=>x.annotations.readonly && !x.annotations.destructive && x.input_schema && x.output_schema));
  }
});

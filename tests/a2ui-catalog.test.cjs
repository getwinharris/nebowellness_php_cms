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

test('server projects only fresh scoped native results into a validated tree',()=>{
  const data=php(String.raw`
    $db=new App\Services\DatabaseService();
    $public=new App\Services\A2uiCatalogService(new App\Services\NativeAbilityRegistry($db,'support',['blog'=>true,'campaigns'=>true]));
    $owner=new App\Services\A2uiCatalogService(new App\Services\NativeAbilityRegistry($db,'admin',['blog'=>true,'campaigns'=>true]));
    $off=new App\Services\A2uiCatalogService(new App\Services\NativeAbilityRegistry($db,'support',['blog'=>false,'campaigns'=>false]));
    $appointments=App\Services\OwnerAbilityService::summariseAppointments([['status'=>'completed']]);
    $articles=['found'=>1,'articles'=>[['title'=>'<img src=x onerror=alert(1)>','path'=>'/blog/example','category'=>'fixture','score'=>5]]];
    $evidence=['appointment_summary'=>$appointments,'search_articles'=>$articles,'clinic_context'=>['secret'=>'private-fixture'],'recent_orders'=>['id'=>'retail-fixture']];
    $trace='0123456789abcdef';
    $message=$public->observedSurface($evidence,$trace);
    $tree=$message['createSurface']['components'];
    $extra=$tree;$extra[]=['id'=>'unused','component'=>'Text','text'=>'orphan'];
    $duplicate=$tree;$duplicate[]=$tree[0];
    $missing=$tree;$missing[0]['children']=['no_such_child'];
    $cyclic=[['id'=>'root','component'=>'Stack','direction'=>'vertical','children'=>['child']],['id'=>'child','component'=>'Stack','direction'=>'vertical','children'=>['root']]];
    $shared=[['id'=>'root','component'=>'Stack','direction'=>'vertical','children'=>['card_a','card_b']],['id'=>'card_a','component'=>'Card','title'=>'A','children'=>['leaf']],['id'=>'card_b','component'=>'Card','title'=>'B','children'=>['leaf']],['id'=>'leaf','component'=>'Text','text'=>'shared']];
    $valid=[App\Services\A2uiCatalogService::validTree($tree),App\Services\A2uiCatalogService::validTree($extra),App\Services\A2uiCatalogService::validTree($duplicate),App\Services\A2uiCatalogService::validTree($missing),App\Services\A2uiCatalogService::validTree($cyclic),App\Services\A2uiCatalogService::validTree($shared)];
    echo json_encode(['support'=>$message,'owner'=>$owner->observedSurface($evidence,$trace),'off'=>$off->observedSurface($evidence,$trace),'invalid'=>$public->observedSurface(['search_articles'=>['found'=>1,'articles'=>[],'extra'=>'invalid']],$trace),'trace'=>$owner->observedSurface($evidence,'bad'),'trees'=>$valid]);
  `);
  assert.deepEqual(data.trees,[true,false,false,false,false,false]);
  const publicJson=JSON.stringify(data.support),ownerJson=JSON.stringify(data.owner);
  assert.match(publicJson,/Published article search/);
  assert.doesNotMatch(publicJson,/Historical appointments|private-fixture|retail-fixture/);
  assert.match(ownerJson,/Total historical appointments/);
  assert.doesNotMatch(ownerJson,/private-fixture|retail-fixture/);
  assert.equal(data.off,null);
  assert.equal(data.invalid,null);
  assert.equal(data.trace,null);
});

test('shared browser renderer validates the entire snapshot and treats hostile text as text',()=>{
  const renderer=require('../assets/a2ui-readonly.js');
  const message={version:'1.0',createSurface:{catalogId:'https://bapx.in/schemas/bapxcms/a2ui/1.0/catalog',surfaceId:'records_0123456789abcdef',components:[
    {id:'root',component:'Stack',direction:'vertical',children:['card']},
    {id:'card',component:'Card',title:'Fixture results',children:['text','metric']},
    {id:'text',component:'Text',text:'<img src=x onerror=alert(1)>'},
    {id:'metric',component:'Metric',label:'Requests',value:'12'},
  ]}};
  const doc={createElement(tag){return {tag,children:[],setAttribute(){},append(...children){this.children.push(...children);},set innerHTML(v){assert.fail('Unsafe HTML '+v);}};}};
  const target={ownerDocument:doc,children:[],append(v){this.children.push(v);}};
  assert.equal(renderer.append(target,message),true);
  const dom=JSON.stringify(target.children);
  assert.match(dom,/onerror=alert/);
  assert.doesNotMatch(dom,/"tag":"img"/);
  assert.match(dom,/Requests/);
  const mutate=fn=>{const copy=structuredClone(message);fn(copy);return copy;};
  const invalid=[
    mutate(x=>x.version='0.8'),mutate(x=>x.createSurface.catalogId='evil'),
    mutate(x=>x.createSurface.surfaceId+='\n'),mutate(x=>x.createSurface.components[2].id+='\n'),
    mutate(x=>x.createSurface.components.push(x.createSurface.components[0])),
    mutate(x=>x.createSurface.components.push({id:'unused',component:'Text',text:'orphan'})),
    mutate(x=>x.createSurface.components[1].children=['root']),
    mutate(x=>x.createSurface.components[1].children=['missing']),
    mutate(x=>x.createSurface.components[2].html='<script>'),
    mutate(x=>x.createSurface.components[2].text={'@path':'/private'}),
    mutate(x=>x.createSurface.components[3].component='Button'),
    mutate(x=>x.createSurface.components[3].value='x'.repeat(161)),
    mutate(x=>x.createSurface.components[0].children=['card','card']),
    mutate(x=>x.createSurface.sendDataModel=true),
    {version:'1.0',callRendererFunction:{name:'delete'}},
  ];
  for(const value of invalid) assert.equal(renderer.append(target,value),false);
  assert.equal(target.children.length,1,'Text answer remains untouched on malformed surface');
});

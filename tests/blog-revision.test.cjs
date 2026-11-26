const assert=require('node:assert/strict');
const {test}=require('node:test');
const {spawn,execFileSync}=require('node:child_process');
const {mkdtemp,rm,mkdir,readFile,stat,chmod,writeFile}=require('node:fs/promises');
const path=require('node:path'),os=require('node:os'),crypto=require('node:crypto');
const root=path.resolve(__dirname,'..'),env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://blog-fixture.test'};
const php=(code,args=[])=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code,...args],{cwd:root,env,encoding:'utf8'}));
function asyncPhp(code,args=[]){
 return new Promise((resolve,reject)=>{
  const p=spawn('php',['-r','require "app/bootstrap.php"; '+code,...args],{cwd:root,env});let body='',err='';
  p.stdout.on('data',x=>body+=x);p.stderr.on('data',x=>err+=x);p.once('error',reject);p.once('close',c=>{if(c!==0)return reject(Error(err));try{resolve(JSON.parse(body));}catch(e){reject(e);}});
 });
}
test('Markdown revisions use exact source hashes and preserve private immutable history under concurrent writes',async()=>{
 const dir=await mkdtemp(path.join(os.tmpdir(),'bapxcms-blog-history-'));
 try{
  const base={slug:'fixture',title:'First editorial version',content:'Original private history fixture',published:true,published_at:'2020-01-01',category:'wellness'};
  const save='$b=new App\\Services\\BlogService($argv[1]);try{echo json_encode(["saved"=>$b->save(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR),$argv[3])]);}catch(DomainException){echo json_encode(["conflict"=>true]);}';
  const write=(data,rev)=>php(save,[dir,JSON.stringify(data),rev]);
  const first=write(base,'new').saved;const firstBytes=await readFile(path.join(dir,'fixture.md'));
  assert.equal(first._revision,crypto.createHash('sha256').update(firstBytes).digest('hex'));
  assert.equal(write(base,'new').conflict,true);
  const update={...base,title:'Second version',content:'Current hidden body',published:false,published_at:'2999-01-01',category:'help',seo_title:'Later SEO field'};
  const second=write(update,first._revision).saved;
  assert.equal(write(base,first._revision).conflict,true);
  const history=php('$b=new App\\Services\\BlogService($argv[1]);echo json_encode($b->revisions("fixture"));',[dir]);
  assert.equal(history.length,1);assert.equal(history[0].revision,first._revision);
  assert.doesNotMatch(JSON.stringify(history),/Original private history fixture/);
  const privateFile=path.join(dir,'.private-revisions','fixture',first._revision+'.md');
  assert.deepEqual(await readFile(privateFile),firstBytes);
  assert.equal((await stat(privateFile)).mode&0o777,0o600);
  const restored=php('$b=new App\\Services\\BlogService($argv[1]);echo json_encode($b->restore("fixture",$argv[2],$argv[3]));',[dir,first._revision,second._revision]);
  assert.equal(restored.title,base.title);assert.equal(restored.content,base.content);
  assert.equal(restored.published,false);assert.equal(restored.published_at,'2999-01-01');assert.equal(restored.category,'help');assert.equal(restored.seo_title,'');
  // Two editors using the same observed revision cannot overwrite one another.
  const [a,b]=await Promise.all([asyncPhp(save,[dir,JSON.stringify({slug:'fixture',content:'Concurrent one'}),restored._revision]),
                                asyncPhp(save,[dir,JSON.stringify({slug:'fixture',content:'Concurrent two'}),restored._revision])]);
  assert.equal([a,b].filter(x=>x.conflict).length,1);
  const winner=[a,b].find(x=>x.saved).saved;
  const state=php('$b=new App\\Services\\BlogService($argv[1]);echo json_encode($b->find("fixture"));',[dir]);
  assert.equal(state.content,winner.content);
  assert.equal(state._revision,winner._revision);
  // Corrupt history must fail closed and keep current bytes unchanged.
  await chmod(privateFile,0o644);
  const denied=php('$b=new App\\Services\\BlogService($argv[1]);try{$b->restore("fixture",$argv[2],$argv[3]);echo "false";}catch(Throwable){echo "true";}',[dir,first._revision,winner._revision]);
  assert.equal(denied,true);
  assert.equal(crypto.createHash('sha256').update(await readFile(path.join(dir,'fixture.md'))).digest('hex'),winner._revision);
  await chmod(privateFile,0o600);
  // Deleted slug history remains reserved, and its exact bytes remain private.
  php('$b=new App\\Services\\BlogService($argv[1]);$b->delete("fixture",$argv[2]);echo "true";',[dir,winner._revision]);
  assert.equal(write(base,'new').conflict,true);
  assert.equal(php('$b=new App\\Services\\BlogService($argv[1]);echo json_encode($b->find("fixture"));',[dir]),null);
  assert.deepEqual(await readFile(privateFile),firstBytes);
 }finally{await rm(dir,{recursive:true,force:true});}
});
test('actual owner blog routes enforce authorization, CSRF, stale revisions and confirmed restore',async()=>{
 const dir=await mkdtemp(path.join(os.tmpdir(),'bapxcms-blog-route-')),posts=path.join(dir,'posts');
 try{
  await mkdir(posts);
  const request=(route,fields={},role='admin',method='POST')=>JSON.parse(execFileSync('php',['tests/fixtures/blog-revision-route.php',dir,posts],{cwd:root,env,input:JSON.stringify({path:route,fields,role,method}),encoding:'utf8'}));
  const current=()=>php('$b=new App\\Services\\BlogService($argv[1]);echo json_encode($b->find("fixture"));',[posts]);
  const initial={slug:'fixture',title:'Public fixture title',content:'Previous source secret fixture',published:'true',published_at:'2020-01-01',revision:'new'};
  for(const role of ['guest','customer']){assert.equal(request('/admin/blog/save',initial,role).status,302);assert.equal(current(),null);}
  assert.equal(request('/admin/blog/save',{...initial,_csrf:'bad'}).flash.type,'error');assert.equal(current(),null);
  assert.equal(request('/admin/blog/save',initial).flash.type,'success');
  const first=current();
  assert.equal(request('/admin/blog/save',initial).flash.type,'error');
  const edit={...initial,title:'Hidden current title',content:'New private content',published:'false',published_at:'2999-01-01',revision:first._revision};
  assert.equal(request('/admin/blog/save',edit).flash.type,'success');
  const second=current();
  assert.equal(request('/admin/blog/save',edit).flash.type,'error');
  assert.equal(request('/admin/blog/toggle',{slug:'fixture',revision:first._revision}).flash.type,'error');
  assert.equal(request('/admin/blog/delete',{slug:'fixture',revision:first._revision}).flash.type,'error');
  const history=request('/admin/blog?history=fixture',{},'admin','GET');
  assert.equal(history.status,200);assert.match(history.body,/Revision history: fixture/);
  assert.match(history.body,new RegExp(first._revision));assert.doesNotMatch(history.body,/Previous source secret fixture/);
  const comparison=request('/admin/blog?history=fixture&version='+first._revision,{},'admin','GET');
  assert.equal(comparison.status,200);assert.match(comparison.body,/Review selected revision/);
  assert.match(comparison.body,/Previous source secret fixture/);assert.match(comparison.body,/New private content/);
  const fields={slug:'fixture',revision:second._revision,restore_revision:first._revision};
  assert.equal(request('/admin/blog/restore',fields).flash.type,'error');
  assert.equal(request('/admin/blog/restore',{...fields,confirm:'1',_csrf:'bad'}).flash.type,'error');
  assert.equal(request('/admin/blog/restore',{...fields,confirm:'1'},'customer').status,302);
  assert.equal(request('/admin/blog/restore',{...fields,confirm:'1'}).flash.type,'success');
  const restored=current();assert.equal(restored.content,initial.content);assert.equal(restored.published,false);assert.equal(restored.published_at,'2999-01-01');
  assert.equal(request('/admin/blog/restore',{...fields,confirm:'1'}).flash.type,'error');
 }finally{await rm(dir,{recursive:true,force:true});}
});

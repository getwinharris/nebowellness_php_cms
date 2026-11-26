const {test}=require('node:test');
const assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const root=path.resolve(__dirname,'..'),env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://fixture.example.test'};
const php=(code,...args)=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code,...args],{cwd:root,env,encoding:'utf8'}));
test('Markdown date gate uses site midnight, preserves hidden edits, and fails closed on invalid metadata',()=>{
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'bapxcms-blog-'));
 try {
 const r=php([
  '$b=new App\\Services\\BlogService($argv[1]);$class=App\\Services\\BlogService::class;',
  '$b->save(["slug"=>"future","title"=>"Future --- article","content"=>"PRIVATE_SCHEDULED_FIXTURE","published_at"=>"2999-01-01","published"=>true]);',
  '$b->save(["slug"=>"hidden","title"=>"Hidden article","content"=>"PRIVATE_HIDDEN_FIXTURE","published"=>false,"published_at"=>"2020-01-01"]);',
  '$b->save(["slug"=>"hidden","title"=>"Edited hidden","content"=>"PRIVATE_HIDDEN_FIXTURE"]);',
  '$b->save(["slug"=>"public","title"=>"Visible fixture article","content"=>"VISIBLE_FIXTURE","published_at"=>"2020-01-01","published"=>true]);',
  'file_put_contents($argv[1]."/invalid.md","---\\nslug: invalid\\npublished: true\\npublished_at: 2026-02-30\\n---\\nPRIVATE_INVALID_FIXTURE");',
  '$before=file_get_contents($argv[1]."/hidden.md");$rejected=false;try{$b->save(["slug"=>"hidden","published_at"=>"2026-02-30","content"=>"changed"]);}catch(InvalidArgumentException){$rejected=true;}',
  '$boundary=["published"=>true,"published_at"=>"2026-11-27"];',
  '$states=[$class::publicationState($boundary,new DateTimeImmutable("2026-11-26T18:29:59Z")),$class::publicationState($boundary,new DateTimeImmutable("2026-11-26T18:30:00Z"))];',
  '$search=(new App\\Services\\ClinicKnowledgeService(blog:$b))->searchArticles("fixture article");',
  'echo json_encode(["states"=>$states,"report"=>$b->publicationReport(),"public"=>array_column($b->all(false,["blog"=>true]),"slug"),"hidden"=>$b->find("hidden")["published"],"futureTitle"=>$b->find("future")["title"],"unchanged"=>$before===file_get_contents($argv[1]."/hidden.md"),"rejected"=>$rejected,"search"=>$search],JSON_THROW_ON_ERROR);'
 ].join(' '),dir);
 assert.deepEqual(r.states,['scheduled','visible']);
 assert.deepEqual(r.report.counts,{visible:1,hidden:1,scheduled:1,invalid:1});
 assert.equal(r.report.next_date,'2999-01-01');assert.deepEqual(r.public,['public']);assert.equal(r.hidden,false);
 assert.equal(r.futureTitle,'Future --- article');assert.ok(r.unchanged&&r.rejected);
 assert.deepEqual(r.search.articles.map(x=>x.path),['/blog/public']);
 const route=slug=>execFileSync('php',['tests/fixtures/blog-publication-route.php',dir,'/blog/'+slug],{cwd:root,env,encoding:'utf8'});
 for(const slug of ['future','hidden','invalid']){const out=route(slug);assert.match(out,/STATUS:404$/);assert.doesNotMatch(out,/PRIVATE_(SCHEDULED|HIDDEN|INVALID)_FIXTURE/);}
 assert.match(route('public'),/VISIBLE_FIXTURE/);assert.match(route('public'),/STATUS:200$/);
 const index=execFileSync('php',['tests/fixtures/blog-publication-route.php',dir,'/blog'],{cwd:root,env,encoding:'utf8'});
 assert.match(index,/Visible fixture article/);assert.doesNotMatch(index,/Future --- article|Edited hidden/);
 }finally{fs.rmSync(dir,{recursive:true,force:true});}
});
test('raw Markdown source is denied independently of publication route',()=>{
 const output=execFileSync('php',['-r','$_SERVER["REQUEST_URI"]="/content/blog/posts/how-to-request-a-consultation.md";register_shutdown_function(static fn()=>print(http_response_code()));require "index.php";'],{cwd:root,env,encoding:'utf8'});
 assert.equal(output,'403');
 assert.match(fs.readFileSync(path.join(root,'.htaccess'),'utf8'),/RewriteRule \^content\(\?:\/\|\$\) - \[F,L,NC\]/);
});

const {test}=require('node:test');
const assert=require('node:assert/strict');
const {spawnSync}=require('node:child_process');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const root=path.resolve(__dirname,'..'),cli=path.join(root,'cli/rea-skills.php');
const run=(r,...args)=>spawnSync('php',[cli,...args,'--root',r],{encoding:'utf8'});
const cat=JSON.parse(fs.readFileSync(path.join(root,'.agents/REA/catalog.json')));
test('REA skill catalog covers each pinned original with exactly one native adaptation',()=>{
 const check=run(root,'check');assert.equal(check.status,0,check.stderr||check.stdout);
 const list=run(root,'list');assert.equal(list.status,0,list.stderr);assert.match(list.stdout,/22 native adaptations/);
 assert.equal(cat.skills.length,22);assert.equal(new Set(cat.skills.map(s=>s.native)).size,22);
 for(const s of cat.skills){const result=run(root,'show',s.native);assert.equal(result.status,0,s.native);assert.match(result.stdout,/Native reconstruction contract/);assert.match(result.stdout,/Behavioral verification/);}
 assert.notEqual(run(root,'show','../reference/wordpress/skills/blueprint').status,0);
});
test('REA skill gate detects tampered references, omitted adaptations, invalid evidence and executable archival helpers',()=>{
 const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'rea-skill-test-'));
 try{
  fs.mkdirSync(path.join(tmp,'.agents'),{recursive:true});fs.cpSync(path.join(root,'.agents/REA'),path.join(tmp,'.agents/REA'),{recursive:true});
  fs.mkdirSync(path.join(tmp,'docs/research'),{recursive:true});fs.cpSync(path.join(root,'docs/research/rea'),path.join(tmp,'docs/research/rea'),{recursive:true});
  assert.equal(run(tmp,'check').status,0);
  const p=path.join(tmp,cat.skills[0].original),bytes=fs.readFileSync(p);fs.appendFileSync(p,'tampered');assert.notEqual(run(tmp,'check').status,0);fs.writeFileSync(p,bytes);
  const native=path.join(tmp,cat.skills[0].path),content=fs.readFileSync(native);fs.unlinkSync(native);assert.notEqual(run(tmp,'check').status,0);fs.writeFileSync(native,content);
  const helper=path.join(tmp,cat.sources.flatMap(s=>s.files).find(f=>f.copy.endsWith('.source.txt')).copy);fs.chmodSync(helper,0o755);assert.notEqual(run(tmp,'check').status,0);fs.chmodSync(helper,0o644);
  const catalog=path.join(tmp,'.agents/REA/catalog.json'),revised=JSON.parse(fs.readFileSync(catalog));revised.skills[0].evidence[0].locator='does-not-exist.php';fs.writeFileSync(catalog,JSON.stringify(revised));assert.notEqual(run(tmp,'check').status,0);
 }finally{fs.rmSync(tmp,{recursive:true,force:true});}
});

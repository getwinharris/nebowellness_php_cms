const {test}=require('node:test');
const assert=require('node:assert/strict');
const {execFileSync,spawnSync}=require('node:child_process');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
const cli=path.join(root,'cli/rea-progress.php');
const rel='docs/research/rea';
const ledger=JSON.parse(fs.readFileSync(path.join(root,rel,'capabilities.json')));
const run=(cwd,command,...args)=>spawnSync('php',[cli,command,'--root',cwd,...args],{encoding:'utf8'});
test('REA register calculates scope counts from rows, not handwritten percentages',()=>{
 const result=run(root,'status');
 assert.equal(result.status,0,result.stderr);
 const done=ledger.components.filter(r=>r.status==='implemented').length;
 assert.match(result.stdout,new RegExp('Total.*'+done+' / '+ledger.components.length));
});
test('REA validator rejects stale output, missing evidence anchors and altered evidence hashes',()=>{
 const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'nebo-rea-'));
 try {
  fs.mkdirSync(path.join(tmp,rel),{recursive:true});
  fs.cpSync(path.join(root,rel),path.join(tmp,rel),{recursive:true});
  for(const c of ledger.components)for(const e of [...c.source,...c.tests]){
   const out=path.join(tmp,e.path);fs.mkdirSync(path.dirname(out),{recursive:true});fs.copyFileSync(path.join(root,e.path),out);
  }
  assert.equal(run(tmp,'update').status,0);
  assert.equal(run(tmp,'check').status,0);
  const report=path.join(tmp,rel,'progress.md');fs.appendFileSync(report,'\nstale\n');
  assert.notEqual(run(tmp,'check').status,0);
  assert.equal(run(tmp,'update').status,0);
  const target=ledger.components.find(c=>c.status==='implemented').source[0];
  fs.writeFileSync(path.join(tmp,target.path),'<?php // removed implementation');
  assert.notEqual(run(tmp,'check').status,0);
  fs.copyFileSync(path.join(root,target.path),path.join(tmp,target.path));
  const m=JSON.parse(fs.readFileSync(path.join(tmp,rel,'evidence/manifest.json')));const a=m.sources.find(s=>s.artifact);fs.appendFileSync(path.join(tmp,a.artifact),'tampered');
  assert.notEqual(run(tmp,'check').status,0);
 } finally {fs.rmSync(tmp,{recursive:true,force:true});}
});
test('REA impact gate requires an append-only review for changed runtime and explicit scope changes',()=>{
 const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'nebo-rea-impact-'));
 const git=(...args)=>execFileSync('git',['-C',tmp,...args],{encoding:'utf8'}).trim();
 try {
  fs.mkdirSync(path.join(tmp,rel),{recursive:true});
  fs.cpSync(path.join(root,rel),path.join(tmp,rel),{recursive:true});
  for(const c of ledger.components)for(const e of [...c.source,...c.tests]){
   const out=path.join(tmp,e.path);fs.mkdirSync(path.dirname(out),{recursive:true});fs.copyFileSync(path.join(root,e.path),out);
  }
  git('init','-q');git('config','user.name','Fixture');git('config','user.email','fixture@example.test');git('add','.');git('commit','-qm','baseline');
  const base=git('rev-parse','HEAD');
  const source=path.join(tmp,ledger.components.find(c=>c.status==='implemented').source[0].path);
  fs.appendFileSync(source,'\n// unrelated fixture revision\n');git('add','.');git('commit','-qm','runtime');
  assert.notEqual(run(tmp,'impact',base).status,0);
  const reg=path.join(tmp,rel,'capabilities.json');const revised=JSON.parse(fs.readFileSync(reg));
  revised.updates.push({id:'fixture-review',date:'2026-11-26',issues:[141],components:[],summary:'Runtime fixture reviewed; no acceptance change.'});
  fs.writeFileSync(reg,JSON.stringify(revised,null,2));git('add','.');git('commit','-qm','review');
  assert.equal(run(tmp,'impact',base).status,0);
  revised.excluded.push('Fixture exclusion');fs.writeFileSync(reg,JSON.stringify(revised));git('add','.');git('commit','-qm','silent scope');
  assert.notEqual(run(tmp,'impact',base).status,0);
  revised.scope_revision++;revised.updates.at(-1).scope_change='Explicit fixture scope change';fs.writeFileSync(reg,JSON.stringify(revised));git('add','.');git('commit','-qm','documented scope');
  assert.equal(run(tmp,'impact',base).status,0);
  revised.updates[0].summary='Changed history';fs.writeFileSync(reg,JSON.stringify(revised));git('add','.');git('commit','-qm','rewrite history');
  assert.notEqual(run(tmp,'impact',base).status,0);
 } finally {fs.rmSync(tmp,{recursive:true,force:true});}
});

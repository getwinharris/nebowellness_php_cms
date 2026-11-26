const assert=require('node:assert/strict');
const {spawn,execFileSync}=require('node:child_process');
const {once}=require('node:events');
const {mkdtemp,rm}=require('node:fs/promises');
const net=require('node:net'),path=require('node:path'),os=require('node:os');
if(!['localhost','127.0.0.1'].includes(process.env.BAPX_MYSQL_HOST)||!/(?:_test|_fixture)$/.test(process.env.BAPX_MYSQL_DB||'')||process.env.BAPX_TEST_MODE==='1')throw Error('Use a disposable local test database.');
const root=path.resolve(__dirname,'..'),slug='cms-fixture-'+process.pid;
const env={...process.env,BAPX_TEST_MODE:'0',APP_URL:'http://127.0.0.1',BAPX_REMOTE_DB_URL:'http://127.0.0.1/remotedb'};
const php=(code,args=[])=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; $db=new App\\Services\\DatabaseService(true); '+code,...args],{cwd:root,env,encoding:'utf8'}));
const record=()=>php('echo json_encode($db->find("cms_pages",$argv[1]),JSON_THROW_ON_ERROR);',[slug]);
const revision=()=>php('echo json_encode(App\\Services\\ResourceService::revision($db->find("cms_pages",$argv[1])));',[slug]);
(async()=>{
    const dir=await mkdtemp(path.join(os.tmpdir(),'cms-page-route-'));let proc;
    assert.equal(record(),null);
    try{
        const listener=net.createServer();await new Promise(r=>listener.listen(0,'127.0.0.1',r));const port=listener.address().port;await new Promise(r=>listener.close(r));
        proc=spawn('php',['-S','127.0.0.1:'+port,path.join(root,'tests/fixtures/brand-appearance-route.php')],{cwd:root,env:{...env,BAPX_BRAND_ROUTE_FIXTURE:'1',BAPX_BRAND_SESSION_DIR:dir},stdio:'ignore'});
        const origin='http://127.0.0.1:'+port;
        for(let i=0;;i++){try{await fetch(origin+'/__fixture/flash');break;}catch(e){if(i>=60)throw e;await new Promise(r=>setTimeout(r,50));}}
        const get=(p,role='guest')=>fetch(origin+p,{headers:{'X-Fixture-Role':role},redirect:'manual'});
        const live=()=>get('/pages/'+slug);
        async function post(action,rev='',doc=null,opts={}){
            const f=new FormData();f.append('_csrf',opts.csrf??'brand-fixture-csrf');f.append('slug',slug);f.append('page_action',action);f.append('revision',rev);
            if(opts.confirm!==false)f.append('confirm','1');if(action==='create')f.append('title','Native fixture');
            if(doc){f.append('page[title]',doc.title);f.append('page[description]',doc.description);doc.sections.forEach((s,i)=>{const pre='page[sections]['+i+']';f.append(pre+'[id]',s.id);f.append(pre+'[type]',s.type);f.append(pre+'[visible]',s.visible?'1':'0');for(const [k,v] of Object.entries(s.content))f.append(pre+'[content]['+k+']',v);});}
            const r=await fetch(origin+'/admin/pages/save',{method:'POST',body:f,headers:{'X-Fixture-Role':opts.role??'admin',referer:origin+'/admin/pages'},redirect:'manual'});
            return {location:r.headers.get('location'),flash:await (await get('/__fixture/flash','admin')).json()};
        }
        assert.equal((await get('/admin/pages')).headers.get('location'),'/login');
        assert.equal((await post('create','',null,{role:'customer'})).location,'/');assert.equal(record(),null);
        assert.equal((await post('create','',null,{csrf:'bad'})).flash.type,'error');assert.equal(record(),null);
        assert.equal((await post('create')).flash.type,'success');const initial=revision();
        assert.equal((await post('create')).flash.type,'error');assert.equal(revision(),initial);
        assert.equal((await live()).status,404);
        assert.equal((await post('add:hero',initial,record().draft)).flash.type,'success');
        let d=record().draft;d.sections[0].content={heading:'Fixture visible heading',body:'First public version'};
        assert.equal((await post('add:faq',revision(),d)).flash.type,'success');
        d=record().draft;d.sections[1].visible=false;d.sections[1].content={heading:'Secret hidden section',body:'Private'};
        assert.equal((await post('save',revision(),d)).flash.type,'success');
        const preview=await (await get('/admin/pages?slug='+slug,'admin')).text();assert.ok(preview.includes('Saved draft preview'));
        assert.equal((await live()).status,404);assert.ok(!(await (await get('/sitemap.xml')).text()).includes('/pages/'+slug));
        assert.equal((await post('publish',initial)).flash.type,'error');
        assert.equal((await post('publish',revision(),null,{confirm:false})).flash.type,'error');
        assert.equal((await post('publish',revision())).flash.type,'success');
        let p=await live();assert.equal(p.status,200);let html=await p.text();assert.ok(html.includes('First public version'));assert.ok(!html.includes('Secret hidden section'));
        assert.ok((await (await get('/sitemap.xml')).text()).includes('/pages/'+slug));
        d=record().published;d.sections[0].content.body='Second private version';
        assert.equal((await post('save',revision(),d)).flash.type,'success');
        html=await (await live()).text();assert.ok(html.includes('First public version'));assert.ok(!html.includes('Second private version'));
        assert.equal((await post('publish',revision())).flash.type,'success');assert.ok((await (await live()).text()).includes('Second private version'));
        assert.equal((await post('restore',revision())).flash.type,'success');assert.ok((await (await live()).text()).includes('First public version'));
        assert.equal((await post('unpublish',revision())).flash.type,'success');assert.equal((await live()).status,404);assert.ok(!(await (await get('/sitemap.xml')).text()).includes('/pages/'+slug));
        assert.equal((await post('restore',revision())).flash.type,'success');assert.equal((await live()).status,200);
        console.log('PASS real CMS owner routes, duplicate creation, private drafts, role/CSRF, revision guards, hidden sections, publication, sitemap, unpublish and restore');
    }finally{
        if(proc&&proc.exitCode===null){const done=once(proc,'exit');proc.kill();await done;}
        php('$db->delete("cms_pages",$argv[1]);echo "true";',[slug]);await rm(dir,{recursive:true,force:true});
    }
})().catch(e=>{console.error(e);process.exitCode=1});

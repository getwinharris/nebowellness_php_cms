const assert=require('node:assert/strict');
const {spawn,execFileSync}=require('node:child_process');
const {once}=require('node:events');
const {mkdtemp,readdir,rm,stat,unlink}=require('node:fs/promises');
const net=require('node:net'),path=require('node:path'),os=require('node:os');
const root=path.resolve(__dirname,'..');
if(!['localhost','127.0.0.1'].includes(process.env.BAPX_MYSQL_HOST)||!/(?:_test|_fixture)$/.test(process.env.BAPX_MYSQL_DB||'')||process.env.BAPX_TEST_MODE==='1'){
    throw Error('Use a disposable local test database.');
}
const env={...process.env,BAPX_TEST_MODE:'0',APP_URL:'http://127.0.0.1',BAPX_REMOTE_DB_URL:'http://127.0.0.1/remotedb'};
const preamble='require "app/bootstrap.php"; $db=new App\\Services\\DatabaseService(true); $id=App\\Services\\SettingsService::SETTINGS_ID; ';
function php(code,args=[]){return JSON.parse(execFileSync('php',['-r',preamble+code,...args],{cwd:root,env,encoding:'utf8'}))}
function record(){return php('echo json_encode($db->find("settings",$id),JSON_THROW_ON_ERROR);')}
function revision(){return php('echo json_encode(App\\Services\\ResourceService::revision($db->find("settings",$id)));')}
function write(values){return php('$db->upsert("settings",["id"=>$id]+json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR)); echo "true";',[JSON.stringify(values)])}
const brandDir=path.join(root,'assets/images/brand');
async function files(){return (await readdir(brandDir).catch(e=>e.code==='ENOENT'?[]:Promise.reject(e))).filter(n=>/^(?:logo|favicon)-[0-9a-f]{32}\.(?:png|jpg|ico)$/.test(n))}
(async()=>{
    const original=record(),before=new Set(await files());
    const legacyId='brand-fixture-legacy';
    const temp=await mkdtemp(path.join(os.tmpdir(),'brand-route-'));
    let proc;
    try{
        const listener=net.createServer();await new Promise(r=>listener.listen(0,'127.0.0.1',r));const port=listener.address().port;await new Promise(r=>listener.close(r));
        proc=spawn('php',['-S','127.0.0.1:'+port,path.join(root,'tests/fixtures/brand-appearance-route.php')],{cwd:root,env:{...env,BAPX_BRAND_ROUTE_FIXTURE:'1',BAPX_BRAND_SESSION_DIR:temp},stdio:'ignore'});
        const origin='http://127.0.0.1:'+port;
        for(let i=0;;i++){try{await fetch(origin+'/__fixture/flash');break;}catch(e){if(i>=60)throw e;await new Promise(r=>setTimeout(r,50));}}
        const png=Buffer.from(execFileSync('php',['-r','$i=imagecreatetruecolor(16,16);ob_start();imagepng($i);echo base64_encode(ob_get_clean());'],{encoding:'utf8'}),'base64');
        async function post(fields,files={},role='admin'){
            const form=new FormData();
            for(const [k,v] of Object.entries(fields))form.append(k,v);
            for(const [k,v] of Object.entries(files))form.append(k,new Blob([v],{type:'image/png'}),'fixture.png');
            const r=await fetch(origin+'/admin/appearance/save',{method:'POST',body:form,headers:{'X-Fixture-Role':role,referer:origin+'/admin/appearance'},redirect:'manual'});
            const f=await fetch(origin+'/__fixture/flash');return {status:r.status,location:r.headers.get('location'),flash:await f.json()};
        }
        php('$db->delete("settings",$id); $db->upsert("settings",["id"=>$argv[1],"fixture_keep"=>"legacy"]); echo "true";',[legacyId]);
        let r=await post({_csrf:'brand-fixture-csrf',appearance_revision:'a'.repeat(64),palette_primary:'#123456'},{logo_file:png});
        assert.equal(r.flash.type,'error');assert.match(r.flash.message,/Canonical/);assert.equal(record(),null);
        assert.equal((await files()).filter(n=>!before.has(n)).length,0);
        php('$db->delete("settings",$argv[1]); echo "true";',[legacyId]);
        write({fixture_keep:'preserve',palette_canvas:'#FFFFFF',palette_text:'#222222',palette_link:'#2255AA'});
        const rev=revision();
        const page=await fetch(origin+'/admin/appearance');assert.equal(page.status,200);
        assert.ok((await page.text()).includes('name="appearance_revision" value="'+rev+'"'));
        r=await post({_csrf:'brand-fixture-csrf',appearance_revision:rev,palette_primary:'#123456'},{logo_file:png},'customer');
        assert.equal(r.location,'/');assert.equal(revision(),rev);
        r=await post({_csrf:'incorrect',appearance_revision:rev,palette_primary:'#123456'},{logo_file:png});
        assert.equal(r.flash.type,'error');assert.equal(revision(),rev);
        r=await post({_csrf:'brand-fixture-csrf',appearance_revision:rev,palette_primary:'#123456'},{logo_file:png,favicon_file:Buffer.from('invalid')});
        assert.equal(r.flash.type,'error');assert.equal(revision(),rev);
        assert.equal((await files()).filter(n=>!before.has(n)).length,0);
        write({fixture_other:'newer change'});
        r=await post({_csrf:'brand-fixture-csrf',appearance_revision:rev,palette_primary:'#123456'},{logo_file:png});
        assert.equal(r.flash.type,'error');assert.match(r.flash.message,/changed/i);
        assert.equal((await files()).filter(n=>!before.has(n)).length,0);
        r=await post({_csrf:'brand-fixture-csrf',appearance_revision:revision(),palette_primary:'#123456'},{logo_file:png});
        assert.equal(r.status,302);assert.equal(r.flash.type,'success',JSON.stringify(r.flash));
        const saved=record();assert.equal(saved.fixture_keep,'preserve');assert.equal(saved.fixture_other,'newer change');
        assert.equal(saved.palette_primary,'#123456');assert.match(saved.logo_url,/^\/assets\/images\/brand\/logo-[0-9a-f]{32}\.png$/);
        const assetId=path.basename(saved.logo_url).slice(5,37);
        const media=php('$r=$db->find("media_files",$argv[1]);echo json_encode($r,JSON_THROW_ON_ERROR);',[assetId]);
        assert.equal(media.url,saved.logo_url);assert.equal(media.context,'brand');
        const blocked=php('echo json_encode((new App\\Services\\MediaService(store:$db))->setArchived([$argv[1]],true));',[assetId]);
        assert.equal(blocked.blocked,1);
        r=await post({_csrf:'brand-fixture-csrf',appearance_revision:revision(),logo_remove:'1'});
        assert.equal(r.flash.type,'success');assert.equal(record().logo_url,'');
        const archived=php('echo json_encode((new App\\Services\\MediaService(store:$db))->setArchived([$argv[1]],true));',[assetId]);
        assert.deepEqual(archived.changed,[assetId]);assert.ok((await stat(path.join(brandDir,path.basename(saved.logo_url)))).isFile());
        // Independent processes patch the same row; no writer may replay an older full row.
        await Promise.all(Array.from({length:8},(_,i)=>new Promise((resolve,reject)=>{
            const child=spawn('php',['-r',preamble+'$db->upsert("settings",["id"=>$id,$argv[1]=>$argv[1]]);','fixture_concurrent_'+i],{cwd:root,env,stdio:'ignore'});
            child.once('error',reject);child.once('exit',code=>code===0?resolve():reject(Error('Concurrent patch failed: '+code)));
        })));
        const final=record();for(let i=0;i<8;i++)assert.equal(final['fixture_concurrent_'+i],'fixture_concurrent_'+i);
        assert.equal(final.fixture_keep,'preserve');
        console.log('PASS real admin appearance routes, CSRF and role gates, legacy/stale rejection, atomic settings patches, media provenance and safe archive.');
    }finally{
        if(proc&&proc.exitCode===null){const done=once(proc,'exit');proc.kill();await done;}
        for(const file of await files()){if(before.has(file))continue;const id=file.match(/-([0-9a-f]{32})\./)[1];php('$db->delete("media_files",$argv[1]);echo "true";',[id]);await unlink(path.join(brandDir,file));}
        php('$db->delete("settings",$id);$db->delete("settings",$argv[1]);$old=json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR);if($old!==null)$db->upsert("settings",$old);echo "true";',[legacyId,JSON.stringify(original)]);
        await rm(temp,{recursive:true,force:true});
    }
})().catch(e=>{console.error(e.message);process.exitCode=1});

const assert=require('node:assert/strict');
const {spawn,execFileSync}=require('node:child_process');
const {once}=require('node:events');
const net=require('node:net'),path=require('node:path');
if(!['localhost','127.0.0.1'].includes(process.env.BAPX_MYSQL_HOST)||!/(?:_test|_fixture)$/.test(process.env.BAPX_MYSQL_DB||'')||process.env.BAPX_TEST_MODE==='1')throw Error('Disposable local database required.');
const root=path.resolve(__dirname,'..'),slug='cms-boundary-'+process.pid,id=slug+'-media';
const url='/assets/images/media/uploads/2026/11/'+slug+'-12345678.png';
const env={...process.env,BAPX_TEST_MODE:'0',APP_URL:'http://127.0.0.1',BAPX_REMOTE_DB_URL:'http://127.0.0.1/remotedb'};
const preamble='require "app/bootstrap.php"; $db=new App\\Services\\DatabaseService(true); ';
function php(code,args=[],extra={}){return JSON.parse(execFileSync('php',['-r',preamble+code,...args],{cwd:root,env:{...env,...extra},encoding:'utf8'}));}
function execAsync(code,args=[]){
    return new Promise((resolve,reject)=>{
        const proc=spawn('php',['-r',preamble+code,...args],{cwd:root,env});let body='',err='';
        proc.stdout.on('data',v=>body+=v);proc.stderr.on('data',v=>err+=v);
        proc.once('error',reject);proc.once('close',code=>{if(code!==0)return reject(Error(err));try{resolve(JSON.parse(body));}catch(e){reject(e);}});
    });
}
(async()=>{
    let proc;
    const before=php('echo json_encode($db->find("cms_pages",$argv[1]));',[slug]);assert.equal(before,null);
    assert.equal(php('echo json_encode($db->find("media_files",$argv[1]));',[id]),null);
    try{
        // Force an independent client through the actual authenticated loopback bridge.
        const listener=net.createServer();await new Promise(r=>listener.listen(0,'127.0.0.1',r));const port=listener.address().port;await new Promise(r=>listener.close(r));
        proc=spawn('php',['-S','127.0.0.1:'+port,'index.php'],{cwd:root,env,stdio:'ignore'});
        const origin='http://127.0.0.1:'+port;
        for(let i=0;;i++){try{await fetch(origin+'/remotedb');break;}catch(e){if(i>=60)throw e;await new Promise(r=>setTimeout(r,50));}}
        const remote={BAPX_MYSQL_PORT:'1',BAPX_REMOTE_DB_URL:origin+'/remotedb'};
        const call=async body=>{
            const r=await fetch(origin+'/remotedb',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
            return {status:r.status,data:await r.json()};
        };
        assert.equal((await call({action:'collection_status',collection:'cms_pages'})).status,401);
        assert.equal(php('$r=new App\\Services\\DatabaseService();echo json_encode($r->collectionExists("cms_pages"));',[],remote),true);
        // A confirmed absence must be distinguishable from an unreadable structure.
        php('if($db->read("cms_pages")!==[])throw new RuntimeException("Expected empty test collection");$db->connection()->exec("DROP TABLE cms_pages");echo "true";');
        assert.equal(php('$r=new App\\Services\\DatabaseService();echo json_encode($r->collectionExists("cms_pages"));',[],remote),false);
        php('$db->connection()->exec("CREATE TABLE cms_pages (id VARCHAR(36) PRIMARY KEY)");echo "true";');
        assert.equal(php('$r=new App\\Services\\DatabaseService();try{$r->collectionExists("cms_pages");echo "false";}catch(Throwable){echo "true";}',[],remote),true);
        php('if($db->query("SELECT COUNT(*) AS n FROM cms_pages")[0]["n"]!=0)throw new RuntimeException("Expected empty fixture");$db->connection()->exec("DROP TABLE cms_pages");echo "true";');
        // Idempotent initialization on the disposable serving database only.
        assert.equal(php('$r=new App\\Services\\CmsPageService();$r->initialize();echo json_encode($r->ready());',[],remote),true);
        php('$db->upsert("media_files",["id"=>$argv[1],"source"=>"upload","url"=>$argv[2],"context"=>"shared"]);echo "true";',[id,url]);
        php('$r=new App\\Services\\CmsPageService();$r->create($argv[1],"Bridge page");echo "true";',[slug],remote);
        let row=php('echo json_encode($db->find("cms_pages",$argv[1]));',[slug]);
        php('$r=new App\\Services\\CmsPageService();$row=$r->editor($argv[1]);$r->save($argv[1],$row["revision"],"add:image_text",$row["state"]["draft"]);echo "true";',[slug],remote);
        row=php('echo json_encode($db->find("cms_pages",$argv[1]));',[slug]);
        const doc=row.draft;doc.sections[0].content={heading:'Image',body:'Text',image_url:url,image_alt:'Image'};
        const input=JSON.stringify(doc);
        // Two independent DB connections start conflicting operations together.
        const [saved,archived]=await Promise.all([
            execAsync('$s=new App\\Services\\CmsPageService($db);try{$r=$s->editor($argv[1]);$s->save($argv[1],$r["revision"],"save",json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo json_encode("saved");}catch(DomainException){echo json_encode("denied");}',[slug,input]),
            execAsync('$m=new App\\Services\\MediaService(store:$db);echo json_encode($m->setArchived([$argv[1]],true));',[id]),
        ]);
        row=php('echo json_encode($db->find("cms_pages",$argv[1]));',[slug]);
        const media=php('echo json_encode($db->find("media_files",$argv[1]));',[id]);
        if(saved==='saved'){assert.equal(archived.blocked,1);assert.ok(!media.archived_at);assert.equal(row.draft.sections[0].content.image_url,url);}
        else{assert.equal(saved,'denied');assert.deepEqual(archived.changed,[id]);assert.ok(media.archived_at);assert.equal(row.draft.sections[0].content.image_url,'');}
        // Remote operation returns a real summary, and uses server-side reference scan.
        const result=php('$r=new App\\Services\\MediaService();echo json_encode($r->setArchived([$argv[1]],true));',[id],remote);
        assert.equal(result.blocked,1);
        console.log('PASS loopback configured bridge readiness/setup/edit/archive and concurrent CMS/media serialization');
    }finally{
        if(proc&&proc.exitCode===null){const done=once(proc,'exit');proc.kill();await done;}
        php('$db->ensureSchemaCollection("cms_pages");$db->delete("cms_pages",$argv[1]);$db->delete("media_files",$argv[2]);echo "true";',[slug,id]);
    }
})().catch(e=>{console.error(e.message);process.exitCode=1});

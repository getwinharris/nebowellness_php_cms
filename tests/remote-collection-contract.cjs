/**
 * A deliberately small remoteDB protocol fixture, not a SQL emulator or
 * production database. It runs only on loopback and a disposable JSON file.
 * Unsupported operations fail, rather than silently returning empty success.
 */
const {test}=require('node:test');
const assert=require('node:assert/strict');
const {spawn}=require('node:child_process');
const {mkdtemp,rm,readFile,writeFile,rename}=require('node:fs/promises');
const http=require('node:http'),path=require('node:path'),os=require('node:os');
const root=path.resolve(__dirname,'..');
const token='remote-contract-fixture-only';
const schemaNames=new Set(['settings','users','cms_pages']);
const wireRow=row=>({id:row.id,_data:JSON.stringify(row)});
function php(code,args=[],env={}){
    return new Promise((resolve,reject)=>{
        const proc=spawn('php',['-r','require "app/bootstrap.php"; '+code,...args],{
            cwd:root,env:{...process.env,BAPX_TEST_MODE:'0',BAPX_DB_MODE:'remote',APP_URL:'http://fixture-client.test',
                BAPX_MYSQL_HOST:'127.0.0.1',BAPX_MYSQL_PORT:'1',BAPX_MYSQL_DB:'unused_fixture',
                BAPX_MYSQL_USER:'unused_fixture',BAPX_MYSQL_PASS:token,...env},stdio:['ignore','pipe','pipe']});
        let out='',err='';proc.stdout.on('data',v=>out+=v);proc.stderr.on('data',v=>err+=v);
        proc.once('error',reject);proc.once('close',status=>{
            if(status!==0)return reject(Error('PHP contract fixture failed: '+err.slice(0,700)));
            try{resolve(JSON.parse(out));}catch(error){reject(error);}
        });
    });
}
test('existing remoteDB client reads and patches canonical collections without a database server',async()=>{
    const dir=await mkdtemp(path.join(os.tmpdir(),'bapxcms-remote-contract-'));
    const file=path.join(dir,'collections.json');
    let server,mode='normal',pending=Promise.resolve();
    const requests=[];
    // Keep the fixture implementation in one serialized JSON transaction. The
    // application domain transition and revision algorithm remain PHP source.
    try{
        await writeFile(file,JSON.stringify({settings:{app_settings:{id:'app_settings',fixture_keep:'preserved',module_blog:'1'}},users:{fixture:{id:'fixture',role:'customer'}}}),{mode:0o600});
        server=http.createServer(async(req,res)=>{
            let raw='';
            for await(const chunk of req){raw+=chunk;if(raw.length>262144){res.writeHead(413);res.end();return;}}
            const respond=(code,value)=>{
                res.writeHead(code,{'Content-Type':'application/json','Cache-Control':'no-store'});
                res.end(JSON.stringify(value));
            };
            let body;
            try{body=JSON.parse(raw);}catch{respond(400,{error:'invalid JSON'});return;}
            if(req.method!=='POST'||req.url!=='/remotedb'||body.password!==token){respond(401,{error:'fixture authorization'});return;}
            requests.push({action:body.action??'query',query:body.query??null,collection:body.collection??null});
            if(mode==='unavailable'){respond(503,{error:'fixture unavailable'});return;}
            if(mode==='malformed'){respond(200,{success:true,data:'invalid'});return;}
            if(mode==='bad-row'){respond(200,{success:true,data:[{id:'fixture',_data:'not JSON'}]});return;}
            if(mode==='bad-object'){respond(200,{success:true,data:[{id:'fixture',_data:'[]'}]});return;}
            if(mode==='wrong-record'){respond(200,{success:true,data:[{id:'other',_data:'{}'}]});return;}
            const operation=async()=>{
                const state=JSON.parse(await readFile(file,'utf8'));
                const save=async()=>{const temp=file+'.tmp';await writeFile(temp,JSON.stringify(state),{mode:0o600});await rename(temp,file);};
                if(!body.action||body.action==='query'){
                    const match=/^SELECT \* FROM ([a-z_]+)( WHERE id = \?)?$/.exec(body.query??'');
                    if(!match||!schemaNames.has(match[1])||!state[match[1]])return respond(422,{error:'unsupported fixture query'});
                    const rows=Object.values(state[match[1]]);
                    return respond(200,{success:true,data:(match[2]?rows.filter(row=>row.id===body.params?.[0]):rows).map(wireRow)});
                }
                const col=body.collection;
                if(!schemaNames.has(col))return respond(422,{error:'unknown fixture collection'});
                if(body.action==='collection_status')return respond(200,{success:true,present:Object.hasOwn(state,col)});
                if(body.action==='init_schema_collections'&&col==='cms_pages'&&JSON.stringify(body.collections)==='["cms_pages"]'){
                    state[col]??={};await save();return respond(200,{success:true,collections:[col]});
                }
                if(!state[col])return respond(422,{error:'fixture collection missing'});
                // Execute the actual native page domain code for the fixture;
                // do not duplicate CMS validation rules in JavaScript.
                if(body.action==='cms_page_create'&&col==='cms_pages'){
                    if(state[col][body.slug])return respond(409,{error:'exists'});
                    const row=await php('echo json_encode(App\\Services\\CmsPageService::newRecord($argv[1],$argv[2]),JSON_THROW_ON_ERROR);',[body.slug,body.title]);
                    state[col][row.id]=row;await save();return respond(200,{success:true,record:row});
                }
                if(body.action==='cms_page_save'&&col==='cms_pages'){
                    const current=state[col][body.slug];
                    if(!current)return respond(409,{error:'not found'});
                    const result=await php('try{$row=json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR);if(!hash_equals(App\\Services\\ResourceService::revision($row),$argv[2]))throw new DomainException("stale");echo json_encode(["record"=>App\\Services\\CmsPageService::transition($row,$argv[3],json_decode($argv[4],true,flags:JSON_THROW_ON_ERROR))],JSON_THROW_ON_ERROR);}catch(DomainException|InvalidArgumentException){echo json_encode(["conflict"=>true]);}',[JSON.stringify(current),body.revision,body.page_action,JSON.stringify(body.document??null)]);
                    if(result.conflict)return respond(409,{error:'fixture conflict'});
                    state[col][body.slug]=result.record;await save();return respond(200,{success:true});
                }
                if(body.action==='upsert'){
                    const patch=body.record,old=state[col][patch?.id];
                    if(!patch||typeof patch.id!=='string')return respond(422,{error:'invalid record'});
                    if(body.expected_revision!==undefined){
                        const revision=old?await php('echo json_encode(App\\Services\\ResourceService::revision(json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR)));',[JSON.stringify(old)]):null;
                        if(revision!==body.expected_revision)return respond(409,{error:'stale'});
                    }
                    state[col][patch.id]={...old,...patch};await save();return respond(200,{success:true,record:state[col][patch.id]});
                }
                return respond(422,{error:'unsupported fixture action'});
            };
            const task=pending.then(operation);pending=task.catch(()=>{});
            try{await task;}catch{if(!res.headersSent)respond(500,{error:'fixture failed'});}
        });
        await new Promise(r=>server.listen(0,'127.0.0.1',r));
        const env={BAPX_REMOTE_DB_URL:'http://127.0.0.1:'+server.address().port+'/remotedb'};
        const run=(code,args=[])=>php(code,args,env);
        const result=await run([
            '$db=new App\\Services\\DatabaseService();',
            '$before=$db->find("settings","app_settings");$rev=App\\Services\\ResourceService::revision($before);',
            '$db->upsertIfRevision("settings",["id"=>"app_settings","module_blog"=>"0"],$rev);',
            '$after=$db->find("settings","app_settings");',
            '$conflict=false;try{$db->upsertIfRevision("settings",["id"=>"app_settings","module_blog"=>"1"],$rev);}catch(DomainException){$conflict=true;}',
            '$users=$db->read("users");',
            'echo json_encode(compact("after","conflict","users"),JSON_THROW_ON_ERROR);'
        ].join(' '));
        assert.equal(result.after.fixture_keep,'preserved');assert.equal(result.after.module_blog,'0');assert.equal(result.conflict,true);assert.equal(result.users[0].id,'fixture');
        const page=await run([
            '$s=new App\\Services\\CmsPageService();$before=$s->ready();$s->initialize();$after=$s->ready();',
            '$s->create("fixture-page","Fixture page");$duplicate=false;try{$s->create("fixture-page","Duplicate");}catch(DomainException){$duplicate=true;}',
            '$r=$s->editor("fixture-page");$initial=$r["revision"];$s->save("fixture-page",$initial,"add:hero",$r["state"]["draft"]);',
            '$r=$s->editor("fixture-page");$d=$r["state"]["draft"];$d["sections"][0]["content"]=["heading"=>"Public heading","body"=>"Private draft until publish"];',
            '$s->save("fixture-page",$r["revision"],"save",$d);$draft=$s->published("fixture-page");',
            '$stale=false;try{$s->save("fixture-page",$initial,"publish",null);}catch(DomainException){$stale=true;}',
            '$r=$s->editor("fixture-page");$s->save("fixture-page",$r["revision"],"publish",null);$published=$s->published("fixture-page");',
            '$r=$s->editor("fixture-page");$s->save("fixture-page",$r["revision"],"unpublish",null);$hidden=$s->published("fixture-page");',
            '$r=$s->editor("fixture-page");$s->save("fixture-page",$r["revision"],"restore",null);$restored=$s->published("fixture-page");',
            'echo json_encode(compact("before","after","duplicate","draft","stale","published","hidden","restored"),JSON_THROW_ON_ERROR);'
        ].join(' '));
        assert.equal(page.before,false);assert.equal(page.after,true);assert.equal(page.duplicate,true);assert.equal(page.draft,null);assert.equal(page.stale,true);assert.equal(page.hidden,null);assert.deepEqual(page.restored,page.published);
        for(const failure of ['unavailable','malformed','bad-row','bad-object']){
            mode=failure;
            const denied=await run('$db=new App\\Services\\DatabaseService();try{$db->read("users");echo "false";}catch(Throwable){echo "true";}');
            assert.equal(denied,true);
        }
        mode='wrong-record';
        assert.equal(await run('$db=new App\\Services\\DatabaseService();try{$db->find("users","fixture");echo "false";}catch(Throwable){echo "true";}'),true);
        mode='normal';
        const self=await php('$_SERVER["HTTP_HOST"]="127.0.0.1";try{(new App\\Services\\DatabaseService())->read("users");echo "false";}catch(RuntimeException){echo "true";}',[],env);
        assert.equal(self,true);
        assert.ok(requests.some(r=>r.action==='cms_page_save'));
        assert.ok(requests.some(r=>r.action==='collection_status'));
        assert.ok(requests.some(r=>r.action==='upsert'));
        console.log('PASS remoteDB wire contract, JSON collections, stale writes, native page lifecycle and transport failures; no SQL engine exercised');
    }finally{
        if(server)await new Promise(r=>server.close(r));
        await rm(dir,{recursive:true,force:true});
    }
});

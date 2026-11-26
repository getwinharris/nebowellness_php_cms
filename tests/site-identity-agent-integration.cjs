const assert=require('node:assert/strict');
const {spawn,execFileSync}=require('node:child_process');
const {randomBytes}=require('node:crypto');
const {mkdtemp,rm}=require('node:fs/promises');
const http=require('node:http'),os=require('node:os'),path=require('node:path');
if(!['localhost','127.0.0.1'].includes(process.env.BAPX_MYSQL_HOST)||!/(?:_test|_fixture)$/.test(process.env.BAPX_MYSQL_DB||'')||process.env.BAPX_TEST_MODE==='1')throw Error('Disposable local database required.');
const {root,alpha,beta,state}=require('./fixtures/site-profile-data.cjs');
const env={...process.env,BAPX_TEST_MODE:'0',APP_URL:'http://127.0.0.1',BAPX_REMOTE_DB_URL:'http://127.0.0.1/remotedb'};
const preamble='require "app/bootstrap.php"; $db=new App\\Services\\DatabaseService(true); $id=App\\Services\\SettingsService::SETTINGS_ID; ';
const php=(code,args=[])=>JSON.parse(execFileSync('php',['-r',preamble+code,...args],{cwd:root,env,encoding:'utf8'}));
const read=()=>php('echo json_encode($db->find("settings",$id),JSON_THROW_ON_ERROR);');
const write=value=>php('$db->upsert("settings",["id"=>$id]+json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR));echo "true";',[JSON.stringify(value)]);
(async()=>{
    const old=read(),dir=await mkdtemp(path.join(os.tmpdir(),'identity-agent-'));
    let provider;const requests=[];
    try{
        provider=http.createServer(async(req,res)=>{
            try{
                let raw='';for await(const chunk of req)raw+=chunk;
                const payload=JSON.parse(raw),prompt=payload.messages[0].content;
                assert.ok(payload.tools.some(t=>t.function.name==='published_site_identity'));
                requests.push({prompt,tools:payload.tools.map(t=>t.function.name)});
                // Match the fixture question, not the shared instruction mentioning a lookup.
                if(/question: What is (?:the public email address|our business email) without a lookup\?/i.test(prompt)){
                    res.end(JSON.stringify({choices:[{finish_reason:'stop',message:{content:'wrong@example.test'}}]}));return;
                }
                if(!payload.messages.some(m=>m.role==='tool')){
                    res.end(JSON.stringify({choices:[{finish_reason:'tool_calls',message:{tool_calls:[{id:'identity_call',type:'function',function:{name:'published_site_identity',arguments:'{}'}}]}}]}));return;
                }
                const tool=payload.messages.at(-1);
                assert.equal(tool.tool_call_id,'identity_call');
                assert.equal(payload.messages.at(-2).tool_calls[0].id,'identity_call');
                const value=JSON.parse(tool.content);
                requests.at(-1).observed=value;
                assert.ok(!('admin_name' in value)&&!('draft' in value)&&!('previous' in value));
                const answer=value.error?'Published contact information is unavailable.':prompt.includes('incorrect value fixture')?'Email: wrong@example.test.':'Public email: '+value.public_email+'.';
                res.end(JSON.stringify({choices:[{finish_reason:'stop',message:{content:answer}}]}));
            }catch(e){res.statusCode=500;res.end(JSON.stringify({error:'fixture assertion failed'}));console.error(e.message);}
        });
        await new Promise(r=>provider.listen(0,'127.0.0.1',r));
        const agentEnv={...env,BAPX_AGENT_DB_FIXTURE:'1',AI_API_KEY:'synthetic-identity-fixture',AGENT_MODEL:'identity-fixture',BAPX_AI_ENDPOINT:'http://127.0.0.1:'+provider.address().port};
        async function ask(surface,message){
            const proc=spawn('php',[path.join(root,'tests/fixtures/agent-session.php'),dir,randomBytes(16).toString('hex')],{cwd:root,env:agentEnv});
            let body='',err='';proc.stdout.on('data',v=>body+=v);proc.stderr.on('data',v=>err+=v);
            const done=new Promise((resolve,reject)=>{proc.on('error',reject);proc.on('close',code=>code===0?resolve():reject(Error('Fixture exited '+code+': '+err)));});
            proc.stdin.end(JSON.stringify({surface,fields:{_csrf:'fixture-token',message},...(surface==='admin'?{identity:'owner-fixture'}:{})}));
            await done;return JSON.parse(body);
        }
        php('$db->delete("settings",$id);echo "true";');
        write({site_profile:state(alpha,beta,{...beta,name:'Previous private identity'},true),clinic_phones:['+12025550101'],module_blog:'0',module_campaigns:'0',unrelated_fixture:'private setting'});
        const before=read();
        for(const surface of ['support','admin']){
            const key=surface==='support'?'reply':'answer';
            const result=await ask(surface,'What is the public email address?');
            assert.equal(result.status,200,JSON.stringify({surface,result,observed:requests.at(-1)?.observed,providerRequests:requests.length}));
            assert.equal(result.body.response_mode,'model_with_retrieval');
            assert.ok(result.body[key].includes(alpha.public_email));
            assert.deepEqual(result.body.sources,['/contact']);
            assert.ok(result.body.a2ui.createSurface.components.some(c=>c.text==='Email: '+alpha.public_email));
            assert.equal(requests.at(-1).observed.name,alpha.name);
            for(const val of [requests.at(-1).observed,result.body]){
                assert.doesNotMatch(JSON.stringify(val),/Cedar Services|cedar@example\.test|Previous private identity|private setting|Orchid Studio Admin/);
            }
            const guessed=await ask(surface,'What is the public email address without a lookup?');
            assert.equal(guessed.status,surface==='support'?503:502);assert.ok(!guessed.body[key]);
            const ordinary=await ask(surface,'What is our business email?');
            assert.equal(ordinary.status,200,JSON.stringify(ordinary));
            assert.ok(ordinary.body[key].includes(alpha.public_email));
            const ordinaryGuess=await ask(surface,'What is our business email without a lookup?');
            assert.equal(ordinaryGuess.status,surface==='support'?503:502);assert.ok(!ordinaryGuess.body[key]);
            const ordinaryWrong=await ask(surface,'What is our business email? incorrect value fixture');
            assert.equal(ordinaryWrong.status,surface==='support'?503:502);assert.ok(!ordinaryWrong.body[key]);
            const wrong=await ask(surface,'What is the public email address? incorrect value fixture');
            assert.equal(wrong.status,surface==='support'?503:502);assert.ok(!wrong.body[key]);
        }
        assert.deepEqual(read(),before);
        write({site_profile:state(beta,alpha)});
        for(const surface of ['support','admin']){
            const key=surface==='support'?'reply':'answer';
            const result=await ask(surface,'What is the public email address?');
            assert.equal(result.status,200,JSON.stringify({surface,result,observed:requests.at(-1)?.observed,providerRequests:requests.length}));
            assert.ok(result.body[key].includes(beta.public_email));
            assert.doesNotMatch(JSON.stringify(result.body.a2ui),/Orchid Studio|orchid@example\.test/);
        }
        write({site_profile:{version:99,published:alpha}});
        for(const surface of ['support','admin']){
            const result=await ask(surface,'What is the public email address?');
            assert.equal(result.status,surface==='support'?503:502,JSON.stringify(result));
            assert.equal(requests.at(-1).observed.code,'ability_unavailable');
            assert.ok(!result.body.a2ui);
        }
        console.log('PASS both native agent routes with loopback provider and MariaDB: fresh identity evidence, no draft leaks, wrong/direct replies rejected, changed publication and unavailable state.');
    }finally{
        if(provider)await new Promise(r=>provider.close(r));
        php('$db->delete("settings",$id);$old=json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR);if($old!==null)$db->upsert("settings",$old);echo "true";',[JSON.stringify(old)]);
        await rm(dir,{recursive:true,force:true});
    }
})().catch(e=>{console.error(e.message);process.exitCode=1});

const assert=require('node:assert/strict');
const {spawn,execFileSync}=require('node:child_process');
const {once}=require('node:events');
const {mkdtemp,rm}=require('node:fs/promises');
const net=require('node:net'),path=require('node:path'),os=require('node:os');
if(!['localhost','127.0.0.1'].includes(process.env.BAPX_MYSQL_HOST)||!/(?:_test|_fixture)$/.test(process.env.BAPX_MYSQL_DB||'')||process.env.BAPX_TEST_MODE==='1')throw Error('Use a disposable local test database.');
const root=path.resolve(__dirname,'..'),slug='cms-fixture-'+process.pid;
const env={...process.env,BAPX_TEST_MODE:'0',APP_URL:'http://127.0.0.1',BAPX_REMOTE_DB_URL:'http://127.0.0.1/remotedb'};
const php=(code,args=[])=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; $db=new App\\Services\\DatabaseService(true); '+code,...args],{cwd:root,env,encoding:'utf8'}));
const dynamicIds=['-team-a','-team-b','-team-hidden','-program-a','-program-b','-program-draft'].map(s=>slug+s);
const mediaId=slug+'-media',mediaUrl='/assets/images/media/uploads/2026/11/'+slug+'-12345678.png';
const record=()=>php('echo json_encode($db->find("cms_pages",$argv[1]),JSON_THROW_ON_ERROR);',[slug]);
const AppDoc=row=>row.draft??row.published;
const revision=()=>php('echo json_encode(App\\Services\\ResourceService::revision($db->find("cms_pages",$argv[1])));',[slug]);
(async()=>{
    const dir=await mkdtemp(path.join(os.tmpdir(),'cms-page-route-'));let proc;
    const originalSettings=php('echo json_encode($db->find("settings",App\\Services\\SettingsService::SETTINGS_ID));');
    assert.equal(record(),null);
    assert.equal(php('echo json_encode($db->find("media_files",$argv[1]));',[mediaId]),null);
    // Disposable CI only: exercise the pre-initialization state. Never drop a
    // nonempty collection, even in this test database.
    php('$rows=$db->read("cms_pages");if($rows!==[])throw new RuntimeException("Expected empty test collection");$db->connection()->exec("DROP TABLE cms_pages");echo "true";');
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
        assert.equal((await live()).status,404);
        const sitemap=await get('/sitemap.xml');assert.equal(sitemap.status,200);assert.match(await sitemap.text(),/<loc>[^<]+\/contact<\/loc>/);
        const setup=await (await get('/admin/pages','admin')).text();assert.ok(setup.includes('Initialize page storage'));assert.ok(!setup.includes('Create draft'));
        assert.equal((await post('initialize','',null,{role:'customer'})).location,'/');
        assert.equal((await post('initialize','',null,{csrf:'bad'})).flash.type,'error');
        assert.equal((await post('initialize','',null,{confirm:false})).flash.type,'error');
        assert.equal(php('echo json_encode($db->collectionExists("cms_pages"));'),false);
        php('$db->upsert("media_files",["id"=>$argv[1],"source"=>"upload","url"=>$argv[2],"context"=>"shared","used_in"=>[]]);$db->upsert("categories",["id"=>$argv[1],"image_url"=>$argv[2]]);echo "true";',[mediaId,mediaUrl]);
        assert.equal(php('$media=new App\\Services\\MediaService(store:$db);$refs=(new ReflectionMethod($media,"references"))->invoke($media);echo json_encode(isset($refs[$argv[1]]["categories"][$argv[2]]));',[mediaUrl,mediaId]),true);
        const archive=()=>php('echo json_encode((new App\\Services\\MediaService(store:$db))->setArchived([$argv[1]],true));',[mediaId]);
        const restoreMedia=()=>php('echo json_encode((new App\\Services\\MediaService(store:$db))->setArchived([$argv[1]],false));',[mediaId]);
        assert.equal(archive().blocked,1);
        php('$db->delete("categories",$argv[1]);echo "true";',[mediaId]);
        assert.equal((await post('initialize')).flash.type,'success');
        assert.equal(php('echo json_encode($db->collectionExists("cms_pages"));'),true);
        assert.equal((await post('initialize')).flash.type,'success');
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
        const publishedRevision=revision();assert.equal((await post('initialize')).flash.type,'success');assert.equal(revision(),publishedRevision);
        // An archive completed first must prevent a stale editor from creating a new reference.
        assert.deepEqual(archive().changed,[mediaId]);
        d=record().published;
        assert.equal((await post('add:image_text',revision(),d)).flash.type,'success');
        d=record().draft;const image=d.sections[2];
        image.content={heading:'Image fixture',body:'Managed image',image_url:mediaUrl,image_alt:'Fixture image'};
        const staleImageRevision=revision();
        assert.equal((await post('save',staleImageRevision,d)).flash.type,'error');assert.equal(revision(),staleImageRevision);
        assert.deepEqual(restoreMedia().changed,[mediaId]);
        assert.equal((await post('save',revision(),d)).flash.type,'success');
        assert.equal(archive().blocked,1);
        assert.equal((await post('publish',revision())).flash.type,'success');
        assert.equal(archive().blocked,1);
        // Removing the current image does not remove its rollback reference.
        d=record().published;
        assert.equal((await post('remove:'+image.id,revision(),d)).flash.type,'success');
        assert.equal((await post('publish',revision())).flash.type,'success');
        assert.equal(archive().blocked,1);
        assert.equal((await post('publish',revision())).flash.type,'error');
        let p=await live();assert.equal(p.status,200);let html=await p.text();assert.ok(html.includes('First public version'));assert.ok(!html.includes('Secret hidden section'));
        assert.ok((await (await get('/sitemap.xml')).text()).includes('/pages/'+slug));
        d=record().published;d.sections[0].content.body='Second private version';
        assert.equal((await post('save',revision(),d)).flash.type,'success');
        html=await (await live()).text();assert.ok(html.includes('First public version'));assert.ok(!html.includes('Second private version'));
        assert.equal((await post('publish',revision())).flash.type,'success');assert.ok((await (await live()).text()).includes('Second private version'));
        assert.equal((await post('restore',revision())).flash.type,'success');assert.ok((await (await live()).text()).includes('First public version'));
        assert.equal((await post('unpublish',revision())).flash.type,'success');assert.equal((await live()).status,404);assert.ok(!(await (await get('/sitemap.xml')).text()).includes('/pages/'+slug));
        assert.equal((await post('restore',revision())).flash.type,'success');assert.equal((await live()).status,200);
        // Live section types use the same published abilities as Support/MCP.
        const fixture={ids:dynamicIds,programmeA:'Public programme Alpha',programmeB:'Public programme Beta'};
        php('$in=json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR);$ids=$in["ids"];foreach([["First visible practitioner","visible"],["Second visible practitioner","visible"],["Hidden practitioner fixture","hidden"]] as $i=>$item)$db->upsert("consultants",["id"=>$ids[$i],"slug"=>$ids[$i],"name"=>$item[0],"status"=>$item[1],"display_order"=>$i,"credentials"=>"Fixture qualification","private_note"=>"private team note"]);foreach([[$in["programmeA"],"published"],[$in["programmeB"],"published"],["Private programme fixture","draft"]] as $i=>$item)$db->upsert("campaign_pages",["id"=>$ids[$i+3],"slug"=>$ids[$i+3],"title"=>$item[0],"status"=>$item[1],"summary"=>"Published description","homepage_program"=>true,"program_area"=>"fixture","program_area_label"=>"Fixture","program_duration"=>"6 weeks","program_order"=>$i,"private_note"=>"private campaign note"]);$fee=App\\Services\\ConsultationFeeService::class;$db->upsert("settings",["id"=>App\\Services\\SettingsService::SETTINGS_ID,"module_campaigns"=>"1","consultation_fees"=>[$fee::normalize(["title"=>"Published fee fixture","amount"=>"123.45","currency"=>"INR","status"=>"published","min_minutes"=>"30","max_minutes"=>"30"],"fee-public-fixture"),$fee::normalize(["title"=>"Private fee fixture","amount"=>"999.00","currency"=>"INR","status"=>"draft","min_minutes"=>"20","max_minutes"=>"20"],"fee-private-fixture")],"consultation_inclusions"=>["Shared published inclusion"]]);echo "true";',[JSON.stringify(fixture)]);
        for(const [type,heading] of [['team','Live practitioners'],['programmes','Live programmes'],['fee_table','Live fees']]){
            d=AppDoc(record());
            assert.equal((await post('add:'+type,revision(),d)).flash.type,'success');
            d=record().draft;d.sections.at(-1).content={heading,body:'Fixture introduction',display_limit:'1'};
            assert.equal((await post('save',revision(),d)).flash.type,'success');
        }
        const ownerPreview=await (await get('/admin/pages?slug='+slug,'admin')).text();
        assert.ok(ownerPreview.includes('Published fee fixture'));
        assert.ok(!(await (await live()).text()).includes('Published fee fixture'));
        assert.equal((await post('publish',revision())).flash.type,'success');
        const dynamicRevision=revision();
        let dynamicHtml=await (await live()).text();
        for(const value of ['First visible practitioner','Public programme Alpha','Published fee fixture','₹123.45','30 minutes','Shared published inclusion','Showing 1 of 2 published entries','An enquiry does not confirm an appointment']){
            assert.ok(dynamicHtml.includes(value),value);
        }
        for(const value of ['Second visible practitioner','Public programme Beta','Hidden practitioner fixture','Private programme fixture','Private fee fixture','private team note','private campaign note']){
            assert.ok(!dynamicHtml.includes(value),value);
        }
        // Published source changes appear without republishing the page document.
        php('$db->upsert("consultants",["id"=>$argv[1],"name"=>"Updated visible practitioner"]);$db->upsert("campaign_pages",["id"=>$argv[2],"status"=>"draft"]);$db->upsert("settings",["id"=>App\\Services\\SettingsService::SETTINGS_ID,"module_campaigns"=>"0"]);echo "true";',[dynamicIds[0],dynamicIds[3]]);
        dynamicHtml=await (await live()).text();assert.ok(dynamicHtml.includes('Updated visible practitioner'));assert.ok(!dynamicHtml.includes('Live programmes'));assert.equal(revision(),dynamicRevision);
        const disabledPreview=await (await get('/admin/pages?slug='+slug,'admin')).text();assert.ok(disabledPreview.includes('This service module is disabled'));
        php('$db->upsert("settings",["id"=>App\\Services\\SettingsService::SETTINGS_ID,"module_campaigns"=>"1","consultation_fees"=>[]]);$db->upsert("consultants",["id"=>$argv[1],"credentials"=>["invalid"]]);echo "true";',[dynamicIds[0]]);
        dynamicHtml=await (await live()).text();
        assert.ok(dynamicHtml.includes('Public programme Beta'));assert.ok(!dynamicHtml.includes('Public programme Alpha'));
        assert.ok(dynamicHtml.includes('No matching published entries are currently available.'));
        assert.ok(dynamicHtml.includes('Current published information is temporarily unavailable.'));
        assert.ok(!dynamicHtml.includes('Updated visible practitioner'));assert.equal(revision(),dynamicRevision);
        console.log('PASS live native programme/team/fee sections share published records, limit results, hide drafts and disabled modules, distinguish empty/unavailable, and update without republishing');
        console.log('PASS schema readiness, owner initialization, existing reference scan, archived-first denial and draft/publication/rollback media protection, real CMS owner routes, duplicate creation, private drafts, role/CSRF, revision guards, hidden sections, publication, sitemap, unpublish and restore');
    }finally{
        if(proc&&proc.exitCode===null){const done=once(proc,'exit');proc.kill();await done;}
        php('$db->ensureSchemaCollection("cms_pages");$db->delete("cms_pages",$argv[1]);$db->delete("categories",$argv[2]);$db->delete("media_files",$argv[2]);$ids=json_decode($argv[3],true,flags:JSON_THROW_ON_ERROR);foreach(array_slice($ids,0,3) as $id)$db->delete("consultants",$id);foreach(array_slice($ids,3) as $id)$db->delete("campaign_pages",$id);$old=json_decode($argv[4],true,flags:JSON_THROW_ON_ERROR);$db->delete("settings",App\\Services\\SettingsService::SETTINGS_ID);if($old!==null)$db->upsert("settings",$old);echo "true";',[slug,mediaId,JSON.stringify(dynamicIds),JSON.stringify(originalSettings)]);await rm(dir,{recursive:true,force:true});
    }
})().catch(e=>{console.error(e);process.exitCode=1});

const {test}=require('node:test');
const assert=require('node:assert/strict');
const {execFileSync}=require('node:child_process');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
const php=code=>JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1'},encoding:'utf8'}));
test('native page lifecycle preserves stable identity, saved publication and section order',()=>{
    const r=php([
        '$c=App\\Services\\CmsPageService::class;',
        '$row=$c::newRecord("fixture-page","Fixture");',
        '$row=$c::transition($row,"add:hero",$row["draft"]);',
        '$d=$row["draft"];$d["sections"][0]["content"]=["heading"=>"Hero","body"=>"Public first"];',
        '$row=$c::transition($row,"add:faq",$d);',
        '$d=$row["draft"];$d["sections"][1]["content"]=["heading"=>"Question","body"=>"**Answer**"];',
        '$first=$d["sections"][0]["id"];$second=$d["sections"][1]["id"];',
        '$row=$c::transition($row,"up:".$second,$d);',
        '$row=$c::transition($row,"duplicate:".$first,$row["draft"]);',
        '$duplicate=$row["draft"]["sections"][2]["id"];',
        '$row=$c::transition($row,"remove:".$duplicate,$row["draft"]);',
        '$row=$c::transition($row,"publish");$published=$row;',
        '$d=$c::editDocument($row);$d["sections"][0]["visible"]=false;$d["sections"][1]["content"]["body"]="Private second";',
        '$row=$c::transition($row,"save",$d);$before=$c::publishedDocument($row);',
        '$row=$c::transition($row,"publish");$after=$c::publishedDocument($row);',
        '$row=$c::transition($row,"restore");$restored=$c::publishedDocument($row);',
        '$row=$c::transition($row,"unpublish");$hidden=$c::publishedDocument($row);',
        '$row=$c::transition($row,"restore");',
        'echo json_encode(compact("first","second","duplicate","before","after","restored","hidden","row"),JSON_THROW_ON_ERROR);'
    ].join(' '));
    assert.deepEqual(r.before.sections.map(s=>s.id),[r.second,r.first]);
    assert.notEqual(r.duplicate,r.first); assert.notEqual(r.duplicate,r.second);
    assert.ok(!JSON.stringify(r.before).includes('Private second'));
    assert.equal(r.after.sections[0].visible,false);
    assert.equal(r.after.sections[1].content.body,'Private second');
    assert.deepEqual(r.restored,r.before); assert.equal(r.hidden,null);
    assert.deepEqual(r.row.published,r.before);
});
test('section contract rejects executable fields, invalid paths, unsupported types and forged identities',()=>{
    const r=php([
        '$c=App\\Services\\CmsPageService::class;$s=App\\Services\\CmsSectionRegistry::class;$bad=[];',
        '$hero=$s::fresh("hero");$hero["content"]=["heading"=>"Safe","body"=>"Text"];',
        '$cta=$s::fresh("cta");$cta["content"]=["heading"=>"Safe","body"=>"Text","link_label"=>"Go","link_url"=>"javascript:alert(1)"];',
        '$image=$s::fresh("image_text");$image["content"]=["heading"=>"Safe","body"=>"Text","image_url"=>"/assets/images/../private.png","image_alt"=>"Alt"];',
        'foreach([array_replace($hero,["type"=>"script"]),array_replace($hero,["content"=>["heading"=>"<script>","body"=>"x"]]),$cta,$image,$hero+["script"=>"x"]] as $candidate){try{$s::validate($candidate);$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}}',
        '$row=$c::newRecord("fixture","Title");',
        'try{$c::transition($row,"publish");$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}',
        '$doc=$row["draft"];$doc["sections"]=[$hero];',
        'try{$c::transition($row,"save",$doc);$bad[]=false;}catch(DomainException){$bad[]=true;}',
        'try{$s::list([$hero,$hero]);$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}',
        'echo json_encode(["bad"=>$bad,"links"=>array_map([$s,"linkPath"],["/contact","/pages/example","/pages","//evil.example","https://user@evil.example","javascript:alert(1)"])]);'
    ].join(' '));
    assert.ok(r.bad.every(Boolean));assert.equal(r.bad.length,8);
    assert.deepEqual(r.links,[true,true,false,false,false,false]);
});
test('preview and public template render visible typed content without leaking hidden sections',()=>{
    const r=php([
        '$s=App\\Services\\CmsSectionRegistry::class;$c=App\\Services\\CmsPageService::class;',
        '$a=$s::fresh("rich_text");$a["content"]=["heading"=>"Visible","body"=>"**Bold** [unsafe](javascript:alert(1))"];',
        '$b=$s::fresh("hero");$b["visible"]=false;$b["content"]=["heading"=>"Hidden fixture secret","body"=>"Private"];',
        '$document=$c::document(["title"=>"Fixture & title","description"=>"Description","sections"=>[$a,$b]],true);',
        '$preview=true;ob_start();require app_path("views/partials/cms-page-document.php");$draft=ob_get_clean();',
        '$preview=false;ob_start();require app_path("views/partials/cms-page-document.php");$live=ob_get_clean();',
        'echo json_encode(compact("draft","live"),JSON_THROW_ON_ERROR);'
    ].join(' '));
    for(const html of [r.draft,r.live]){
        assert.match(html,/Fixture &amp; title/);assert.match(html,/<strong>Bold<\/strong>/);
        assert.ok(!html.includes('Hidden fixture secret'));assert.ok(!html.includes('href="javascript:'));
    }
    assert.match(r.live,/<h1 class="cms-document-title">/);assert.match(r.draft,/<h2 class="cms-document-title">/);
});

test('live section schema bounds counts and projections reject extra/private or malformed data',()=>{
    const result=php([
        '$s=App\\Services\\CmsSectionRegistry::class;$d=App\\Services\\CmsDynamicSectionService::class;',
        '$db=new App\\Services\\DatabaseService();$r=new App\\Services\\NativeAbilityRegistry($db,"support",["campaigns"=>true]);$off=new App\\Services\\NativeAbilityRegistry($db,"support",["campaigns"=>false]);',
        '$section=$s::fresh("programmes");$section["content"]["heading"]="Live programmes";$good=$s::validate($section,true);$bad=[];',
        'foreach(["0","13","1.0","01","999"] as $limit){$candidate=$section;$candidate["content"]["display_limit"]=$limit;try{$s::validate($candidate);$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}}',
        '$row=["title"=>"Fixture","summary"=>"Text","duration"=>"8 weeks","url"=>"/campaigns/fixture"];$value=["programmes"=>[$row,$row]];',
        '$ready=$d::project("programmes",$value,1,$r);$empty=$d::project("programmes",["programmes"=>[]],1,$r);$disabled=$d::project("programmes",$value,1,$off);',
        '$invalid=$d::project("programmes",["programmes"=>[$row+["private"=>"not public"]]],1,$r);$unsafe=$d::project("programmes",["programmes"=>[array_replace($row,["url"=>"javascript:alert(1)"])]],1,$r);',
        'echo json_encode(compact("good","bad","ready","empty","disabled","invalid","unsafe"),JSON_THROW_ON_ERROR);'
    ].join(' '));
    assert.equal(result.good.content.display_limit,'6');assert.ok(result.bad.every(Boolean));
    assert.equal(result.ready.state,'ready');assert.equal(result.ready.rows.length,1);assert.equal(result.ready.total,2);
    for(const [key,expected] of [['empty','empty'],['disabled','disabled'],['invalid','unavailable'],['unsafe','unavailable']]){
        assert.equal(result[key].state,expected);assert.deepEqual(result[key].rows,[]);
    }
});

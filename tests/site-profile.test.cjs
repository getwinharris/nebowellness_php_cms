const assert=require('node:assert/strict');
const {test}=require('node:test');
const {execFileSync}=require('node:child_process');
const path=require('node:path');
const {root,env,alpha,beta,state,render}=require('./fixtures/site-profile-data.cjs');
function evaluate(code,input={}){
    return JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; $in=json_decode(stream_get_contents(STDIN),true,flags:JSON_THROW_ON_ERROR); '+code],{cwd:root,env,input:JSON.stringify(input),encoding:'utf8'}));
}

test('profile lifecycle keeps draft private and restores prior publication',()=>{
    const result=evaluate(String.raw`
    $c=App\Services\SiteProfileService::class;
    $r=['id'=>'app_settings','fixture_keep'=>'unchanged'];
    $draft=$c::transition($r,'save_draft',$in['alpha']);
    $draftView=$c::resolve($r+['site_profile'=>$draft]);
    $first=$c::transition($r+['site_profile'=>$draft],'publish');
    $next=$c::transition($r+['site_profile'=>$first],'save_draft',$in['beta']);
    $nextView=$c::resolve($r+['site_profile'=>$next]);
    $second=$c::transition($r+['site_profile'=>$next],'publish');
    $restored=$c::transition($r+['site_profile'=>$second],'restore');
    $initial=$c::transition($r+['site_profile'=>$first],'restore');
    echo json_encode(['before'=>$draftView,'next'=>$nextView,'second'=>$c::resolve($r+['site_profile'=>$second]),'restored'=>$c::resolve($r+['site_profile'=>$restored]),'initial'=>$c::resolve($r+['site_profile'=>$initial])]);
    `,{alpha,beta});
    assert.equal(result.before.mode,'unconfigured');
    assert.equal(result.next.name,alpha.name);
    assert.ok(!JSON.stringify(result.next).includes(beta.name));
    assert.equal(result.second.name,beta.name);
    assert.equal(result.second.address,'');
    assert.equal(result.restored.name,alpha.name);
    assert.equal(result.initial.mode,'unconfigured');
    assert.equal(result.second.logo_url,'');
    assert.equal(result.second.favicon_url,'');
});
test('strict fields and allowlisted URLs reject malformed or executable input',()=>{
    const output=evaluate(String.raw`
    $c=App\Services\SiteProfileService::class;
    $bad=[];
    foreach ([
      array_replace($in,['name'=>'<script>']),
      array_replace($in,['public_email'=>'bad']),
      array_replace($in,['instagram_url'=>'https://instagram.com@bad.example.test/profile']),
      array_replace($in,['organization_type'=>'Script']),
      array_replace($in,['unknown'=>'extra']),
      array_replace($in,['short_name'=>str_repeat('x',25)]),
      array_replace($in,['country'=>'IND']),
    ] as $candidate) {
      try{$c::validate($candidate);$bad[]=false;}catch(InvalidArgumentException){$bad[]=true;}
    }
    try{$c::transition([],'publish');$bad[]=false;}catch(DomainException){$bad[]=true;}
    $unavailable=$c::resolve(['site_profile'=>['version'=>900,'published'=>$in]]);
    echo json_encode(['bad'=>$bad,'unavailable'=>$unavailable]);
    `,alpha);
    assert.ok(output.bad.every(Boolean));
    assert.equal(output.unavailable.mode,'unavailable');
    assert.ok(!JSON.stringify(output.unavailable).includes(alpha.name));
});
test('two independent synthetic identities render actual templates and public projections',()=>{
    for(const [p,other] of [[alpha,beta],[beta,alpha]]){
        const settings={id:'app_settings',site_profile:state(p,other),palette_primary:'#123456'};
        const contact=render('/contact',settings);
        const admin=render('/admin/agent',settings);
        const md=render('/',settings);
        const manifest=JSON.parse(render('/manifest.json',settings));
        const adminManifest=JSON.parse(render('/admin/manifest.json',settings));
        const api=JSON.parse(render('/openapi.json',settings));
        assert.equal(api.info.title,p.name+' public discovery');
        assert.ok(contact.includes('aria-label="'+p.name+' home"'));
        assert.ok(contact.includes('mailto:'+p.public_email));
        assert.ok(contact.includes('content="'+p.name+'"'));
        assert.ok(admin.includes(p.admin_name));
        assert.ok(md.startsWith('# '+p.name));
        assert.equal(manifest.name,p.name);
        assert.equal(manifest.theme_color,'#123456');
        assert.deepEqual(manifest.icons,[]);
        assert.equal(adminManifest.name,p.short_name+' Admin');
        for(const value of [contact,admin,md,JSON.stringify(manifest),JSON.stringify(adminManifest),JSON.stringify(api)]){
            assert.ok(!value.includes(other.public_email),'Draft email leaked');
            assert.ok(!value.includes(other.name),'Draft identity leaked');
        }
        assert.ok(!contact.includes('mailto:nebolifestyleclinic@gmail.com'));
        assert.ok(!contact.includes('src="/assets/images/nebo-official-logo.jpg"'));
        assert.ok(!contact.includes('href="/assets/images/nebo-symbol.png"'));
        if(!p.address)assert.ok(!contact.includes('Find us on Google Maps'));
    }
    const failed=render('/contact',{id:'app_settings',site_profile:state(alpha)},true);
    assert.ok(failed.includes('Service website'));
    assert.ok(!failed.includes(alpha.public_email));
    assert.ok(!failed.includes('mailto:nebolifestyleclinic@gmail.com'));
    const noProfile=JSON.parse(render('/manifest.json',{id:'app_settings'}));
    assert.equal(noProfile.name,'Service website');
    assert.deepEqual(noProfile.icons,[]);
});

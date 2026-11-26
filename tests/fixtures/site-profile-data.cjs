const {execFileSync}=require('node:child_process');
const path=require('node:path');
const root=path.resolve(__dirname,'../..');
const env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'http://profile-fixture.test'};
const fields=JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; echo json_encode(array_keys(App\\Services\\SiteProfileService::FIELDS));'],{cwd:root,env,encoding:'utf8'}));
const profile=(name,email,overrides={})=>({...Object.fromEntries(fields.map(k=>[k,''])),name,short_name:name.split(' ')[0],admin_name:name+' Admin',tagline:'A different identity',description:'Professional service information',public_email:email,organization_type:'ProfessionalService',...overrides});
const alpha=profile('Orchid Studio','orchid@example.test',{footer_description:'Orchid footer',address:'18 Example Road',locality:'Orchid City',region:'Test Region',country:'GB',postal_code:'ABC1',hours_weekday:'Weekdays: 9 AM – 5 PM',instagram_url:'https://www.instagram.com/orchid_example/'});
const beta=profile('Cedar Services','cedar@example.test',{address:'',footer_description:'Cedar footer',organization_type:'Organization'});
const state=(published,draft=null,previous=null,can_restore=false)=>({version:1,published,draft,previous,can_restore});
function render(path,settings,unavailable=false){
    return execFileSync('php',['tests/fixtures/site-profile-view.php',path],{cwd:root,env,input:JSON.stringify({settings,unavailable}),encoding:'utf8'});
}
module.exports={root,env,profile,alpha,beta,state,render};

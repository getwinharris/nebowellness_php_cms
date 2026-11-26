const { chromium }=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'../..');
const fixture=surface=>execFileSync('php',[path.join(root,'tests/fixtures/a2ui-chat-view.php'),surface],{
    encoding:'utf8',env:{...process.env,BAPX_TEST_MODE:'1',APP_URL:'http://nebo-fixture.test'}
});
const html={support:fixture('support'),admin:fixture('admin')};
const message={version:'1.0',createSurface:{
    catalogId:'https://bapx.in/schemas/bapxcms/a2ui/1.0/catalog',surfaceId:'records_0123456789abcdef',
    components:[
        {id:'root',component:'Stack',direction:'vertical',children:['card']},
        {id:'card',component:'Card',title:'Fixture retrieved records',children:['metric','text']},
        {id:'metric',component:'Metric',label:'Sample requests',value:'12'},
        {id:'text',component:'Text',text:'<img src=x onerror=alert(1)> '+('Longword'.repeat(28))},
    ]
}};
(async()=>{
    const browser=await chromium.launch({channel:'chrome',headless:true});
    try {
        for(const surface of ['support','admin']) for(const width of [1440,375,390]) {
            const page=await browser.newPage({viewport:{width,height:900},serviceWorkers:'block'});
            const errors=[];page.on('pageerror',e=>errors.push(e.message));
            let sent=0;
            const pagePath=surface==='support'?'/support':'/admin/agent';
            const askPath=surface==='support'?'/support/ask':'/admin/agent/ask';
            await page.route('**/*',async route=>{
                const url=new URL(route.request().url());
                if(url.hostname!=='nebo-fixture.test')return route.abort();
                if(url.pathname===pagePath)return route.fulfill({contentType:'text/html',body:html[surface]});
                if(url.pathname===askPath&&route.request().method()==='POST'){
                    sent++;
                    const payload=surface==='support'?{reply:'Fixture text answer'}:{answer:'Fixture text answer'};
                    payload.response_mode='model_with_retrieval';
                    payload.sources=[];
                    payload.a2ui=sent===1?message:{...message,version:'0.8'};
                    return route.fulfill({contentType:'application/json',body:JSON.stringify(payload)});
                }
                if(/^\/assets\/[a-zA-Z0-9_/.\-]+$/.test(url.pathname)){
                    const file=path.join(root,url.pathname);
                    if(file.startsWith(root+'/assets/')&&fs.existsSync(file)&&fs.statSync(file).isFile())
                        return route.fulfill({path:file});
                }
                return route.fulfill({status:404,body:''});
            });
            await page.goto('http://nebo-fixture.test'+pagePath);
            if(surface==='support')await page.locator('.support-fab').click();
            const input=page.locator(surface==='support'?'#support-message':'#agent-input');
            const button=page.locator(surface==='support'?'#support-form button[type=submit]':'#agent-submit').first();
            await input.fill('Fixture records');
            await button.click();
            const card=page.locator('.a2ui-card');
            await card.waitFor({state:'visible'});
            assert.match(await card.innerText(),/Sample requests/);
            assert.match(await card.innerText(),/<img src=x onerror=alert\(1\)>/);
            assert.equal(await card.locator('img,script,[onerror]').count(),0);
            const dimensions=await card.evaluate(el=>({client:el.clientWidth,scroll:el.scrollWidth}));
            assert.ok(dimensions.scroll<=dimensions.client+2,JSON.stringify(dimensions));
            if(width<=390)assert.ok(dimensions.client>=230,JSON.stringify(dimensions));
            await input.fill('Malformed surface');
            await button.click();
            await page.waitForFunction(()=>document.body.innerText.split('Fixture text answer').length>=3);
            assert.equal(await page.locator('.a2ui-card').count(),1);
            assert.equal(errors.length,0,errors.join('; '));
            console.log(JSON.stringify({surface,width,dimensions,accepted:'pass',malformedFallback:'pass',errors}));
            await page.close();
        }
    }finally{await browser.close()}
})().catch(e=>{console.error(e);process.exit(1)});

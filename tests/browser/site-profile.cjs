const {chromium}=require('playwright');
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path');
const {root,alpha,beta,state,render}=require('../fixtures/site-profile-data.cjs');
(async()=>{
    const browser=await chromium.launch({channel:'chrome',headless:true});
    try{
        for(const [p,other] of [[alpha,beta],[beta,alpha]]){
            const settings={id:'app_settings',site_profile:state(p,other),palette_primary:'#123456'};
            const html={
                '/contact':render('/contact',settings),
                '/admin/site-profile':render('/admin/site-profile',settings),
            };
            for(const width of [1440,375,390])for(const pagePath of Object.keys(html)){
                const page=await browser.newPage({viewport:{width,height:900},serviceWorkers:'block',reducedMotion:'reduce'});
                const errors=[];page.on('pageerror',e=>errors.push(e.message));
                await page.route('**/*',async route=>{
                    const url=new URL(route.request().url());
                    if(url.hostname!=='profile-fixture.test')return route.abort();
                    if(html[url.pathname])return route.fulfill({contentType:'text/html',body:html[url.pathname]});
                    if(/^\/assets\/[a-zA-Z0-9_/.\-]+$/.test(url.pathname)){
                        const file=path.join(root,url.pathname);
                        if(file.startsWith(root+'/assets/')&&fs.existsSync(file)&&fs.statSync(file).isFile())return route.fulfill({path:file});
                    }
                    return route.fulfill({status:404,body:''});
                });
                await page.goto('http://profile-fixture.test'+pagePath);
                if(pagePath==='/contact'){
                    assert.equal(await page.locator('.brand').getAttribute('aria-label'),p.name+' home');
                    assert.ok(await page.locator('a[href="mailto:'+p.public_email+'"]').count());
                    assert.equal(await page.locator('a[href="mailto:'+other.public_email+'"]').count(),0);
                    assert.equal(await page.locator('.brand .brand__text').textContent(),p.name);
                }else{
                    assert.equal(await page.locator('.admin-sidebar__brand span').textContent(),p.admin_name);
                    assert.ok(await page.locator('.site-profile-preview').getByText(other.name,{exact:true}).count());
                    assert.equal(await page.locator('#profile-name').inputValue(),other.name);
                    assert.ok(await page.getByRole('button',{name:'Publish saved draft'}).isVisible());
                    const field=page.locator('.site-profile-fields');
                    const dim=await field.evaluate(e=>({client:e.clientWidth,scroll:e.scrollWidth}));
                    assert.ok(dim.scroll<=dim.client+2,JSON.stringify(dim));
                }
                const dim=await page.evaluate(()=>({client:document.documentElement.clientWidth,scroll:document.documentElement.scrollWidth}));
                assert.ok(dim.scroll<=dim.client+2,JSON.stringify({width,pagePath,...dim}));
                assert.equal(errors.length,0,errors.join('; '));
                console.log(JSON.stringify({brand:p.name,width,page:pagePath,overflow:false,errors}));
                await page.close();
            }
        }
    }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});

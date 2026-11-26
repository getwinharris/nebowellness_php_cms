const assert=require('node:assert/strict');
const {test}=require('node:test');
const {spawn,execFileSync}=require('node:child_process');
const {once}=require('node:events');
const {mkdtemp,readdir,readFile,rm}=require('node:fs/promises');
const net=require('node:net'),path=require('node:path'),os=require('node:os'),zlib=require('node:zlib');
const root=path.resolve(__dirname,'..');
function php(code){return JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1'},encoding:'utf8'}))}
function png(w,h){
    const crc=bytes=>{let c=0xffffffff;for(const b of bytes){c^=b;for(let i=0;i<8;i++)c=(c>>>1)^((c&1)?0xedb88320:0);}return (c^0xffffffff)>>>0;};
    const chunk=(kind,data)=>{const body=Buffer.concat([Buffer.from(kind),data]);const len=Buffer.alloc(4),sum=Buffer.alloc(4);len.writeUInt32BE(data.length);sum.writeUInt32BE(crc(body));return Buffer.concat([len,body,sum]);};
    const header=Buffer.alloc(13);header.writeUInt32BE(w,0);header.writeUInt32BE(h,4);header[8]=8;header[9]=6;
    return Buffer.concat([Buffer.from('89504e470d0a1a0a','hex'),chunk('IHDR',header),chunk('IDAT',zlib.deflateSync(Buffer.alloc((w*4+1)*h))),chunk('IEND',Buffer.alloc(0))]);
}
function ico(w,h) {
    const data=png(w,h),header=Buffer.alloc(22);
    header.writeUInt16LE(1,2);header.writeUInt16LE(1,4);
    header[6]=w;header[7]=h;
    header.writeUInt16LE(1,10);header.writeUInt16LE(32,12);
    header.writeUInt32LE(data.length,14);header.writeUInt32LE(22,18);
    return Buffer.concat([header,data]);
}
test('published palette is bounded, partial patches preserve other settings, reset is contrast safe',()=>{
    const result=php(String.raw`
    $s=App\Services\BrandAssetService::class;
    $base=['palette_primary'=>'#abc','palette_canvas'=>'not a color','palette_text'=>['bad'],'clinic_phones'=>['fixture']];
    $invalid=false;$contrast=false;
    try{$s::palettePatch([],['palette_link'=>['bad']]);}catch(InvalidArgumentException){$invalid=true;}
    try{$s::palettePatch([],['palette_text'=>'#FFFFFF']);}catch(InvalidArgumentException){$contrast=true;}
    $reset=$s::palettePatch([],['reset_palette'=>'1']);
    echo json_encode(['effective'=>$s::effectivePalette($base),'patch'=>$s::palettePatch($base,['palette_primary'=>'#123456']),'unchanged'=>$s::palettePatch([],App\Services\BrandAssetService::PALETTE),'reset'=>$reset,'contrast'=>$s::contrast($reset['palette_link'],$reset['palette_canvas']),'invalid'=>$invalid,'badContrast'=>$contrast]);
    `);
    assert.equal(result.effective.palette_primary,'#AABBCC');
    assert.equal(result.effective.palette_canvas,'#F7F9FA');
    assert.equal(result.effective.palette_text,'#222222');
    assert.deepEqual(result.patch,{palette_primary:'#123456'});
    assert.deepEqual(result.unchanged,[]);
    assert.ok(result.contrast>=4.5);
    assert.equal(result.invalid,true);assert.equal(result.badContrast,true);
    assert.ok(!('clinic_phones' in result.patch));
});
test('real multipart helper uses server-selected image names and validates all inputs before storing',async t=>{
    const dir=await mkdtemp(path.join(os.tmpdir(),'nebo-brand-fixture-'));
    const listener=net.createServer();await new Promise(resolve=>listener.listen(0,'127.0.0.1',resolve));
    const port=listener.address().port;await new Promise(resolve=>listener.close(resolve));
    const proc=spawn('php',['-S','127.0.0.1:'+port,path.join(root,'tests/fixtures/brand-asset-upload.php')],{cwd:root,env:{...process.env,BAPX_TEST_MODE:'1',BRAND_FIXTURE_DIRECTORY:dir},stdio:'ignore'});
    t.after(async()=>{if(proc.exitCode===null){const exit=once(proc,'exit');proc.kill();await exit;}await rm(dir,{recursive:true,force:true});});
    const origin='http://127.0.0.1:'+port;
    for(let i=0;;i++){
        try{await fetch(origin);break;}catch(e){if(i>=60)throw e;await new Promise(r=>setTimeout(r,50));}
    }
    async function send(files={},fields={}){
        const form=new FormData();
        for(const [name,{bytes,filename='fixture.png',type='image/png'}] of Object.entries(files))form.append(name,new Blob([bytes],{type}),filename);
        for(const [k,v] of Object.entries(fields))form.append(k,v);
        const response=await fetch(origin,{method:'POST',body:form});return {status:response.status,data:await response.json()};
    }
    const image=png(16,16);
    for(const files of [
        {logo_file:{bytes:Buffer.from('plain text'),filename:'not-image.png'}},
        {favicon_file:{bytes:png(65,65)}},
        {logo_file:{bytes:png(513,1)}},
        {logo_file:{bytes:Buffer.alloc(102401)}},
        {favicon_file:{bytes:Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="8" height="8"></svg>'),filename:'fixture.svg',type:'image/svg+xml'}},
        {logo_file:{bytes:image},favicon_file:{bytes:Buffer.from('bad')}},
        {logo_file:{bytes:image.subarray(0,33)}},
        {favicon_file:{bytes:Buffer.concat([ico(16,16).subarray(0,22),Buffer.alloc(ico(16,16).length-22)])}},

    ]){
        const r=await send(files);assert.equal(r.status,422,JSON.stringify(r));
        assert.deepEqual(await readdir(dir),[]);
    }
    assert.equal((await send({logo_file:{bytes:image}},{palette_text:'#FFFFFF'})).status,422);
    assert.deepEqual(await readdir(dir),[]);
    // Browser filename and declared content type do not determine the stored extension.
    const a=await send({logo_file:{bytes:image,filename:'fixture.unknown',type:'application/octet-stream'},favicon_file:{bytes:image}});
    assert.equal(a.status,200,JSON.stringify(a));
    const urls=a.data.patch;
    assert.match(urls.logo_url,/^\/assets\/images\/brand\/logo-[0-9a-f]{32}\.png$/);
    assert.match(urls.favicon_url,/^\/assets\/images\/brand\/favicon-[0-9a-f]{32}\.png$/);
    for(const url of Object.values(urls))assert.deepEqual(await readFile(path.join(dir,path.basename(url))),image);
    const b=await send({logo_file:{bytes:image}});
    assert.equal(b.status,200);assert.notEqual(b.data.patch.logo_url,urls.logo_url);
    assert.equal((await readdir(dir)).length,3);
    const icon=ico(16,16);
    const c=await send({favicon_file:{bytes:icon,filename:'fixture.ico',type:'image/x-icon'}});
    assert.equal(c.status,200,JSON.stringify(c));
    assert.match(c.data.patch.favicon_url,/^\/assets\/images\/brand\/favicon-[0-9a-f]{32}\.ico$/);
    const malformed=Buffer.from(icon);malformed.writeUInt32LE(999999,14);
    assert.equal((await send({favicon_file:{bytes:malformed,filename:'fixture.ico'}})).status,422);
    assert.equal((await send({favicon_file:{bytes:ico(65,65),filename:'fixture.ico'}})).status,422);
    assert.equal((await readdir(dir)).length,4);
});

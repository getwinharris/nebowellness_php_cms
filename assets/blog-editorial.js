(function(root){
    'use strict';
    // Exact editor state comparison prevents a late request from overwriting edits
    // or another article. Keep this pure so the concurrency contract is testable.
    function sameSnapshot(a,b) {
        return !!a && !!b && a.epoch===b.epoch && a.slug===b.slug &&
            a.title===b.title && a.content===b.content && a.template===b.template &&
            a.sourceUrl===b.sourceUrl;
    }
    function wire(editor) {
        const status=document.getElementById('blog-ai-status');
        const review=document.getElementById('blog-ai-review');
        const suggestion=document.getElementById('blog-ai-suggestion');
        const apply=document.getElementById('blog-ai-apply');
        const discard=document.getElementById('blog-ai-discard');
        const buttons=[['enhance-title','title','enhance-title'],['enhance-content','content','enhance-content'],['generate-draft','content','ai-draft']];
        if(!status || !review || !suggestion || !apply || !discard) return;
        let pending=null, serial=0;
        function clear(message) {
            pending=null;review.hidden=true;suggestion.value='';status.textContent=message||'';
        }
        for (const [id,field,route] of buttons) {
            const button=document.getElementById(id);
            if(!button) continue;
            button.addEventListener('click',async function(){
                const original=editor.snapshot();
                if(!original)return;
                if(route==='ai-draft' && original.content.trim()!==''){
                    clear('Use Enhance content to preserve the existing article.');return;
                }
                const request=++serial;
                clear('Requesting an AI suggestion…');
                const label=button.textContent;button.disabled=true;button.textContent='Working…';
                try {
                    const payload={_csrf:document.querySelector('#blog-form input[name="_csrf"]').value};
                    if(route==='ai-draft')Object.assign(payload,{title:original.title,template:original.template,source_url:original.sourceUrl,content:original.content});
                    else payload[field]=original[field];
                    const r=await fetch('/admin/blog/'+route,{method:'POST',body:new URLSearchParams(payload),credentials:'same-origin'});
                    const data=await r.json().catch(()=>({error:'Unreadable response. Original retained.'}));
                    if(request!==serial)return;
                    if(!r.ok||data.error||data.source!=='provider'||data.mode!=='review_required'||typeof data[field]!=='string'||!data[field].trim()){
                        clear(data.error||'The AI did not return a usable suggestion. Original retained.');return;
                    }
                    if(!sameSnapshot(original,editor.snapshot())){
                        clear('The editor changed while the AI was working. Original retained.');return;
                    }
                    pending={original,field,value:data[field]};suggestion.value=data[field];review.hidden=false;
                    status.textContent='AI suggestion ready. Review it before applying; nothing has been saved.';
                } catch(err) {
                    if(request===serial)clear('The AI request failed. Original retained.');
                } finally {button.disabled=false;button.textContent=label;}
            });
        }
        apply.addEventListener('click',()=>{
            if(!pending)return;
            if(!sameSnapshot(pending.original,editor.snapshot())){
                clear('The editor changed since this suggestion was generated. Original retained.');return;
            }
            editor.apply(pending.field,pending.value);clear('Suggestion applied to the editor. Review and press Save when ready.');
        });
        discard.addEventListener('click',()=>{++serial;clear('Suggestion discarded. Original retained.');});
        editor.onReset=()=>{++serial;clear('');};
    }
    if(typeof module==='object'&&module.exports)module.exports={sameSnapshot};
    else document.addEventListener('DOMContentLoaded',()=>{if(root.beEditorial)wire(root.beEditorial);});
})(typeof window==='object'?window:this);

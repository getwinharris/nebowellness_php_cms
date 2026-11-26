/* Native A2UI 1.0 literal snapshot subset. No functions, HTML, actions or data binding. */
(function (root) {
    'use strict';
    const catalogId = 'https://bapx.in/schemas/bapxcms/a2ui/1.0/catalog';
    const own = (v, k) => Object.prototype.hasOwnProperty.call(v, k);
    const record = v => v !== null && typeof v === 'object' && !Array.isArray(v);
    const exact = (v, keys) => record(v) && Object.keys(v).length === keys.length && keys.every(k => own(v,k));
    const text = (v,max) => typeof v === 'string' && v.length>=1 && v.length<=max*2 && [...v].length <= max;
    const id = v => typeof v === 'string' && /^[A-Za-z_][A-Za-z0-9_]{0,63}(?![\s\S])/.test(v);
    const children = (v,max) => Array.isArray(v) && v.length>=1 && v.length<=max &&
        v.every(id) && new Set(v).size===v.length;
    function validComponent(v) {
        if (!record(v)||!id(v.id)) return false;
        switch (v.component) {
            case 'Text': return exact(v,['id','component','text'])&&text(v.text,4000);
            case 'Metric': return exact(v,['id','component','label','value'])&&text(v.label,120)&&text(v.value,160);
            case 'Stack': return exact(v,['id','component','direction','children'])&&
                ['vertical','horizontal'].includes(v.direction)&&children(v.children,24);
            case 'Card': return exact(v,['id','component','title','children'])&&text(v.title,180)&&children(v.children,12);
            default: return false;
        }
    }
    function validatedSnapshot(message) {
        if (!exact(message,['version','createSurface'])||message.version!=='1.0') return null;
        const surface=message.createSurface;
        if (!exact(surface,['surfaceId','catalogId','components'])||surface.catalogId!==catalogId||
            typeof surface.surfaceId!=='string'||!/^records_[a-f0-9]{16}(?![\s\S])/.test(surface.surfaceId)||
            !Array.isArray(surface.components)||surface.components.length<1||surface.components.length>48) return null;
        const byId=new Map();
        for (const item of surface.components) {
            if (!validComponent(item)||byId.has(item.id)) return null;
            byId.set(item.id,item);
        }
        const seen=new Set();
        function walk(key,depth) {
            if (depth>6||seen.has(key)||!byId.has(key)) return false;
            seen.add(key);
            return (byId.get(key).children||[]).every(child=>walk(child,depth+1));
        }
        return walk('root',0)&&seen.size===byId.size ? byId : null;
    }
    function append(target,message) {
        try {
            const components=validatedSnapshot(message);
            if (!components||!target||!target.ownerDocument) return false;
            const doc=target.ownerDocument;
            function node(tag,cls,value) {
                const el=doc.createElement(tag);
                el.className=cls;
                if (value!==undefined) el.textContent=value;
                return el;
            }
            function render(key) {
                const v=components.get(key);
                switch (v.component) {
                    case 'Text': return node('p','a2ui-text',v.text);
                    case 'Metric': {
                        const metric=node('div','a2ui-metric');
                        metric.append(node('span','a2ui-metric-label',v.label),node('strong','a2ui-metric-value',v.value));
                        return metric;
                    }
                    case 'Stack': {
                        const stack=node('div','a2ui-stack a2ui-stack--'+v.direction);
                        stack.append(...v.children.map(render));return stack;
                    }
                    case 'Card': {
                        const card=node('section','a2ui-card');
                        card.append(node('h4','a2ui-card-title',v.title),...v.children.map(render));return card;
                    }
                }
            }
            const wrapper=node('section','a2ui-readonly');
            wrapper.setAttribute('aria-label','Retrieved records');
            wrapper.append(node('p','a2ui-source','Retrieved records'),render('root'));
            target.append(wrapper);
            return true;
        } catch {
            // Existing text reply stays visible if the optional structured surface is invalid.
            return false;
        }
    }
    const api=Object.freeze({append,validatedSnapshot});
    if (typeof module!=='undefined' && module.exports) module.exports=api;
    else root.BapxA2ui=api;
})(typeof window==='undefined'?globalThis:window);

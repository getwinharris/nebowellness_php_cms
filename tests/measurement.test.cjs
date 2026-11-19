const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const consent = fs.readFileSync(require.resolve('../assets/measurement-consent.js'), 'utf8');
const frame = fs.readFileSync(require.resolve('../assets/measurement-frame.js'), 'utf8');

function page({stored, page = 'home', privacy = false, search = '', hash = ''} = {}) {
    const sent = [], children = [], listeners = {};
    const status = {};
    const fields = ['analytics', 'advertising'].map(name => ({name}));
    const form = {elements: Object.fromEntries(fields.map(input => [input.name, input])), querySelectorAll: () => fields,
        querySelector: () => status, addEventListener: (type, fn) => {form[type] = fn;}};
    const config = {ga: 'G-ABCD123456', ads: 'AW-123456789', pixel: '123456789', page};
    const elements = {'clinic-measurement': {dataset: config}, 'measurement-choices': page ? null : form, measurement: {hidden:true}};
    const window = {addEventListener: (event, listener) => {listeners[event] = listener;}};
    const document = {getElementById: id => elements[id], createElement: () => ({
        setAttribute(name, value) {this[name] = value;}, addEventListener(type, fn) {this[type] = fn;},
        remove() {children.splice(children.indexOf(this), 1);}, contentWindow: {postMessage: data => sent.push(data)}
    }), body: {append: target => {children.push(target); target.load();}}};
    const localStorage = {getItem: () => stored, setItem: (_, value) => {stored = value;}};
    vm.runInNewContext(consent, {document, window, location:{search,hash}, navigator:{globalPrivacyControl:privacy}, localStorage, URLSearchParams});
    return {children,sent,form,stored:()=>stored,status,change(value) {stored = value; listeners.storage({key:'nebo.measurement.v1'});}};
}

const granted = JSON.stringify({expires:Date.now()+60000, analytics:true, advertising:true});
test('default, expiry, browser signals, privacy, fragments and query strings suppress tag loading', () => {
    for (const options of [{}, {stored:'{invalid'}, {stored:JSON.stringify({expires:0,analytics:true})},
        {stored:granted,privacy:true},{stored:granted,page:''},{stored:granted,hash:'#intake'},{stored:granted,search:'?q=private'}]) {
        assert.equal(page(options).children.length, 0);
    }
});

test('choices are independent, saved on privacy, and withdrawal tears down the sandbox', () => {
    const preferences = page({page:''});
    preferences.form.elements.analytics.checked = true;
    preferences.form.submit({preventDefault() {}});
    assert.equal(JSON.parse(preferences.stored()).advertising, false);
    assert.equal(preferences.children.length, 0);
    const visit = page({stored:preferences.stored()});
    assert.equal(visit.children[0].sandbox, 'allow-scripts');
    assert.equal(visit.children[0].referrerPolicy, 'no-referrer');
    assert.deepEqual({...visit.sent[0]}, {analytics:'G-ABCD123456', ads:'', pixel:'', source:'', page:'home'});
    const sourced = page({stored:preferences.stored(), search:'?utm_source=instagram'});
    assert.equal(sourced.sent[0].source, 'instagram');
    for (const search of ['?utm_source=therapy', '?utm_source=google&utm_term=private', '?utm_source=a&utm_source=google']) {
        assert.equal(page({stored:preferences.stored(),search}).children.length, 0);
    }
    visit.change(granted);
    assert.equal(visit.children.length, 2);
    assert.equal(visit.sent.filter(data => data.analytics).length, 1, 'Adding advertising consent does not count Analytics twice');
    visit.change(granted);
    assert.equal(visit.sent.length, 2, 'Saving the same preferences again preserves both original frames');
    visit.change(null);
    assert.equal(visit.children.length, 0);
});

test('frame accepts only its embedding origin and sends safe, fixed page labels', () => {
    const listeners = {};
    const window = {addEventListener: (event, fn) => listeners[event] = fn, removeEventListener: event => delete listeners[event]};
    const parent = {}, scripts = [], pixels = [];
    const document = {head:{append: tag => scripts.push(tag)}, body:{append: tag => pixels.push(tag)}, createElement: () => ({})};
    vm.runInNewContext(frame, {document, window, parent, URL, URLSearchParams, Image:class {}, location:{href:'https://clinic.test/assets/measurement-frame.html'}});
    const event = {source:parent, origin:'https://other.test', data:{analytics:'G-ABCD123456',ads:'',pixel:'123456789',page:'about',text:'PRIVATE'}};
    listeners.message(event);
    assert.equal(scripts.length, 0);
    listeners.message({...event,origin:'https://clinic.test'});
    assert.equal(scripts.length, 1);
    assert.equal(pixels.length, 1);
    assert.equal(new URL(pixels[0].src).searchParams.get('dl'), 'https://clinic.test/about');
    assert.equal(pixels[0].referrerPolicy, 'no-referrer');
    const sent = JSON.stringify(window.dataLayer);
    assert.match(sent, /https:\/\/clinic.test\/about/);
    assert.doesNotMatch(sent, /PRIVATE/);
    assert.equal(listeners.message, undefined);
});

const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
    constructor() { this.listeners = {}; this.dataset = {}; this.attrs = {}; this.children = []; }
    addEventListener(type, cb) { this.listeners[type] = cb; }
    dispatchEvent(event) { return this.listeners[event.type]?.(event); }
    setAttribute(key, value) { this.attrs[key] = value; }
    removeAttribute(key) { delete this.attrs[key]; }
    appendChild(child) { this.children.push(child); }
    querySelectorAll() { return this.children; }
}
function setup() {
    const scroll = new Element(), button = new Element(), status = new Element(), body = new Element();
    body.children = [{dataset: {applicationId: '1'}, checked: true}];
    scroll.scrollTop = 800; scroll.clientHeight = 200; scroll.scrollHeight = 1000;
    scroll.querySelector = () => body;
    button.dataset.next = '/applications?cursor=next';
    const nodes = {'application-scroll': scroll, 'load-applications': button, 'application-load-status': status};
    const events = [], pending = [];
    const document = {
        getElementById: id => nodes[id], dispatchEvent: e => events.push(e.type),
        createElement: () => ({
            set innerHTML(html) { this.content = {querySelectorAll: () => [...html.matchAll(/data-application-id="(\d+)"/g)].map(m => ({dataset: {applicationId: m[1]}}))}; },
        }),
    };
    vm.runInNewContext(fs.readFileSync('public/js/admin-application-scroll.js', 'utf8'), {
        document, Event, AbortSignal,
        fetch: url => new Promise(resolve => pending.push({url, resolve})),
    });
    const respond = async (ok, data) => {
        pending.at(-1).resolve({ok, headers: {get: () => 'application/json'}, json: async () => data});
        await new Promise(resolve => setImmediate(resolve));
    };
    return {scroll, button, status, body, events, pending, respond};
}

test('scroll loads once, appends unique rows and preserves existing selections and scroll', async () => {
    const s = setup();
    s.scroll.dispatchEvent({type: 'scroll'});
    s.scroll.dispatchEvent({type: 'scroll'});
    assert.equal(s.pending.length, 1);
    assert.equal(s.scroll.attrs['aria-busy'], 'true');
    await s.respond(true, {html: '<tr data-application-id="1"></tr><tr data-application-id="2"></tr>', next: null, total: 2});
    assert.deepEqual(s.body.children.map(r => r.dataset.applicationId), ['1', '2']);
    assert.equal(s.body.children[0].checked, true);
    assert.equal(s.scroll.scrollTop, 800);
    assert.equal(s.button.hidden, true);
    assert.match(s.status.textContent, /모든 접수 내역/);
    assert.deepEqual(s.events, ['applications:loaded']);
});

test('failed request keeps its cursor and allows manual retry without repeated automatic requests', async () => {
    const s = setup();
    s.scroll.dispatchEvent({type: 'scroll'});
    await s.respond(false);
    assert.equal(s.button.dataset.next, '/applications?cursor=next');
    assert.equal(s.button.textContent, '다시 불러오기');
    assert.equal(s.button.disabled, false);
    s.scroll.dispatchEvent({type: 'scroll'});
    assert.equal(s.pending.length, 1);
    s.button.dispatchEvent({type: 'click'});
    assert.equal(s.pending.length, 2);
    await s.respond(true, {html: '<tr data-application-id="2"></tr>', next: '/applications?cursor=third', total: 3});
    assert.equal(s.body.children.length, 2);
    assert.equal(s.button.dataset.next, '/applications?cursor=third');
    assert.equal(s.button.textContent, '더 불러오기');
});

function selectionSetup() {
    const count = new Element(), note = new Element(), all = new Element(), clear = new Element(), header = new Element();
    const submit = new Element(), form = new Element();
    all.dataset = {failed: 'true', total: '25'};
    form.dataset.ready = 'true';
    form.querySelector = selector => selector === 'button[type="submit"]' ? submit : null;
    form.querySelectorAll = () => [];
    const boxes = [];
    const nodes = {'select-all-recipients': header, 'select-every-recipient': all, 'clear-recipients': clear, 'selection-scope-note': note};
    const document = new Element();
    document.getElementById = id => nodes[id];
    document.querySelector = () => null;
    document.createElement = () => new Element();
    document.querySelectorAll = selector => ({'.recipient-checkbox': boxes, '[data-selected-count]': [count], '.js-alimtalk-form': [form]})[selector] || [];
    vm.runInNewContext(fs.readFileSync('public/js/admin-alimtalk-dispatch.js', 'utf8'), {
        document, window: new Element(), setInterval: () => {}, setTimeout: () => {}, Event,
    });
    const addBox = (id, checked = false) => {
        const box = new Element(); box.value = id; box.checked = checked; box.matches = selector => selector === '.recipient-checkbox'; boxes.push(box); return box;
    };
    return {document, boxes, count, all, clear, header, submit, form, addBox};
}

test('appended checkboxes update recipient count without automatically selecting new recipients', () => {
    const s = selectionSetup();
    const first = s.addBox('1', true);
    s.document.dispatchEvent({type: 'change', target: first});
    assert.equal(s.count.textContent, '1명');
    assert.equal(s.submit.disabled, false);
    const second = s.addBox('2');
    s.document.dispatchEvent({type: 'applications:loaded'});
    assert.equal(s.count.textContent, '1명');
    assert.equal(second.checked, false);
    assert.equal(s.header.indeterminate, true);
    second.checked = true;
    s.document.dispatchEvent({type: 'change', target: second});
    assert.equal(s.count.textContent, '2명');
    assert.equal(s.header.checked, true);
});

test('select all failures submits the failure scope without broadening to all registered recipients', () => {
    const s = selectionSetup();
    s.addBox('1');
    s.all.dispatchEvent({type: 'click'});
    const next = s.addBox('2');
    s.document.dispatchEvent({type: 'applications:loaded'});
    assert.equal(next.disabled, true);
    assert.equal(s.count.textContent, '25명');
    s.form.dispatchEvent({type: 'submit'});
    assert.deepEqual(s.form.children.map(({name, value}) => [name, value]), [['scope', 'failed'], ['failed_all', '1']]);
});

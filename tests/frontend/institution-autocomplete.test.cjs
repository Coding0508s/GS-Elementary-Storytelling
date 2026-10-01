const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
    constructor() { this.listeners = {}; this.attrs = {}; this.children = []; this.style = {}; this.value = ''; this.dataset = {}; }
    addEventListener(type, callback) { (this.listeners[type] ||= []).push(callback); }
    dispatchEvent(event) { for (const callback of this.listeners[event.type] || []) callback(event); }
    setAttribute(key, value) { this.attrs[key] = value; }
    getAttribute(key) { return this.attrs[key] ?? null; }
    removeAttribute(key) { delete this.attrs[key]; }
    replaceChildren() { this.children = []; }
    appendChild(child) { this.children.push(child); }
    scrollIntoView() {}
}
function setup() {
    const input = new Element(), list = new Element(), status = new Element();
    input.dataset.suggestionsUrl = '/api/institutions';
    input.setAttribute('aria-expanded', 'false');
    const pending = [];
    const elements = { institution_name: input, institution_suggestions: list, 'institution-status': status };
    const document = { activeElement: input, getElementById: id => elements[id], createElement: () => new Element(), addEventListener: (_, callback) => callback() };
    vm.runInNewContext(fs.readFileSync('public/js/institution-autocomplete.js', 'utf8'), {
        document, setTimeout, clearTimeout, Event,
        fetch: url => new Promise(resolve => pending.push({ url, resolve })),
    });
    const emit = (type, key) => input.dispatchEvent({ type, key, preventDefault() {} });
    const respond = async (index, data) => { pending[index].resolve({ ok: true, json: async () => data }); await new Promise(resolve => setImmediate(resolve)); };
    return { input, list, status, document, pending, emit, respond };
}

test('keyboard selection announces exactly one active option and closes after Enter', async () => {
    const s = setup();
    s.emit('focus'); await s.respond(0, ['첫 기관', '둘째 기관']);
    s.emit('keydown', 'ArrowDown'); s.emit('keydown', 'ArrowDown');
    assert.equal(s.input.getAttribute('aria-activedescendant'), 'institution-option-1');
    assert.deepEqual(s.list.children.map(item => item.getAttribute('aria-selected')), ['false', 'true']);
    s.emit('keydown', 'Enter');
    assert.equal(s.input.value, '둘째 기관');
    assert.equal(s.input.getAttribute('aria-expanded'), 'false');
    assert.equal(s.input.getAttribute('aria-activedescendant'), null);
    assert.match(s.status.textContent, /둘째 기관 선택됨/);
});

test('Escape prevents an in-flight result from reopening the popup', async () => {
    const s = setup(); s.emit('focus'); s.emit('keydown', 'Escape');
    await s.respond(0, ['늦게 도착한 기관']);
    assert.equal(s.input.getAttribute('aria-expanded'), 'false');
    assert.equal(s.list.children.length, 0);
    s.emit('keydown', 'Enter');
    assert.equal(s.input.value, '');
});

test('out-of-order requests cannot replace the latest results', async () => {
    const s = setup(); s.emit('focus');
    s.input.value = '서울'; s.emit('focus');
    await s.respond(1, ['서울 기관']); await s.respond(0, ['이전 기관']);
    assert.equal(s.list.children[0].textContent, '서울 기관');
});

test('results arriving after focus leaves the field remain hidden', async () => {
    const s = setup(); s.emit('focus'); s.document.activeElement = null; s.emit('blur');
    await s.respond(0, ['기관']);
    assert.equal(s.input.getAttribute('aria-expanded'), 'false');
});

test('no matching institutions keeps free-text input available', async () => {
    const s = setup(); s.input.value = '직접 입력 기관'; s.emit('focus');
    await s.respond(0, []);
    assert.equal(s.input.value, '직접 입력 기관');
    assert.equal(s.input.getAttribute('aria-expanded'), 'false');
    assert.match(s.status.textContent, /직접 입력/);
});

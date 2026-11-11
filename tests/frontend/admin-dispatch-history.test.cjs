const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
    constructor(dataset = {}) { this.dataset = dataset; this.listeners = {}; this.attrs = {}; this.nodes = {}; this.focused = false; this.hidden = false; }
    addEventListener(type, callback) { this.listeners[type] = callback; }
    dispatchEvent(event) { return this.listeners[event.type]?.(event); }
    querySelector(selector) { return this.nodes[selector] || null; }
    querySelectorAll(selector) { return this.nodes[selector] || []; }
    setAttribute(name, value) { this.attrs[name] = value; }
    focus(options) { this.focused = true; this.focusOptions = options; }
    showModal() { this.open = true; }
    close() { this.open = false; this.dispatchEvent({type:'close'}); }
}
function setup(auto = false) {
    const history = new Element(), document = new Element(), panel = new Element(), empty = new Element(), scroll = new Element();
    const summary = ['active', 'review'].map(group => new Element({historySummary:group}));
    const tabs = ['active', 'review', 'done'].map(group => {
        const tab = new Element({historyTab:group}); tab.nodes['[data-history-count]'] = new Element(); return tab;
    });
    const rows = ['active', 'review', 'done'].map((group, index) => {
        const row = new Element({dispatchRow:String(index), historyGroup:group});
        row.nodes['[data-dispatch-open]'] = new Element({dispatchOpen:String(index)});
        row.nodes['[data-history-status]'] = new Element(); return row;
    });
    const dialogs = rows.map(row => {
        const dialog = new Element();
        const card = new Element({dispatchId:row.dataset.dispatchRow, status:'scheduled'});
        card.nodes['[data-dispatch-status]'] = new Element(); card.nodes['[data-dispatch-status]'].textContent = '예약 접수 완료';
        dialog.nodes['[data-dispatch-id]'] = card;
        dialog.nodes['[data-dispatch-close]'] = [new Element()]; return dialog;
    });
    history.nodes = {
        '[data-history-tab]':tabs, '[data-dispatch-row]':rows, '[role="tabpanel"]':panel,
        '[data-history-empty]':empty, '.dispatch-history-scroll':scroll, '[data-history-summary]':summary,
        '[data-history-visible]':new Element(), '[data-dispatch-open]':rows.map(r=>r.nodes['[data-dispatch-open]']),
        '.dispatch-dialog':dialogs, '.dispatch-dialog[data-auto-open="true"]':auto ? dialogs[0] : null,
    };
    document.getElementById = id => id === 'alimtalk-progress' ? history : dialogs[Number(id.replace('dispatch-dialog-', ''))];
    vm.runInNewContext(fs.readFileSync('public/js/admin-dispatch-history.js', 'utf8'), {document});
    return {document, history, tabs, rows, dialogs, panel, empty, scroll, summary};
}

test('tabs filter the list and arrow keys switch the selected tab and keyboard focus', () => {
    const s = setup();
    assert.deepEqual(s.rows.map(r=>r.hidden), [false, true, true]);
    s.tabs[0].dispatchEvent({type:'keydown',key:'ArrowRight',preventDefault(){}});
    assert.deepEqual(s.rows.map(r=>r.hidden), [true, false, true]);
    assert.equal(s.tabs[1].attrs['aria-selected'], 'true');
    assert.equal(s.tabs[1].focused, true);
    assert.equal(s.panel.attrs['aria-labelledby'], 'dispatch-tab-review');
});

test('view opens its own dialog and close restores focus without resetting the list position', () => {
    const s = setup(); s.scroll.scrollTop = 140;
    s.rows[0].nodes['[data-dispatch-open]'].dispatchEvent({type:'click'});
    assert.equal(s.dialogs[0].open, true);
    assert.equal(s.dialogs[1].open, undefined);
    s.dialogs[0].nodes['[data-dispatch-close]'][0].dispatchEvent({type:'click'});
    assert.equal(s.dialogs[0].open, false);
    assert.equal(s.scroll.scrollTop, 140);
    assert.equal(s.rows[0].nodes['[data-dispatch-open]'].focused, true);
    assert.equal(s.rows[0].nodes['[data-dispatch-open]'].focusOptions.preventScroll, true);
});

test('live updates move the row between groups and refresh counts without closing its dialog', () => {
    const s = setup(true);
    assert.equal(s.dialogs[0].open, true);
    const card = s.dialogs[0].nodes['[data-dispatch-id]'];
    card.dataset.status = 'completed'; card.nodes['[data-dispatch-status]'].textContent = '실패 포함';
    s.document.dispatchEvent({type:'dispatches:updated',detail:[{id:'0',history_group:'review'}]});
    assert.equal(s.rows[0].hidden, true);
    assert.equal(s.rows[0].nodes['[data-history-status]'].textContent, '실패 포함');
    assert.equal(s.summary[1].textContent, 2);
    assert.equal(s.empty.hidden, false);
    assert.equal(s.dialogs[0].open, true);
    s.dialogs[0].close();
    assert.equal(s.tabs[1].attrs['aria-selected'], 'true');
    assert.equal(s.rows[0].hidden, false);
});

function managementSetup(confirmed) {
    const document = new Element(), window = new Element(), card = new Element(), form = new Element();
    const buttons = [new Element(), new Element()];
    form.nodes.button = buttons;
    card.nodes['.js-schedule-management'] = form;
    document.getElementById = id => id === 'selection-scope-note' ? new Element() : null;
    document.querySelectorAll = selector => selector === '[data-dispatch-id]' ? [card] : [];
    document.createElement = () => new Element();
    form.children = []; form.appendChild = node => form.children.push(node);
    window.confirm = () => confirmed;
    vm.runInNewContext(fs.readFileSync('public/js/admin-alimtalk-dispatch.js', 'utf8'), {document, window, setInterval(){}, setTimeout(){}});
    return {form, buttons};
}

test('reservation cancellation requires confirmation and repeated submits are blocked', () => {
    const cancelled = managementSetup(false);
    let prevented = false;
    cancelled.form.dispatchEvent({type:'submit',submitter:{value:'cancel'},preventDefault(){prevented = true;}});
    assert.equal(prevented, true);
    assert.equal(cancelled.form.children.length, 0);
    const s = managementSetup(true);
    s.form.dispatchEvent({type:'submit',submitter:{value:'cancel'},preventDefault(){}});
    assert.equal(s.form.dataset.submitting, 'true');
    assert.equal(s.form.children[0].name, 'action');
    assert.equal(s.form.children[0].value, 'cancel');
    assert.equal(s.buttons.every(button=>button.disabled), true);
    let repeated = false;
    s.form.dispatchEvent({type:'submit',submitter:{value:'cancel'},preventDefault(){repeated = true;}});
    assert.equal(repeated, true);
    assert.equal(s.form.children.length, 1);
});

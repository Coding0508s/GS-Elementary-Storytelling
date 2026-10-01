const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/privacy-consent.js', 'utf8');
function setup() {
    const elements = {};
    for (const id of ['all_consent','privacy_consent','marketing_consent','submit-btn']) {
        elements[id] = {checked:false,disabled:true,indeterminate:false,addEventListener(type,fn){this[type]=fn;}};
    }
    vm.runInNewContext(source, {
        document:{getElementById:id=>elements[id],addEventListener:(type,fn)=>fn()},
        window:{addEventListener(){}},
    });
    return elements;
}
test('all agreements start unchecked and continuation requires the required agreement',()=>{
    const e=setup();
    assert.equal(e.all_consent.checked,false);
    assert.equal(e.marketing_consent.checked,false);
    assert.equal(e['submit-btn'].disabled,true);
});
test('all consent selects both agreements and clearing it clears both',()=>{
    const e=setup();
    e.all_consent.checked=true;
    e.all_consent.change();
    assert.equal(e.privacy_consent.checked,true);
    assert.equal(e.marketing_consent.checked,true);
    assert.equal(e['submit-btn'].disabled,false);
    e.all_consent.checked=false;
    e.all_consent.change();
    assert.equal(e.privacy_consent.checked,false);
    assert.equal(e.marketing_consent.checked,false);
    assert.equal(e['submit-btn'].disabled,true);
});
test('required consent alone permits continuation with a mixed all-consent state',()=>{
    const e=setup();
    e.privacy_consent.checked=true;
    e.privacy_consent.change();
    assert.equal(e['submit-btn'].disabled,false);
    assert.equal(e.all_consent.checked,false);
    assert.equal(e.all_consent.indeterminate,true);
    assert.equal(e.marketing_consent.checked,false);
});
test('marketing consent alone cannot permit continuation',()=>{
    const e=setup();
    e.marketing_consent.checked=true;
    e.marketing_consent.change();
    assert.equal(e['submit-btn'].disabled,true);
    assert.equal(e.all_consent.indeterminate,true);
});
test('withdrawing optional consent after all-consent does not block continuation',()=>{
    const e=setup();
    e.all_consent.checked=true;
    e.all_consent.change();
    e.marketing_consent.checked=false;
    e.marketing_consent.change();
    assert.equal(e['submit-btn'].disabled,false);
    assert.equal(e.all_consent.checked,false);
    assert.equal(e.all_consent.indeterminate,true);
});

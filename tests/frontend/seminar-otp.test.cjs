const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const template = fs.readFileSync('resources/views/upload-form.blade.php', 'utf8');
const helpers = template.slice(template.indexOf('    const otpSendStatus ='), template.indexOf('    // 지역 데이터'));
const flow = template.slice(template.indexOf('    // OTP 타이머 시작 함수'), template.indexOf('    // 전화번호 포맷팅'));

function setup(response = {success:true}, ok = true) {
    const elements = {};
    function element(id) {
        const classes = new Set();
        return elements[id] = {
            value:'', hidden:true, disabled:false, style:{display:'none'}, dataset:{}, attributes:{},
            textContent:'', focused:false,
            classList:{add:c=>classes.add(c),remove:c=>classes.delete(c),toggle:(c,on)=>on?classes.add(c):classes.delete(c)},
            addEventListener(type, fn){this[type]=fn;},
            setAttribute(k,v){this.attributes[k]=v;}, removeAttribute(k){delete this.attributes[k];},
            focus(){this.focused=true;}, reportValidity(){return true;},
        };
    }
    ['parent_phone','otp-send-status','otp-code-error','otp_code','send-otp-btn','verify-otp-btn',
        'otp-verification-area','otp-success-area','otp-timer','resend-otp-btn','verification_token'].forEach(element);
    elements.parent_phone.value='010-0000-0000';
    let requests = 0;
    let tick;
    const sandbox = {
        document:{getElementById:id=>elements[id],querySelector:()=>({getAttribute:()=> 'test-csrf'})},
        fetch:async()=>{requests++;return {ok,status:ok?200:500,json:async()=>response,text:async()=>JSON.stringify(response)};},
        setInterval:fn=>{tick=fn;return 1;},clearInterval:()=>{},
        otpCodeInput:elements.otp_code,otpSendBtn:elements['send-otp-btn'],otpVerifyBtn:elements['verify-otp-btn'],
        otpVerificationArea:elements['otp-verification-area'],otpSuccessArea:elements['otp-success-area'],
        otpTimer:elements['otp-timer'],resendOtpBtn:elements['resend-otp-btn'],verificationToken:elements.verification_token,
    };
    vm.createContext(sandbox);
    vm.runInContext(helpers+flow,sandbox);
    return {elements,sandbox,requests:()=>requests,tick:()=>tick()};
}

test('sending succeeds without a popup and focuses the code input',async()=>{
    const {elements:e,sandbox}=setup();
    await sandbox.sendOtp();
    assert.equal(e['otp-verification-area'].style.display,'block');
    assert.equal(e.otp_code.focused,true);
    assert.equal(e['otp-send-status'].hidden,true);
    assert.equal(e['send-otp-btn'].disabled,false);
});

test('send errors appear inline and both send controls become available again',async()=>{
    const {elements:e,sandbox}=setup({success:false,message:'잠시 후 다시 시도해주세요'},false);
    await sandbox.sendOtp();
    assert.equal(e['otp-send-status'].hidden,false);
    assert.match(e['otp-send-status'].textContent,/잠시 후/);
    assert.equal(e['resend-otp-btn'].disabled,false);
});

test('a nonnumeric code is rejected locally and its error clears on input',async()=>{
    const {elements:e,requests}=setup();
    e.otp_code.value='ABCDEF';
    await e['verify-otp-btn'].click();
    assert.equal(requests(),0);
    assert.equal(e.otp_code.attributes['aria-invalid'],'true');
    assert.equal(e['otp-code-error'].hidden,false);
    e.otp_code.input();
    assert.equal(e['otp-code-error'].hidden,true);
    assert.equal(e.otp_code.attributes['aria-invalid'],undefined);
});

test('expired codes disable verification and a resend restores it',async()=>{
    const {elements:e,sandbox,tick}=setup();
    await sandbox.sendOtp();
    for(let i=0;i<300;i++)tick();
    assert.equal(e['verify-otp-btn'].disabled,true);
    assert.equal(e['resend-otp-btn'].style.display,'inline');
    await sandbox.sendOtp();
    assert.equal(e['verify-otp-btn'].disabled,false);
    assert.equal(e['resend-otp-btn'].style.display,'none');
});

test('verification failure stays beside the code input',async()=>{
    const {elements:e}=setup({success:false,message:'인증번호가 일치하지 않습니다'},false);
    e.otp_code.value='123456';
    await e['verify-otp-btn'].click();
    assert.equal(e['otp-code-error'].hidden,false);
    assert.equal(e.otp_code.focused,true);
    assert.equal(e['verify-otp-btn'].disabled,false);
});

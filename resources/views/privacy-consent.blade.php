@extends('layouts.app')

@section('title', '개인정보 수집 및 이용 동의 - 예비 초등 Storytelling Contest')

@section('content')
<div class="progress-indicator">
    <div class="progress-step active">1</div>
    <div class="progress-line"></div>
    <div class="progress-step inactive">2</div>
    <div class="progress-line"></div>
    <div class="progress-step inactive">3</div>
</div>

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="text-center mb-4">
            <h2><i class="bi bi-shield-check"></i> 개인정보 수집 및 이용 동의</h2>
            <p class="text-muted">비디오 업로드를 시작하기 전에 개인정보 처리 방침에 동의해주세요.</p>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> 개인정보 수집 및 이용 안내</h5>
            </div>
            <div class="card-body">
                <div class="privacy-content" style="max-height: 300px; overflow-y: auto; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <h6><strong>[개인정보 수집 및 이용 동의]</strong></h6>
                    <p>
                    그레이프시드코리아㈜는 본 대회 운영을 위해 다음과 같이 참가자의 개인정보를 수집 및 이용하고자 합니다.<br>
                    만 14세 미만 자녀(학생)의 개인정보는 법정대리인(학부모)의 동의하에 다음 항목을 수집 및 이용합니다.<br>
                    - 수집 및 이용 목적 : 참가자 심사, 시상, 마케팅 및 홍보<br>
                    - 수집 항목 : 자녀(학생)의 기관명, 반이름, 학년/나이, 이름(한글/영어), 제출영상<br>
                    - 법적대리인(학부모)의 이름, 연락처(전화번호)<br>
                    상기 개인정보 수집 및 이용에 동의합니다.
                   
                    </p>

                    <h6><strong>[참가 동의 및 제출영상의 초상권 활용에 대한 동의]</strong></h6>
                    <p>본인은 위 학생의 학부모로서, 본 대회 참가에 동의하며, 제출하는 영상물의 전체 또는 일부가 그레이프시드코리아(주)의 심사, 마케팅 및 홍보 목적으로 활용되는 것에 동의합니다.<br>
                    상기 참가 및 초상권 활용에 동의합니다.</p>

                    <h6><strong>3. 개인정보 보유 및 이용 기간</strong></h6>
                    <p>- 보유 및 이용기간 : 행사 종료 후 6개월까지 보관하며, 이후 즉시 파기<br>
                    </p>

                    <h6><strong>4. 개인정보 제3자 제공</strong></h6>
                    <p>• 수집된 개인정보는 제3자에게 제공되지 않습니다.<br>
                       • 법령에 의해 요구되는 경우 예외적으로 제공될 수 있습니다.</p>

                    <h6><strong>5. 개인정보 보호책임자</strong></h6>
                    <p>• 문의사항이 있으시면 아래 연락처로 문의해주세요.<br>
                       • 이메일: kr-elementary@grapeseed.com<br>
                       • 전화: 1544-9055</p>
                </div>
            </div>
        </div>

        <form action="{{ route('privacy.consent.process') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-body text-center">
                    <div class="form-check d-flex justify-content-center align-items-center mb-4">
                        <input type="checkbox" 
                               class="form-check-input me-3" 
                               id="privacy_consent" 
                               name="privacy_consent" 
                               value="1" 
                               style="transform: scale(1.5);">
                        <label class="form-check-label fs-5" for="privacy_consent">
                            <strong>위 개인정보 수집 및 이용에 동의합니다.</strong>
                        </label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" 
                                class="btn btn-primary btn-lg"
                                id="submit-btn"
                                disabled>
                            <i class="bi bi-arrow-right"></i> 동의하고 다음 단계로
                        </button>
                    </div>

                    <p class="text-muted mt-3 small">
                        <i class="bi bi-info-circle"></i> 
                        개인정보 수집 및 이용에 동의해야 비디오 업로드가 가능합니다.
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('privacy_consent');
    const submitBtn = document.getElementById('submit-btn');
    
    checkbox.addEventListener('change', function() {
        submitBtn.disabled = !this.checked;
        if (this.checked) {
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-primary');
        } else {
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-secondary');
        }
    });
});
</script>
@endsection 
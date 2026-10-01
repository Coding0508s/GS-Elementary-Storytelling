@extends('layouts.app')

@section('title', '개인정보 동의 - GrapeSEED 세미나')

@section('content')
@include('partials.application-progress', ['currentStep' => 1])

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="text-center mb-4">
            <h2><i class="bi bi-shield-check"></i> 개인정보 수집 및 이용 동의</h2>
            <p class="text-muted">세미나 신청을 위해 개인정보 수집 및 이용 내용을 확인해주세요.</p>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="h5 mb-0"><i class="bi bi-info-circle"></i> 개인정보 수집 및 이용 안내</h3>
            </div>
            <div class="card-body">
                <div role="region" aria-label="개인정보 수집 및 이용 상세 안내" class="privacy-content">
                    <h4 class="h6"><strong>[개인정보 수집 및 이용 동의]</strong></h4>
                    <p>
                    그레이프시드코리아㈜는 본 세미나 운영을 위해 다음과 같이 참가자의 개인정보를 수집 및 이용하고자 합니다.<br>
                    만 14세 미만 자녀(학생)의 개인정보는 법정대리인(학부모)의 동의하에 다음 항목을 수집 및 이용합니다.<br>
                    &nbsp;&nbsp;- 수집 및 이용 목적 : 세미나 신청 접수, 참가자 확인 및 접수 안내<br>
                    &nbsp;&nbsp;- 수집 항목 : 거주 지역, 자녀(학생)의 기관명, 학년/연령, 한글 이름<br>
                    &nbsp;&nbsp;- 법정대리인(학부모)의 이름, 연락처(전화번호)<br>
                    &nbsp;&nbsp;- 강사님께 궁금한 점 (선택 입력)
                   
                    </p>

                    <h4 class="h6"><strong>[개인정보 보유 및 이용 기간]</strong></h4>
                    <p>보유 및 이용기간 : 행사 종료 후 6개월까지 보관하며, 이후 즉시 파기<br>
                    </p>

                    <h4 class="h6"><strong>[개인정보 제3자 제공]</strong></h4>
                    <p>&nbsp;&nbsp;- 수집된 개인정보는 제3자에게 제공되지 않습니다.<br>
                       &nbsp;&nbsp;- 법령에 의해 요구되는 경우 예외적으로 제공될 수 있습니다.</p>

                    <h4 class="h6"><strong>[개인정보 보호책임자]</strong></h4>
                    <p>문의사항이 있으시면 아래 연락처로 문의해주세요.<br>
                       &nbsp;&nbsp;- 이메일: kr-elementary@grapeseed.com<br>
                       &nbsp;&nbsp;- 전화: 1544-9055</p>
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
                        개인정보 수집 및 이용에 동의해야 세미나 신청이 가능합니다.
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

    });
    

});
</script>
@endsection 
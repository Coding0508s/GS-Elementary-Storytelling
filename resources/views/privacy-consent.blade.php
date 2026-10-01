@extends('layouts.app')

@section('title', '개인정보 동의 - GrapeSEED 웨비나')

@section('content')
@include('partials.application-progress', ['currentStep' => 1])

<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="text-center mb-4">
            <h2><i class="bi bi-shield-check"></i> 개인정보 수집 및 이용 동의</h2>
            <p class="text-muted">웨비나 신청을 위해 개인정보 수집 및 이용 내용을 확인해주세요.</p>
        </div>

        <form action="{{ route('privacy.consent.process') }}" method="POST" id="consent-form">
            @csrf
            <div class="agreement-list mb-4">
                <div class="agreement-all">
                    <input type="checkbox" class="form-check-input" id="all_consent">
                    <label for="all_consent"><strong>전체 동의 <span class="small">(선택 항목 포함)</span></strong></label>
                </div>

                <div class="agreement-item">
                    <div class="agreement-line">
                        <input type="checkbox" class="form-check-input" id="privacy_consent" name="privacy_consent" value="1" required>
                        <label for="privacy_consent">개인정보 수집 및 이용 <span class="agreement-required">(필수)</span></label>
                    </div>
                    <details class="agreement-details">
                        <summary><span class="visually-hidden">개인정보 수집 및 이용 상세 내용</span><span class="agreement-chevron" aria-hidden="true"></span></summary>
                        <div class="privacy-content agreement-content">
                    <h4 class="h6"><strong>[개인정보 수집 및 이용 동의]</strong></h4>
                    <p>
                    그레이프시드코리아㈜는 본 웨비나 운영을 위해 다음과 같이 참가자의 개인정보를 수집 및 이용하고자 합니다.<br>
                    만 14세 미만 자녀(학생)의 개인정보는 법정대리인(학부모)의 동의하에 다음 항목을 수집 및 이용합니다.<br>
                    &nbsp;&nbsp;- 수집 및 이용 목적 : 웨비나 신청 접수, 참가자 확인 및 접수 안내<br>
                    &nbsp;&nbsp;- 수집 항목 : 거주 지역, 자녀(학생)의 기관명, 학년/연령, 한글 이름<br>
                    &nbsp;&nbsp;- 법정대리인(학부모)의 이름, 연락처(전화번호)<br>
                    &nbsp;&nbsp;- 강사님께 궁금한 점 (선택 입력)
                   
                    </p>

                    <h4 class="h6"><strong>[개인정보 보유 및 이용 기간]</strong></h4>
                    <p>보유 및 이용기간 : 180일간 보관하며, 이후 즉시 파기<br>
                    </p>

                    <h4 class="h6"><strong>[개인정보 제3자 제공]</strong></h4>
                    <p>&nbsp;&nbsp;- 수집된 개인정보는 제3자에게 제공되지 않습니다.<br>
                       &nbsp;&nbsp;- 법령에 의해 요구되는 경우 예외적으로 제공될 수 있습니다.</p>

                    <h4 class="h6"><strong>[개인정보 보호책임자]</strong></h4>
                    <p>문의사항이 있으시면 아래 연락처로 문의해주세요.<br>
                       &nbsp;&nbsp;- 이메일: kr-elementary@grapeseed.com<br>
                       &nbsp;&nbsp;- 전화: 1544-9055</p>

                        </div>
                    </details>
                </div>

                <div class="agreement-item">
                    <div class="agreement-line">
                        <input type="checkbox" class="form-check-input" id="marketing_consent" name="marketing_consent" value="1" aria-describedby="marketing-consent-help" @checked(old('marketing_consent'))>
                        <label for="marketing_consent">마케팅 정보 수신 동의 <span class="consent-optional">(선택)</span></label>
                    </div>
                    <details class="agreement-details">
                        <summary><span class="visually-hidden">마케팅 정보 수신 동의 상세 내용</span><span class="agreement-chevron" aria-hidden="true"></span></summary>
                        <div class="privacy-content agreement-content">
                            <p>마케팅 목적의 개인정보 이용 및 광고성 문자 수신에 대한 동의입니다.</p>
                <dl class="consent-details">
                    <div>
                        <dt>이용 정보</dt>
                        <dd>학부모 휴대전화번호</dd>
                    </div>
                    <div>
                        <dt>이용 목적</dt>
                        <dd>GrapeSEED 이벤트·프로모션·신규 프로그램 안내</dd>
                    </div>
                    <div>
                        <dt>수신 채널</dt>
                        <dd>(문자·알림톡)</dd>
                    </div>
                    <div>
                        <dt>보유·이용 기간</dt>
                        <dd>동의 철회 시까지</dd>
                    </div>
                    <div>
                        <dt>철회 방법</dt>
                        <dd>고객센터(1544-9055) 또는 수신 문자에 안내된 방법</dd>
                    </div>
                </dl>
                <p class="form-text mb-2">광고성 정보는 오후 9시부터 다음 날 오전 8시 사이에는 전송하지 않습니다.</p>
                <p class="form-text mb-3">수신 동의는 언제든지 철회할 수 있으며, 회사는 관련 법령에 따라 2년마다 수신 동의 여부를 확인합니다.</p>

                        </div>
                    </details>
                </div>

                <div class="agreement-channels" aria-label="마케팅 수신 채널">
                    <i class="bi bi-check2" aria-hidden="true"></i> (문자·알림톡)
                </div>
                <p id="marketing-consent-help" class="form-text mt-2 mb-0">선택 항목에 동의하지 않아도 웨비나 신청이 가능합니다.</p>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="submit-btn" disabled>다음 단계로</button>
            </div>
            <p class="text-muted mt-3 small">필수 항목에 동의하면 다음 단계로 이동할 수 있습니다.</p>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/privacy-consent.js') }}" defer></script>
@endsection

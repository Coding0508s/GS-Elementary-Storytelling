@extends('layouts.app')

@section('title', '연사 초청 웨비나 신청 - GrapeSEED')

@section('content')
@include('partials.application-progress', ['currentStep' => 2])

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="text-center mb-2">
           <!--  <h2><i class="bi bi-pencil-square"></i> 연사 초청 웨비나 신청</h2> -->
            <p class="application-intro"><span>학생과 학부모 정보를 입력해주세요.</span> <span class="small">* 표시는 필수 항목입니다.</span></p>
        </div>

        <form id="upload-form">
            @csrf
            
            <!-- 학생 기본 정보 -->
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="h5 mb-0"><i class="bi bi-person"></i> 학생 기본 정보</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-2">
                            <div class="form-label">거주 지역 <span class="text-danger">*</span></div>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6">
                                    <label for="province" class="visually-hidden">시/도</label>
                                    <select class="form-select" id="province" name="province" required>
                                        <option value="">시/도 선택</option>
                                        @foreach(array_keys(\App\Models\VideoSubmission::REGIONS) as $province)
                                            <option value="{{ $province }}" {{ old('province') == $province ? 'selected' : '' }}>
                                                {{ $province }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="city" class="visually-hidden">시/군/구</label>
                                    <select class="form-select" id="city" name="city" required disabled>
                                        <option value="">시/군/구 선택</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" id="region" name="region" value="{{ old('region') }}">
                        </div>
                        <div class="col-12 mb-2">
                            <label for="institution_name" class="form-label">기관명 <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control" 
                                       id="institution_name" 
                                       name="institution_name" 
                                       value="{{ old('institution_name') }}" 
                                       placeholder="기관명을 검색하거나 직접 입력"
                                       autocomplete="off"
                                       role="combobox"
                                       aria-autocomplete="list"
                                       aria-expanded="false"
                                       aria-controls="institution_suggestions"
                                       aria-describedby="institution-help"
                                       data-suggestions-url="{{ route('api.institutions') }}"
                                       required>
                                <div role="listbox" aria-label="기관 검색 결과" id="institution_suggestions" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="display: none; z-index: 1000; max-height: 320px; overflow-y: auto;">
                                </div>
                            </div>
                            <div id="institution-help" class="form-text">목록에 없어도 직접 입력할 수 있습니다.</div>
                            <div id="institution-status" class="visually-hidden" role="status" aria-live="polite"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-6 mb-2">
                            <label for="grade" class="form-label">학년 / 연령 <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade" name="grade" required>
                                <option value="">학년 또는 연령을 선택하세요</option>
                                @foreach(\App\Models\VideoSubmission::GRADE_OPTIONS as $gradeOption)
                                    <option value="{{ $gradeOption }}" {{ old('grade') == $gradeOption ? 'selected' : '' }}>{{ $gradeOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6 mb-2">
                            <label for="student_name_korean" class="form-label">학생 이름 (한글) <span class="text-danger">*</span></label>
                            <input type="text"
                                   class="form-control"
                                   id="student_name_korean"
                                   name="student_name_korean"
                                   value="{{ old('student_name_korean') }}"
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                    </div>


                </div>
            </div>

            <!-- 학부모 정보 -->
            <div class="card mb-2">
                <div class="card-header">
                    <h3 class="h5 mb-0"><i class="bi bi-people"></i> 학부모 정보</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 mb-2">
                            <label for="parent_name" class="form-label">학부모 성함 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="parent_name" 
                                   name="parent_name" 
                                   value="{{ old('parent_name') }}" 
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                        <div class="col-lg-8 mb-2">
                            <label for="parent_phone" class="form-label">학부모 전화번호 <span class="text-danger">*</span></label>
                            <div class="phone-input-group">
                                <input type="tel" 
                                       class="form-control" 
                                       id="parent_phone" 
                                       name="parent_phone"
                                       inputmode="tel"
                                       autocomplete="tel"
                                       aria-describedby="phone-help otp-send-status"
                                       value="{{ old('parent_phone') }}" 
                                       placeholder="010-1234-5678"
                                       pattern="[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}"
                                       required>
                                <button type="button" 
                                        class="btn btn-outline-primary" 
                                        id="send-otp-btn">
                                    인증번호 전송
                                </button>
                            </div>
                            <p id="otp-send-status" class="form-text mb-0" role="status" aria-live="polite" hidden></p>
                            <div id="phone-help" class="form-text">휴대폰 인증 후 신청할 수 있습니다. 접수번호를 문자로 보내드립니다.</div>
                        </div>
                    </div>
                    
                    <!-- OTP 인증 영역 -->
                    <div class="row" id="otp-verification-area" style="display: none;">
                        <div class="col-12 mb-3">
                            <div class="alert verification-notice">
                                <h4 class="h6 fw-bold mb-2">휴대폰 인증</h4>
                                <p class="mb-2">입력하신 휴대폰 번호로 인증번호를 전송했습니다.</p>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="otp_code" class="form-label">인증번호 <span class="text-danger">*</span></label>
                                        <div class="phone-input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="otp_code" 
                                                   name="otp_code"
                                                   inputmode="numeric"
                                                   autocomplete="one-time-code"
                                                   aria-describedby="otp-timer otp-code-error"
                                                   placeholder="6자리 인증번호"
                                                   maxlength="6"
                                                   pattern="[0-9]{6}">
                                            <button type="button" 
                                                    class="btn btn-primary"
                                                    id="verify-otp-btn">
                                                인증확인
                                            </button>
                                        </div>
                                        <p id="otp-code-error" class="text-danger small mt-2 mb-0" role="alert" hidden></p>
                                        <div class="form-text">
                                            <span id="otp-timer"></span>
                                            <button type="button" 
                                                    class="btn btn-link btn-sm p-0 ms-2" 
                                                    id="resend-otp-btn" 
                                                    style="display: none;">
                                                재전송
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 인증 완료 표시 -->
                    <div class="row" id="otp-success-area" style="display: none;">
                        <div class="col-12 mb-3">
                            <div class="alert verification-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>휴대폰 인증이 완료되었습니다!</strong>
                                <input type="hidden" id="verification_token" name="verification_token" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="h5 mb-0"><i class="bi bi-chat-left-text"></i> 강사님께 궁금한 점</h3>
                </div>
                <div class="card-body">
                    <div id="instructor-questions" class="row g-2">
                        <div id="question-day1-wrap" class="col-12 col-md-6">
                            <label for="question_day1" class="form-label">Day 1 · 김상균 교수님 <span class="text-muted small">(선택)</span></label>
                            <textarea class="form-control"
                                      id="question_day1"
                                      name="question_day1"
                                      rows="4"
                                      maxlength="2000"
                                      placeholder="김상균 교수님께 궁금한 점을 적어 주세요.">{{ old('question_day1') }}</textarea>
                        </div>
                        <div id="question-day2-wrap" class="col-12 col-md-6">
                            <label for="question_day2" class="form-label">Day 2 · 윤윤구 강사님 <span class="text-muted small">(선택)</span></label>
                            <textarea class="form-control"
                                      id="question_day2"
                                      name="question_day2"
                                      rows="4"
                                      maxlength="2000"
                                      placeholder="윤윤구 강사님께 궁금한 점을 적어 주세요.">{{ old('question_day2') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 제출 버튼 -->
            <div class="card">
                <div class="card-body text-center">
                    <p id="application-error" class="text-danger small" role="alert" hidden></p>
                    <button type="submit" class="btn btn-primary btn-lg w-100 application-submit" id="submit-btn">
                        웨비나 신청하기
                    </button>
                    <a href="{{ url('/') }}" class="application-cancel">취소하기</a>

                    <p class="text-muted mt-2 small">
                        <i class="bi bi-info-circle"></i>
                        신청이 완료되면 입력하신 전화번호로 접수번호를 보내드립니다.
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/institution-autocomplete.js') }}" defer></script>
<!-- 지역 데이터를 JavaScript로 전달하기 위한 숨겨진 요소 -->
<script type="application/json" id="regions-data">@json(\App\Models\VideoSubmission::REGIONS)</script>

<script>
// 동시 접속 최적화: 재시도 로직이 포함된 fetch 함수
async function fetchWithRetry(url, options, maxRetries = 3, delay = 1000) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
        try {
            const response = await fetch(url, options);
            
            if (response.ok) {
                return await response.json();
            }
            
            // 서버 과부하 상태인 경우 더 긴 대기
            if (response.status === 503 || response.status === 429) {
                const retryAfter = response.headers.get('Retry-After') || 3;
                const waitTime = Math.max(delay * attempt, retryAfter * 1000);
                
                if (attempt < maxRetries) {
                    console.log(`서버 과부하 감지. ${waitTime/1000}초 후 재시도... (${attempt}/${maxRetries})`);
                    await new Promise(resolve => setTimeout(resolve, waitTime));
                    continue;
                }
            }
            
            // 기타 오류의 경우 JSON 파싱 시도
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP ${response.status}`);
            
        } catch (error) {
            if (attempt === maxRetries) {
                throw error;
            }
            
            console.log(`요청 실패 (${attempt}/${maxRetries}): ${error.message}. ${delay}ms 후 재시도...`);
            await new Promise(resolve => setTimeout(resolve, delay * attempt));
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 페이드인 효과
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease-in';
    
    setTimeout(function() {
        document.body.style.opacity = '1';
    }, 100);
    
    const fileInput = document.getElementById('video_file');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const uploadArea = document.querySelector('.file-upload-area');
    const submitBtn = document.getElementById('submit-btn');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.getElementById('progress-text');
    const otpCodeInput = document.getElementById('otp_code');
    const otpSendBtn = document.getElementById('send-otp-btn');
    const otpVerifyBtn = document.getElementById('verify-otp-btn');
    const otpVerificationArea = document.getElementById('otp-verification-area');
    const otpSuccessArea = document.getElementById('otp-success-area');
    const otpTimer = document.getElementById('otp-timer');
    const resendOtpBtn = document.getElementById('resend-otp-btn');
    const verificationToken = document.getElementById('verification_token');
    
    const otpSendStatus = document.getElementById('otp-send-status');
    const otpCodeError = document.getElementById('otp-code-error');

    function showOtpSendStatus(message, isError = false) {
        otpSendStatus.textContent = message;
        otpSendStatus.hidden = false;
        otpSendStatus.classList.toggle('text-danger', isError);
    }

    function showOtpCodeError(message) {
        otpCodeError.textContent = message;
        otpCodeError.hidden = false;
        otpCodeInput.setAttribute('aria-invalid', 'true');
        otpCodeInput.focus();
    }

    otpCodeInput.addEventListener('input', function() {
        otpCodeError.hidden = true;
        otpCodeInput.removeAttribute('aria-invalid');
    });

    let otpCountdown = null;
    
    // 지역 데이터 (PHP에서 JavaScript로 전달)
    const regionsDataElement = document.getElementById('regions-data');
    const regionsData = regionsDataElement ? JSON.parse(regionsDataElement.textContent) : {};
    
    // 시/도 선택 시 시/군/구 목록 업데이트
    document.getElementById('province').addEventListener('change', function() {
        const selectedProvince = this.value;
        const citySelect = document.getElementById('city');
        const regionInput = document.getElementById('region');
        
        // 시/군/구 선택 초기화
        citySelect.innerHTML = '<option value="">시/군/구 선택</option>';
        citySelect.disabled = !selectedProvince;
        regionInput.value = '';
        
        if (selectedProvince && regionsData[selectedProvince]) {
            // 선택된 시/도의 시/군/구 목록 추가
            regionsData[selectedProvince].forEach(function(city) {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
    });
    
    // 시/군/구 선택 시 최종 지역 값 설정
    document.getElementById('city').addEventListener('change', function() {
        const province = document.getElementById('province').value;
        const city = this.value;
        const regionInput = document.getElementById('region');
        
        if (province && city) {
            regionInput.value = province + ' ' + city;
        } else {
            regionInput.value = '';
        }
    });
    
    // 페이지 로드 시 기존 값 복원 (폼 오류 시)
    document.addEventListener('DOMContentLoaded', function() {
        const oldRegion = '{{ old("region") }}';
        if (oldRegion) {
            const parts = oldRegion.split(' ');
            if (parts.length >= 2) {
                const province = parts[0];
                const city = parts.slice(1).join(' ');
                
                // 시/도 선택
                document.getElementById('province').value = province;
                document.getElementById('province').dispatchEvent(new Event('change'));
                
                // 시/군/구 선택 (약간의 지연 후)
                setTimeout(function() {
                    document.getElementById('city').value = city;
                    document.getElementById('city').dispatchEvent(new Event('change'));
                }, 100);
            }
        }
    });

    // 파일 크기 포맷팅 함수
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    if (fileInput && uploadArea) {
    // 파일 선택 시 정보 표시
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const maxSize = 2 * 1024 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('파일 크기가 1GB를 초과합니다. 더 작은 파일을 선택해주세요.');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                return;
            }

            const allowedTypes = ['video/mp4', 'video/quicktime', 'video/avi', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm', 'video/x-matroska'];
            if (!allowedTypes.includes(file.type)) {
                alert('지원하지 않는 파일 형식입니다. (MP4,MOV만 허용)');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                return;
            }

            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('d-none');
            uploadArea.style.borderColor = '#28a745';
            uploadArea.style.backgroundColor = '#f8fff8';
        }
    });

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        uploadArea.classList.add('dragover');
    }

    function unhighlight(e) {
        uploadArea.classList.remove('dragover');
    }

    uploadArea.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    }
    }

    const applicationForm = document.getElementById('upload-form');
    const applicationError = document.getElementById('application-error');
    applicationForm.addEventListener('invalid', function(event) {
        const field = event.target;
        const errorId = field.id + '-error';
        let feedback = document.getElementById(errorId);
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = errorId;
            feedback.className = 'text-danger small mt-1';
            field.closest('.input-group, .phone-input-group, .position-relative')?.after(feedback);
            if (!feedback.parentNode) field.after(feedback);
        }
        feedback.textContent = field.validationMessage;
        field.setAttribute('aria-invalid', 'true');
        const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
        descriptions.add(errorId);
        field.setAttribute('aria-describedby', [...descriptions].join(' '));
    }, true);
    function clearFieldError(event) {
        const field = event.target;
        if (field.validity?.valid) {
            field.removeAttribute('aria-invalid');
            const feedback = document.getElementById(field.id + '-error');
            if (feedback) feedback.textContent = '';
        }
    }
    applicationForm.addEventListener('input', clearFieldError);
    applicationForm.addEventListener('change', clearFieldError);

    document.getElementById('upload-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        applicationError.hidden = true;
        if (!otpVerifyBtn.dataset.verified) {
            applicationError.textContent = '휴대폰 인증을 완료한 후 신청해주세요.';
            applicationError.hidden = false;
            otpSendBtn.focus();
            return;
        }

        const formData = new FormData(this);
        submitBtn.disabled = true;
        submitBtn.innerHTML = '신청 중…';

        try {
            const response = await fetch('{{ route("upload.process") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                try {
                    const errorData = JSON.parse(errorText);
                    if (errorData.errors) {
                        const errorMessages = Object.values(errorData.errors)
                            .map(messages => Array.isArray(messages) ? messages.join(', ') : messages)
                            .join('\n');
                        throw new Error(errorMessages);
                    }
                    throw new Error(errorData.message || '서버 오류');
                } catch (parseError) {
                    if (parseError instanceof Error && parseError.message && parseError.message !== 'Unexpected token') {
                        throw parseError;
                    }
                    throw new Error('서버 오류 (' + response.status + ')');
                }
            }

            const result = await response.json();
            if (result.success) {
                window.location.href = result.redirect_url || '{{ route("upload.success") }}';
            } else {
                throw new Error(result.message || '제출 실패');
            }
        } catch (error) {
            console.error('제출 실패:', error);
            applicationError.textContent = '신청 중 오류가 발생했습니다: ' + error.message;
            applicationError.hidden = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = '웨비나 신청하기';
        }
    });

    // OTP 타이머 시작 함수
    function startOtpTimer(duration) {
        if (otpCountdown) {
            clearInterval(otpCountdown);
        }
        
        let timeLeft = duration;
        otpTimer.classList.remove('text-danger');
        otpVerifyBtn.disabled = false;
        otpTimer.textContent = `남은 시간: ${Math.floor(timeLeft / 60)}:${String(timeLeft % 60).padStart(2, '0')}`;
        
        otpCountdown = setInterval(() => {
            timeLeft--;
            otpTimer.textContent = `남은 시간: ${Math.floor(timeLeft / 60)}:${String(timeLeft % 60).padStart(2, '0')}`;
            
            otpTimer.classList.toggle('text-danger', timeLeft <= 60);
            if (timeLeft <= 0) {
                clearInterval(otpCountdown);
                otpTimer.textContent = '인증 시간이 만료되었습니다.';
                resendOtpBtn.style.display = 'inline';
                otpVerifyBtn.disabled = true;
            }
        }, 1000);
    }
    
    async function readJsonResponse(resp) {
        const text = await resp.text();
        try {
            return JSON.parse(text);
        } catch (error) {
            if (resp.status === 419) {
                throw new Error('페이지가 오래되었습니다. 새로고침 후 다시 시도해주세요.');
            }
            throw new Error('서버 오류가 발생했습니다. 잠시 후 다시 시도해주세요.');
        }
    }

    // OTP: 인증번호 발송
    async function sendOtp() {
        const phone = document.getElementById('parent_phone').value.trim();
        if (!document.getElementById('parent_phone').reportValidity()) return;
        
        otpSendBtn.disabled = true;
        resendOtpBtn.disabled = true;
        otpSendBtn.textContent = '발송 중…';
        showOtpSendStatus('인증번호를 보내고 있습니다.');
        
        try {
            const resp = await fetch('{{ route("api.otp.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ parent_phone: phone })
            });
            const data = await readJsonResponse(resp);
            
            if (!resp.ok || !data.success) {
                throw new Error(data.message || '발송 실패');
            }
            
            // OTP 인증 영역 표시
            otpVerificationArea.style.display = 'block';
            otpCodeInput.value = '';
            otpCodeInput.disabled = false;
            otpCodeError.hidden = true;
            otpCodeInput.removeAttribute('aria-invalid');
            resendOtpBtn.style.display = 'none';
            
            // 5분 타이머 시작
            startOtpTimer(300); // 5분 = 300초
            
            otpSendStatus.hidden = true;
            otpCodeInput.focus();
            
        } catch (err) {
            showOtpSendStatus('인증번호를 보내지 못했습니다. ' + err.message, true);
        } finally {
            otpSendBtn.disabled = false;
            resendOtpBtn.disabled = false;
            otpSendBtn.textContent = '인증번호 전송';
        }
    }
    
    otpSendBtn.addEventListener('click', sendOtp);
    resendOtpBtn.addEventListener('click', sendOtp);

    // OTP: 인증 확인
    otpVerifyBtn.addEventListener('click', async function() {
        const phone = document.getElementById('parent_phone').value.trim();
        const code = otpCodeInput.value.trim();
        
        if (!/^[0-9]{6}$/.test(code)) {
            showOtpCodeError('숫자 6자리 인증번호를 입력해주세요.');
            return;
        }
        
        otpCodeError.hidden = true;
        otpCodeInput.removeAttribute('aria-invalid');
        otpVerifyBtn.disabled = true;
        otpVerifyBtn.textContent = '확인 중…';
        
        try {
            const resp = await fetch('{{ route("api.otp.verify") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ parent_phone: phone, code })
            });
            const data = await readJsonResponse(resp);
            
            if (!resp.ok || !data.success) {
                throw new Error(data.message || '인증 실패');
            }
            
            // 인증 성공 처리
            if (otpCountdown) {
                clearInterval(otpCountdown);
            }
            
            // UI 업데이트
            otpVerificationArea.style.display = 'none';
            otpSuccessArea.style.display = 'block';
            otpVerifyBtn.dataset.verified = 'true';
            verificationToken.value = 'verified';
            
            // 전화번호 입력 필드 읽기 전용으로 변경 (disabled는 폼 제출 시 값이 전송되지 않음)
            const parentPhoneInput = document.getElementById('parent_phone');
            parentPhoneInput.readOnly = true;
            parentPhoneInput.classList.add('is-verified');
            
        } catch (err) {
            showOtpCodeError('인증번호를 확인해주세요. ' + err.message);
        } finally {
            otpVerifyBtn.disabled = resendOtpBtn.style.display === 'inline';
            otpVerifyBtn.textContent = '인증확인';
        }
    });
    
    // 전화번호 포맷팅
    document.getElementById('parent_phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/[^\d]/g, '');
        if (value.length >= 3 && value.length < 7) {
            value = value.slice(0, 3) + '-' + value.slice(3);
        } else if (value.length >= 7) {
            value = value.slice(0, 3) + '-' + value.slice(3, 7) + '-' + value.slice(7, 11);
        }
        e.target.value = value;
    });

    // 📱 모바일 데이터 환경 감지 함수
    function detectNetworkInfo() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (connection) {
            return {
                effectiveType: connection.effectiveType, // 'slow-2g', '2g', '3g', '4g'
                downlink: connection.downlink, // Mbps
                rtt: connection.rtt, // Round Trip Time (ms)
                saveData: connection.saveData, // 데이터 절약 모드
                type: connection.type // 'cellular', 'wifi', 'ethernet', etc.
            };
        }
        
        // 기본값 (연결 정보를 알 수 없는 경우)
        return {
            effectiveType: '4g',
            downlink: 10,
            rtt: 100,
            saveData: false,
            type: 'unknown'
        };
    }

    // 📊 데이터 사용량 추정 함수
    function estimateDataUsage(fileSize) {
        const networkInfo = detectNetworkInfo();
        
        // 압축률 추정 (비디오 파일의 경우)
        const compressionRatio = 0.8; // 20% 압축 가정
        const estimatedUploadSize = fileSize * compressionRatio;
        
        // 네트워크 오버헤드 (HTTP 헤더, 재시도 등)
        const overheadRatio = 1.1; // 10% 오버헤드
        const totalDataUsage = estimatedUploadSize * overheadRatio;
        
        return {
            originalSize: formatFileSize(fileSize),
            estimatedUploadSize: formatFileSize(estimatedUploadSize),
            totalDataUsage: formatFileSize(totalDataUsage),
            isDataSaver: networkInfo.saveData,
            networkType: networkInfo.effectiveType
        };
    }

    // 📱 모바일 데이터 경고 표시
    function showMobileDataWarning(dataUsage, networkInfo) {
        const warningHtml = `
            <div class="mobile-data-warning alert alert-warning" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill warning-icon"></i>
                    <div>
                        <strong>📱 모바일 데이터 사용 중</strong><br>
                        <small>예상 데이터 사용량: ${dataUsage.totalDataUsage} (원본: ${dataUsage.originalSize})</small>
                    </div>
                </div>
                <div class="network-info mt-2">
                    <small>
                        <strong>네트워크:</strong> ${networkInfo.effectiveType.toUpperCase()} 
                        ${networkInfo.downlink ? `(${networkInfo.downlink} Mbps)` : ''}
                        ${networkInfo.saveData ? ' | 데이터 절약 모드' : ''}
                    </small>
                </div>
            </div>
        `;
        
        // 파일 선택 영역 위에 경고 표시
        const fileInputContainer = document.querySelector('.file-input-container');
        if (fileInputContainer && !document.querySelector('.mobile-data-warning')) {
            fileInputContainer.insertAdjacentHTML('beforebegin', warningHtml);
        }
    }

    // 📏 파일 크기 포맷팅 함수
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // 🗜️ 압축 옵션 제안 함수
    async function showCompressionOption(fileSize, networkInfo) {
        const originalSize = formatFileSize(fileSize);
        const compressedSize = formatFileSize(fileSize * 0.6); // 40% 압축 가정
        const dataSaved = formatFileSize(fileSize * 0.4);
        
        const compressionHtml = `
            <div class="compression-option-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div class="card" style="max-width: 400px; margin: 20px;">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="h5 mb-0"><i class="bi bi-compress"></i> 모바일 데이터 절약 옵션</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>현재 파일 크기:</strong> ${originalSize}</p>
                        <p><strong>압축 후 예상 크기:</strong> ${compressedSize}</p>
                        <p><strong>절약되는 데이터:</strong> ${dataSaved}</p>
                        <p><strong>네트워크:</strong> ${networkInfo.effectiveType.toUpperCase()}</p>
                        
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle"></i>
                                압축 시 화질이 약간 저하될 수 있지만, 데이터 사용량을 크게 줄일 수 있습니다.
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-success me-2" id="compress-yes">
                            <i class="bi bi-check-circle"></i> 압축하여 업로드
                        </button>
                        <button type="button" class="btn btn-secondary" id="compress-no">
                            <i class="bi bi-x-circle"></i> 원본 그대로 업로드
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // 모달 표시
        document.body.insertAdjacentHTML('beforeend', compressionHtml);
        
        return new Promise((resolve) => {
            document.getElementById('compress-yes').addEventListener('click', () => {
                document.querySelector('.compression-option-modal').remove();
                resolve(true);
            });
            
            document.getElementById('compress-no').addEventListener('click', () => {
                document.querySelector('.compression-option-modal').remove();
                resolve(false);
            });
        });
    }

    // 📱 모바일 최적화 업로드 전략 적용
    function applyMobileOptimization(file, networkInfo) {
        const uploadStrategy = {
            chunkSize: 5 * 1024 * 1024, // 기본 5MB
            timeout: 900000, // 기본 15분
            retryAttempts: 3,
            retryDelay: 1000
        };
        
        // 네트워크 상태에 따른 전략 조정
        if (networkInfo.effectiveType === '2g' || networkInfo.effectiveType === 'slow-2g') {
            uploadStrategy.chunkSize = 512 * 1024; // 512KB
            uploadStrategy.timeout = 3600000; // 1시간
            uploadStrategy.retryAttempts = 10;
            uploadStrategy.retryDelay = 5000;
        } else if (networkInfo.effectiveType === '3g') {
            uploadStrategy.chunkSize = 1 * 1024 * 1024; // 1MB
            uploadStrategy.timeout = 1800000; // 30분
            uploadStrategy.retryAttempts = 7;
            uploadStrategy.retryDelay = 3000;
        } else if (networkInfo.effectiveType === '4g' && networkInfo.downlink > 5) {
            uploadStrategy.chunkSize = 10 * 1024 * 1024; // 10MB
            uploadStrategy.timeout = 900000; // 15분
            uploadStrategy.retryAttempts = 3;
            uploadStrategy.retryDelay = 1000;
        }
        
        console.log('📱 모바일 최적화 전략 적용:', {
            networkType: networkInfo.effectiveType,
            chunkSize: formatFileSize(uploadStrategy.chunkSize),
            timeout: Math.round(uploadStrategy.timeout / 60000) + '분',
            retryAttempts: uploadStrategy.retryAttempts
        });
        
        return uploadStrategy;
    }

    // 📊 데이터 사용량 모니터링 및 알림
    function monitorDataUsage(file, networkInfo) {
        const dataUsage = estimateDataUsage(file.size);
        const usageThresholds = {
            '2g': 50 * 1024 * 1024, // 50MB
            '3g': 100 * 1024 * 1024, // 100MB
            '4g': 500 * 1024 * 1024 // 500MB
        };
        
        const threshold = usageThresholds[networkInfo.effectiveType] || usageThresholds['4g'];
        
        if (file.size > threshold) {
            showDataUsageWarning(dataUsage, networkInfo, threshold);
        }
        
        // 데이터 사용량 추적 시작
        startDataUsageTracking(file.size, networkInfo);
    }

    // ⚠️ 데이터 사용량 경고 표시
    function showDataUsageWarning(dataUsage, networkInfo, threshold) {
        const thresholdFormatted = formatFileSize(threshold);
        const warningHtml = `
            <div class="data-usage-warning alert alert-danger" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>⚠️ 대용량 파일 업로드</strong><br>
                        <small>
                            파일 크기: ${dataUsage.originalSize} | 
                            예상 데이터 사용량: ${dataUsage.totalDataUsage} | 
                            권장 한도: ${thresholdFormatted}
                        </small>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <strong>네트워크:</strong> ${networkInfo.effectiveType.toUpperCase()}
                        ${networkInfo.saveData ? ' | 데이터 절약 모드 활성화' : ''}
                    </small>
                </div>
            </div>
        `;
        
        // 경고 표시
        const fileInputContainer = document.querySelector('.file-input-container');
        if (fileInputContainer && !document.querySelector('.data-usage-warning')) {
            fileInputContainer.insertAdjacentHTML('beforebegin', warningHtml);
        }
    }

    // 📈 데이터 사용량 추적 시작
    function startDataUsageTracking(fileSize, networkInfo) {
        const trackingData = {
            startTime: Date.now(),
            fileSize: fileSize,
            networkType: networkInfo.effectiveType,
            estimatedUsage: fileSize * 0.8 * 1.1, // 압축 + 오버헤드
            isMobileData: networkInfo.type === 'cellular'
        };
        
        // 로컬 스토리지에 추적 데이터 저장
        try {
            localStorage.setItem('data_usage_tracking', JSON.stringify(trackingData));
        } catch (e) {
            console.warn('데이터 사용량 추적 저장 실패:', e);
        }
        
        // 주기적으로 데이터 사용량 업데이트
        const trackingInterval = setInterval(() => {
            updateDataUsageProgress(trackingData);
        }, 5000); // 5초마다 업데이트
        
        // 업로드 완료 시 추적 정리
        window.addEventListener('uploadComplete', () => {
            clearInterval(trackingInterval);
            finalizeDataUsageTracking(trackingData);
        });
    }

    // 📊 데이터 사용량 진행률 업데이트
    function updateDataUsageProgress(trackingData) {
        const elapsed = Date.now() - trackingData.startTime;
        const elapsedMinutes = Math.round(elapsed / 60000);
        
        // 예상 업로드 시간 계산 (네트워크 속도 기반)
        const estimatedUploadTime = estimateUploadTime(trackingData.fileSize, trackingData.networkType);
        const progress = Math.min((elapsed / estimatedUploadTime) * 100, 95); // 최대 95%까지
        
        console.log('📊 데이터 사용량 추적:', {
            진행률: Math.round(progress) + '%',
            경과시간: elapsedMinutes + '분',
            예상완료: Math.round((estimatedUploadTime - elapsed) / 60000) + '분 후'
        });
    }

    // ⏱️ 업로드 시간 추정
    function estimateUploadTime(fileSize, networkType) {
        const speeds = {
            'slow-2g': 0.05, // 50KB/s
            '2g': 0.25, // 250KB/s
            '3g': 0.75, // 750KB/s
            '4g': 2.5 // 2.5MB/s
        };
        
        const speed = speeds[networkType] || speeds['4g']; // MB/s
        return (fileSize / (speed * 1024 * 1024)) * 1000; // 밀리초
    }

    // ✅ 데이터 사용량 추적 완료
    function finalizeDataUsageTracking(trackingData) {
        const totalTime = Date.now() - trackingData.startTime;
        const actualUsage = trackingData.estimatedUsage;
        
        const finalData = {
            ...trackingData,
            totalTime: totalTime,
            actualUsage: actualUsage,
            completedAt: new Date().toISOString()
        };
        
        // 최종 데이터 저장
        try {
            const existingData = JSON.parse(localStorage.getItem('data_usage_history') || '[]');
            existingData.push(finalData);
            
            // 최근 10개만 유지
            if (existingData.length > 10) {
                existingData.splice(0, existingData.length - 10);
            }
            
            localStorage.setItem('data_usage_history', JSON.stringify(existingData));
            localStorage.removeItem('data_usage_tracking');
            
            console.log('✅ 데이터 사용량 추적 완료:', finalData);
        } catch (e) {
            console.warn('데이터 사용량 추적 완료 저장 실패:', e);
        }
    }
});
</script>
@endsection 

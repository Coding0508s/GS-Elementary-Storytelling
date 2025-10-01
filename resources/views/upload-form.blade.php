@extends('layouts.app')

@section('title', 'GS Elementary Speech Contest')

@section('content')
<div class="progress-indicator">
    <div class="progress-step inactive">1</div>
    <div class="progress-line"></div>
    <div class="progress-step active">2</div>
    <div class="progress-line"></div>
    <div class="progress-step inactive">3</div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-10">
        <div class="text-center mb-2">
            <h2><i class="bi bi-cloud-upload"></i> 영상 업로드</h2>
            <p class="text-muted">학생 정보와 Unit 영상을 업로드해주세요.</p>
        </div>

        <form id="upload-form">
            @csrf
            
            <!-- 학생 기본 정보 -->
            <div class="card mb-2">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person"></i> 학생 기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label for="region" class="form-label">거주 지역 <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-6">
                                    <select class="form-control" id="province" name="province" required>
                                        <option value="">시/도 선택</option>
                                        @foreach(array_keys(\App\Models\VideoSubmission::REGIONS) as $province)
                                            <option value="{{ $province }}" {{ old('province') == $province ? 'selected' : '' }}>
                                                {{ $province }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select class="form-control" id="city" name="city" required disabled>
                                        <option value="">시/군/구 선택</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" id="region" name="region" value="{{ old('region') }}">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="institution_name" class="form-label">기관명(예:용인000) <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input type="text" 
                                       class="form-control" 
                                       id="institution_name" 
                                       name="institution_name" 
                                       value="{{ old('institution_name') }}" 
                                       placeholder="기관명을 입력하거나 선택해주세요"
                                       autocomplete="off"
                                       required>
                                <div id="institution_suggestions" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="display: none; z-index: 1000; max-height: 200px; overflow-y: auto;">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label for="class_name" class="form-label">반 이름 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="class_name" 
                                   name="class_name" 
                                   value="{{ old('class_name') }}" 
                                   placeholder="예: London, 감사반"
                                   required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="grade" class="form-label">학년 <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade" name="grade" required>
                                <option value="">학년을 선택하세요</option>
                                <option value=" 예비 초 1학년" {{ old('grade') == '1학년' ? 'selected' : '' }}>예비 초 1학년</option>
                               <!--  <option value="2학년" {{ old('grade') == '2학년' ? 'selected' : '' }}>2학년</option>
                                <option value="3학년" {{ old('grade') == '3학년' ? 'selected' : '' }}>3학년</option>
                                <option value="4학년" {{ old('grade') == '4학년' ? 'selected' : '' }}>4학년</option>
                                <option value="5학년" {{ old('grade') == '5학년' ? 'selected' : '' }}>5학년</option>
                                <option value="6학년" {{ old('grade') == '6학년' ? 'selected' : '' }}>6학년</option> -->
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label for="student_name_korean" class="form-label">학생 이름 (한글) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="student_name_korean" 
                                   name="student_name_korean" 
                                   value="{{ old('student_name_korean') }}" 
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="student_name_english" class="form-label">학생 이름 (영어) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="student_name_english" 
                                   name="student_name_english" 
                                   value="{{ old('student_name_english') }}" 
                                   placeholder="예: John"
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label for="age" class="form-label">나이 (2019년생만 참여가능합니다.) <span class="text-danger">*</span></label>
                            <select class="form-control" id="age" name="age" required>
                                <option value="">나이를 선택하세요</option>
                                <!-- <option value="5" {{ old('age') == '5' ? 'selected' : '' }}>5세</option> -->
                                <!-- <option value="6" {{ old('age') == '6' ? 'selected' : '' }}>6세</option> -->
                                <option value="7" {{ old('age') == '7' ? 'selected' : '' }}>7세</option>
                                <!-- <option value="8" {{ old('age') == '8' ? 'selected' : '' }}>8세</option> -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="unit_topic" class="form-label">스토리의 제목을 입력해주세요. <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="unit_topic" 
                                   name="unit_topic" 
                                   value="{{ old('unit_topic') }}" 
                                   placeholder="예: Unit 5 - My Family"
                                   required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 학부모 정보 -->
            <div class="card mb-2">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-people"></i> 학부모 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <label for="parent_name" class="form-label">학부모 성함 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="parent_name" 
                                   name="parent_name" 
                                   value="{{ old('parent_name') }}" 
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                        <div class="col-md-7 mb-2">
                            <label for="parent_phone" class="form-label">학부모 전화번호 <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="tel" 
                                       class="form-control" 
                                       id="parent_phone" 
                                       name="parent_phone" 
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
                            <div class="form-text">업로드 완료 알림을 받을 연락처입니다.</div>
                        </div>
                    </div>
                    
                    <!-- OTP 인증 영역 -->
                    <div class="row" id="otp-verification-area" style="display: none;">
                        <div class="col-12 mb-3">
                            <div class="alert alert-info">
                                <h6 class="fw-bold mb-2">📱 휴대폰 인증</h6>
                                <p class="mb-2">입력하신 휴대폰 번호로 인증번호를 전송했습니다.</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="otp_code" class="form-label">인증번호 <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="otp_code" 
                                                   name="otp_code" 
                                                   placeholder="6자리 인증번호"
                                                   maxlength="6"
                                                   pattern="[0-9]{6}">
                                            <button type="button" 
                                                    class="btn btn-success" 
                                                    id="verify-otp-btn">
                                                인증확인
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <span id="otp-timer" class="text-warning"></span>
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
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i>
                                <strong>휴대폰 인증이 완료되었습니다!</strong>
                                <input type="hidden" id="verification_token" name="verification_token" value="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 비디오 업로드 -->
            <div class="card mb-2">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-camera-video"></i> 영상 파일 업로드</h5>
                </div>
                <div class="card-body">
                    <div class="file-upload-area" onclick="document.getElementById('video_file').click()">
                        <i class="bi bi-cloud-upload fs-2 text-muted mb-2"></i>
                        <h6>여기를 클릭하여 영상을 선택하거나 여기에 드래그하세요</h6>
                        <p class="text-muted">
                            지원 형식: MP4, MOV<br>
                            최대 크기: 1GB
                        </p>
                        <input type="file" 
                               class="d-none" 
                               id="video_file" 
                               name="video_file" 
                               accept=".mp4,.mov,.avi,.wmv,.flv,.webm,.mkv,video/mp4,video/quicktime,video/avi,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/webm,video/x-matroska"
                               required>
                        <div id="file-info" class="mt-3 d-none">
                            <div class="alert alert-info">
                                <i class="bi bi-file-earmark-play"></i>
                                <span id="file-name"></span>
                                <br>
                                <small>크기: <span id="file-size"></span></small>
                            </div>
                        </div>
                    </div>
                    
                    <div id="upload-progress" class="mt-3 d-none">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" 
                                 style="width: 0%">
                                <span id="progress-text">0%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 제출 버튼 -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="d-grid gap-2">
                        <button type="submit" 
                                class="btn btn-primary btn-lg"
                                id="submit-btn">
                            <i class="bi bi-upload"></i> 제출하기
                        </button>
                    </div>
                    
                    <p class="text-muted mt-2 small">
                        <i class="bi bi-info-circle"></i> 
                        <b>영상 제출시 업로드에는 시간이 다소 소요될 수 있으니,</b><br>
                        <b>충분한 여유 시간을 두고 진행해주시기 바랍니다. </b>
                        <br>
                        업로드가 완료되면 입력하신 전화번호로 접수번호를 보내드립니다.
                    </p>
                    
                    <!-- 처음으로 가기 버튼 -->
                    <div class="mt-2">
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle-fill"></i> 취소하기
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<!-- S3 직접 업로드 라이브러리 -->
<script src="{{ asset('js/s3-upload.js') }}"></script>

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
    
    // 기관명 자동완성 기능
    const institutionInput = document.getElementById('institution_name');
    const suggestionsList = document.getElementById('institution_suggestions');
    let debounceTimer;
    
    institutionInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // 디바운스 적용 (300ms 지연)
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            fetchInstitutions(query);
        }, 300);
    });
    
    institutionInput.addEventListener('blur', function() {
        // 약간의 지연을 두어 클릭 이벤트가 처리되도록 함
        setTimeout(() => {
            hideSuggestions();
        }, 200);
    });
    
    institutionInput.addEventListener('focus', function() {
        // 포커스 시 항상 기관명 목록 표시 (검색어 길이 상관없이)
        fetchInstitutions(this.value.trim());
    });
    
    function fetchInstitutions(query) {
        fetch(`{{ route('api.institutions') }}?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                showSuggestions(data);
            })
            .catch(error => {
                console.error('기관명 검색 오류:', error);
                hideSuggestions();
            });
    }
    
    function showSuggestions(institutions) {
        suggestionsList.innerHTML = '';
        
        if (institutions.length === 0) {
            const query = institutionInput.value.trim();
            if (query.length === 0) {
                suggestionsList.innerHTML = '<div class="p-2 text-muted small">등록된 기관명이 없습니다. 새로운 기관명을 입력해주세요.</div>';
            } else {
                suggestionsList.innerHTML = '<div class="p-2 text-muted small">검색 결과가 없습니다. 새로운 기관명을 입력해주세요.</div>';
            }
        } else {
            institutions.forEach(institution => {
                const item = document.createElement('div');
                item.className = 'p-2 cursor-pointer border-bottom suggestion-item';
                item.style.cursor = 'pointer';
                item.textContent = institution;
                
                item.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = 'white';
                });
                
                item.addEventListener('click', function() {
                    institutionInput.value = institution;
                    hideSuggestions();
                    institutionInput.focus();
                });
                
                suggestionsList.appendChild(item);
            });
        }
        
        suggestionsList.style.display = 'block';
    }
    
    function hideSuggestions() {
        suggestionsList.style.display = 'none';
    }
    
    // 키보드 내비게이션 지원
    institutionInput.addEventListener('keydown', function(e) {
        const items = suggestionsList.querySelectorAll('.suggestion-item');
        const activeItem = suggestionsList.querySelector('.suggestion-item.active');
        let activeIndex = -1;
        
        if (activeItem) {
            activeIndex = Array.from(items).indexOf(activeItem);
        }
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                const nextIndex = (activeIndex + 1) % items.length;
                items[nextIndex].classList.add('active');
                items[nextIndex].style.backgroundColor = '#e9ecef';
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                const prevIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
                items[prevIndex].classList.add('active');
                items[prevIndex].style.backgroundColor = '#e9ecef';
            }
        } else if (e.key === 'Enter') {
            if (activeItem) {
                e.preventDefault();
                institutionInput.value = activeItem.textContent;
                hideSuggestions();
            }
        } else if (e.key === 'Escape') {
            hideSuggestions();
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
    
    // 파일 선택 시 정보 표시
    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // 파일 크기 체크 (2GB)
            const maxSize = 2 * 1024 * 1024 * 1024; // 2GB
            if (file.size > maxSize) {
                alert('파일 크기가 1GB를 초과합니다. 더 작은 파일을 선택해주세요.');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                return;
            }
            
            // 파일 형식 체크
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
    
    // 드래그 앤 드롭 기능
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
    
    // S3 직접 업로드 인스턴스 생성
    const s3Uploader = new S3DirectUpload();
    let uploadedFileInfo = null;

    // 폼 제출 시 S3 직접 업로드 처리
    document.getElementById('upload-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // OTP 검증 여부 체크
        if (!otpVerifyBtn.dataset.verified) {
            alert('휴대폰 인증을 완료해 주세요.');
            return;
        }

        const file = fileInput.files[0];
        if (!file) {
            alert('영상 파일을 선택해주세요.');
            return;
        }

        // 폼 데이터 수집
        const formData = new FormData(this);
        
        // 버튼 비활성화 및 UI 업데이트
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 업로드 중...';
        uploadProgress.classList.remove('d-none');
        
        try {
            // 사용자 정보 수집
            const userInfo = {
                institution_name: document.getElementById('institution_name').value,
                student_name_korean: document.getElementById('student_name_korean').value,
                grade: document.getElementById('grade').value
            };

            // S3에 파일 업로드 (로깅 최소화로 성능 향상)
            
            // Presigned URL 요청 (재시도 로직 포함)
            const presignedData = await fetchWithRetry('/api/s3/presigned-url', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    filename: file.name,
                    content_type: file.type,
                    file_size: file.size,
                    institution_name: userInfo.institution_name,
                    student_name_korean: userInfo.student_name_korean,
                    grade: userInfo.grade
                })
            }, 3, 1000); // 최대 3회 재시도, 1초 간격

            // ☁️ S3에 직접 업로드 (최적화됨)
            const xhr = new XMLHttpRequest();
            const uploadPromise = new Promise((resolve, reject) => {
                let lastUpdate = 0;
                
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = (e.loaded / e.total) * 100;
                        const now = Date.now();
                        
                        // UI 업데이트 (매번)
                        progressBar.style.width = percent + '%';
                        progressText.textContent = Math.round(percent) + '%';
                        
                        // 로깅 최소화 (1초마다 또는 25% 단위)
                        if (now - lastUpdate > 1000 || Math.round(percent) % 25 === 0) {
                            lastUpdate = now;
                        }
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 204 || xhr.status === 200) {
                        resolve({
                            s3_key: presignedData.s3_key,
                            url: presignedData.s3_url
                        });
                    } else {
                        reject(new Error('S3 업로드 실패'));
                    }
                });

                xhr.addEventListener('error', () => reject(new Error('네트워크 오류')));
                xhr.addEventListener('timeout', () => reject(new Error('업로드 타임아웃')));

                xhr.open('PUT', presignedData.presigned_url);
                xhr.timeout = 300000; // 5분
                xhr.setRequestHeader('Content-Type', file.type);
                xhr.send(file);
            });

            const uploadResult = await uploadPromise;

            // 업로드된 파일 정보를 폼에 추가 (최적화: notifyUploadComplete 생략)
            const s3Key = uploadResult.s3_key;
            const s3Url = uploadResult.url;
            
            if (!s3Key || !s3Url) {
                throw new Error('S3 업로드 정보가 불완전합니다.');
            }
            
            // ⚡ 새로운 FormData 생성 (파일 제외, 필수 정보만)
            const serverFormData = new FormData();
            
            // 폼 필드만 추가 (파일 제외)
            serverFormData.append('region', formData.get('region'));
            serverFormData.append('institution_name', formData.get('institution_name'));
            serverFormData.append('class_name', formData.get('class_name'));
            serverFormData.append('student_name_korean', formData.get('student_name_korean'));
            serverFormData.append('student_name_english', formData.get('student_name_english'));
            serverFormData.append('grade', formData.get('grade'));
            serverFormData.append('age', formData.get('age'));
            serverFormData.append('parent_name', formData.get('parent_name'));
            serverFormData.append('parent_phone', formData.get('parent_phone'));
            serverFormData.append('unit_topic', formData.get('unit_topic') || '');
            
            // S3 업로드 정보 추가
            serverFormData.append('s3_key', s3Key);
            serverFormData.append('s3_url', s3Url);
            serverFormData.append('file_size', file.size);
            serverFormData.append('content_type', file.type);
            
            console.log('서버 제출 시작 (파일 제외, 메타데이터만)...');
            
            // ⚡ 서버에 최적화된 데이터만 제출
            const response = await fetch('{{ route("upload.process") }}', {
                method: 'POST',
                body: serverFormData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                try {
                    const errorData = JSON.parse(errorText);
                    if (errorData.errors) {
                        const errorMessages = Object.entries(errorData.errors)
                            .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
                            .join('\n');
                        throw new Error(`입력 데이터 오류:\n${errorMessages}`);
                    }
                    throw new Error(errorData.message || '서버 오류');
                } catch (parseError) {
                    throw new Error(`서버 오류 (${response.status})`);
                }
            }

            const result = await response.json();
            
            // ✅ 성공 시 즉시 리다이렉트
            if (result.success) {
                window.location.href = result.redirect_url || '{{ route("upload.success") }}';
            } else {
                throw new Error(result.message || '업로드 실패');
            }

        } catch (error) {
            console.error('업로드 실패:', error);
            alert('업로드 중 오류가 발생했습니다: ' + error.message);
            
            // UI 복원
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-upload"></i> 제출하기';
            uploadProgress.classList.add('d-none');
            progressBar.style.width = '0%';
            progressText.textContent = '0%';
        }
    });

    // OTP 타이머 시작 함수
    function startOtpTimer(duration) {
        if (otpCountdown) {
            clearInterval(otpCountdown);
        }
        
        let timeLeft = duration;
        otpTimer.textContent = `남은 시간: ${Math.floor(timeLeft / 60)}:${String(timeLeft % 60).padStart(2, '0')}`;
        
        otpCountdown = setInterval(() => {
            timeLeft--;
            otpTimer.textContent = `남은 시간: ${Math.floor(timeLeft / 60)}:${String(timeLeft % 60).padStart(2, '0')}`;
            
            if (timeLeft <= 0) {
                clearInterval(otpCountdown);
                otpTimer.textContent = '인증 시간이 만료되었습니다.';
                resendOtpBtn.style.display = 'inline';
            }
        }, 1000);
    }
    
    // OTP: 인증번호 발송
    async function sendOtp() {
        const phone = document.getElementById('parent_phone').value.trim();
        if (!phone) {
            alert('전화번호를 입력해 주세요.');
            return;
        }
        
        otpSendBtn.disabled = true;
        otpSendBtn.textContent = '발송 중...';
        
        try {
            const resp = await fetch('{{ route("api.otp.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ parent_phone: phone })
            });
            const data = await resp.json();
            
            if (!resp.ok || !data.success) {
                throw new Error(data.message || '발송 실패');
            }
            
            // OTP 인증 영역 표시
            otpVerificationArea.style.display = 'block';
            otpCodeInput.value = '';
            otpCodeInput.disabled = false;
            resendOtpBtn.style.display = 'none';
            
            // 5분 타이머 시작
            startOtpTimer(300); // 5분 = 300초
            
            alert('인증번호가 발송되었습니다. 5분 내에 입력해주세요.');
            
        } catch (err) {
            alert('인증번호 발송 오류: ' + err.message);
        } finally {
            otpSendBtn.disabled = false;
            otpSendBtn.textContent = '인증번호 전송';
        }
    }
    
    otpSendBtn.addEventListener('click', sendOtp);
    resendOtpBtn.addEventListener('click', sendOtp);

    // OTP: 인증 확인
    otpVerifyBtn.addEventListener('click', async function() {
        const phone = document.getElementById('parent_phone').value.trim();
        const code = otpCodeInput.value.trim();
        
        if (!code || code.length !== 6) {
            alert('6자리 인증번호를 입력해 주세요.');
            return;
        }
        
        otpVerifyBtn.disabled = true;
        otpVerifyBtn.textContent = '확인 중...';
        
        try {
            const resp = await fetch('{{ route("api.otp.verify") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ parent_phone: phone, code })
            });
            const data = await resp.json();
            
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
            
            // 전화번호 입력 필드 비활성화
            document.getElementById('parent_phone').disabled = true;
            
        } catch (err) {
            alert('인증 실패: ' + err.message);
        } finally {
            otpVerifyBtn.disabled = false;
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
});
</script>
@endsection 
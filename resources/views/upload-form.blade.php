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
    <div class="col-md-10">
        <div class="text-center mb-4">
            <h2><i class="bi bi-cloud-upload"></i> 비디오 업로드</h2>
            <p class="text-muted">학생 정보와 Unit 비디오를 업로드해주세요.</p>
        </div>

        <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data" id="upload-form">
            @csrf
            
            <!-- 학생 기본 정보 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-person"></i> 학생 기본 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="region" class="form-label">거주 지역 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="region" 
                                   name="region" 
                                   value="{{ old('region') }}" 
                                   placeholder="예: 서울"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="institution_name" class="form-label">기관명 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="institution_name" 
                                   name="institution_name" 
                                   value="{{ old('institution_name') }}" 
                                   placeholder="예: GS어학원"
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="class_name" class="form-label">반 이름 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="class_name" 
                                   name="class_name" 
                                   value="{{ old('class_name') }}" 
                                   placeholder="예: London, 감사반"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="grade" class="form-label">학년 <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade" name="grade" required>
                                <option value="">학년을 선택하세요</option>
                                <option value="1학년" {{ old('grade') == '1학년' ? 'selected' : '' }}>1학년</option>
                                <option value="2학년" {{ old('grade') == '2학년' ? 'selected' : '' }}>2학년</option>
                                <option value="3학년" {{ old('grade') == '3학년' ? 'selected' : '' }}>3학년</option>
                                <option value="4학년" {{ old('grade') == '4학년' ? 'selected' : '' }}>4학년</option>
                                <option value="5학년" {{ old('grade') == '5학년' ? 'selected' : '' }}>5학년</option>
                                <option value="6학년" {{ old('grade') == '6학년' ? 'selected' : '' }}>6학년</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_name_korean" class="form-label">학생 이름 (한글) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="student_name_korean" 
                                   name="student_name_korean" 
                                   value="{{ old('student_name_korean') }}" 
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="student_name_english" class="form-label">학생 이름 (영어) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="student_name_english" 
                                   name="student_name_english" 
                                   value="{{ old('student_name_english') }}" 
                                   placeholder="예: John Doe"
                                   required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">나이 <span class="text-danger">*</span></label>
                            <input type="number" 
                                   class="form-control" 
                                   id="age" 
                                   name="age" 
                                   value="{{ old('age') }}" 
                                   min="5" 
                                   max="15"
                                   placeholder="예: 10"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="unit_topic" class="form-label">Unit 주제</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="unit_topic" 
                                   name="unit_topic" 
                                   value="{{ old('unit_topic') }}" 
                                   placeholder="예: Unit 5 - My Family">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 학부모 정보 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-people"></i> 학부모 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="parent_name" class="form-label">학부모 성함 <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="parent_name" 
                                   name="parent_name" 
                                   value="{{ old('parent_name') }}" 
                                   placeholder="예: 김철수"
                                   required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="parent_phone" class="form-label">학부모 전화번호 <span class="text-danger">*</span></label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="parent_phone" 
                                   name="parent_phone" 
                                   value="{{ old('parent_phone') }}" 
                                   placeholder="010-1234-5678"
                                   pattern="[0-9]{2,3}-[0-9]{3,4}-[0-9]{4}"
                                   required>
                            <div class="form-text">업로드 완료 알림을 받을 연락처입니다.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 비디오 업로드 -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-camera-video"></i> 비디오 파일 업로드</h5>
                </div>
                <div class="card-body">
                    <div class="file-upload-area" onclick="document.getElementById('video_file').click()">
                        <i class="bi bi-cloud-upload fs-1 text-muted mb-3"></i>
                        <h5>비디오 파일을 선택하거나 여기에 드래그하세요</h5>
                        <p class="text-muted">
                            지원 형식: MP4, MOV<br>
                            최대 크기: 100MB
                        </p>
                        <input type="file" 
                               class="d-none" 
                               id="video_file" 
                               name="video_file" 
                               accept=".mp4,.mov,video/mp4,video/quicktime"
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
                            <i class="bi bi-upload"></i> 비디오 업로드
                        </button>
                    </div>
                    
                    <p class="text-muted mt-3 small">
                        <i class="bi bi-info-circle"></i> 
                        업로드가 완료되면 입력하신 전화번호로 알림을 보내드립니다.
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
    const fileInput = document.getElementById('video_file');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');
    const fileSize = document.getElementById('file-size');
    const uploadArea = document.querySelector('.file-upload-area');
    const submitBtn = document.getElementById('submit-btn');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.querySelector('.progress-bar');
    const progressText = document.getElementById('progress-text');
    
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
            // 파일 크기 체크 (100MB)
            const maxSize = 100 * 1024 * 1024; // 100MB
            if (file.size > maxSize) {
                alert('파일 크기가 100MB를 초과합니다. 더 작은 파일을 선택해주세요.');
                fileInput.value = '';
                fileInfo.classList.add('d-none');
                return;
            }
            
            // 파일 형식 체크
            const allowedTypes = ['video/mp4', 'video/quicktime'];
            if (!allowedTypes.includes(file.type)) {
                alert('MP4 또는 MOV 형식의 파일만 업로드 가능합니다.');
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
    
    // 폼 제출 시 진행률 표시
    document.getElementById('upload-form').addEventListener('submit', function(e) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 업로드 중...';
        uploadProgress.classList.remove('d-none');
        
        // 가상의 진행률 표시 (실제로는 서버에서 처리)
        let progress = 0;
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) {
                progress = 90;
                clearInterval(interval);
            }
            progressBar.style.width = progress + '%';
            progressText.textContent = Math.round(progress) + '%';
        }, 200);
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
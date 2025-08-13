@extends('admin.layout')

@section('title', '영상 심사')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-clipboard-check"></i> 영상 심사</h1>
    <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle"></i> 다음 오류를 확인해주세요:
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- 학생 정보 및 영상 정보 -->
<div class="row mb-4">
    <!-- 학생 정보 -->
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person-circle"></i> 학생 정보</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">학생명</label>
                        <p class="mb-0 fw-bold">{{ $submission->student_name_korean }} ({{ $submission->student_name_english }})</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">기관명</label>
                        <p class="mb-0">{{ $submission->institution_name }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">반</label>
                        <p class="mb-0">{{ $submission->class_name }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">학년/나이</label>
                        <p class="mb-0">{{ $submission->grade }} ({{ $submission->age }}세)</p>
                    </div>
                    @if($submission->unit_topic)
                    <div class="col-12">
                        <label class="form-label text-muted small">Unit 주제</label>
                        <p class="mb-0 fw-bold text-primary">{{ $submission->unit_topic }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- 영상 정보 -->
    <div class="col-md-6 mb-3">
        <div class="card admin-card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-camera-video"></i> 영상 정보</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">파일명</label>
                        <p class="mb-0">{{ $submission->video_file_name }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">파일 크기</label>
                        <p class="mb-0">{{ $submission->getFormattedFileSizeAttribute() }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">파일 형식</label>
                        <p class="mb-0">{{ strtoupper($submission->video_file_type) }}</p>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label text-muted small">업로드 일시</label>
                        <p class="mb-0">{{ $submission->created_at->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 영상 플레이어 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-play-circle"></i> 영상 재생</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label text-muted">영상 파일</label>
            <div class="border rounded overflow-hidden">
                <!-- 영상 플레이어 -->
                <div id="video-container" class="position-relative" style="background: #000; min-height: 300px;">
                    <div id="video-loading" class="d-flex align-items-center justify-content-center h-100 text-white">
                        <div class="text-center">
                            <div class="spinner-border mb-3" role="status">
                                <span class="visually-hidden">로딩중...</span>
                            </div>
                            <p>재생 버튼을 클릭해 주세요.</p>
                        </div>
                    </div>
                    <video id="video-player" class="w-100 h-100" controls style="display: none; max-height: 400px;">
                        <source src="" type="video/mp4">
                        <p>브라우저가 영상 재생을 지원하지 않습니다.</p>
                    </video>
                </div>
                
                <!-- 영상 컨트롤 패널 -->
                <div class="p-3 bg-light border-top">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1 fw-bold">{{ $submission->video_file_name }}</h6>
                            <small class="text-muted">
                                {{ strtoupper($submission->video_file_type) }} · 
                                {{ $submission->getFormattedFileSizeAttribute() }} · 
                                업로드: {{ $submission->created_at->format('m/d H:i') }}
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="load-video-btn">
                                    <i class="bi bi-play-fill"></i> 재생
                                </button>
                                <a href="{{ route('judge.video.download', $assignment->id) }}" 
                                   class="btn btn-outline-success btn-sm"
                                   target="_blank">
                                    <i class="bi bi-download"></i> 다운로드
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 영상 상태 표시 -->
                    <div id="video-status" class="mt-2" style="display: none;">
                        <div class="alert alert-info alert-sm mb-0">
                            <i class="bi bi-info-circle"></i>
                            <span id="status-text">영상이 준비되었습니다.</span>
                        </div>
                    </div>
                    
                    <!-- 에러 표시 -->
                    <div id="video-error" class="mt-2" style="display: none;">
                        <div class="alert alert-danger alert-sm mb-0">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span id="error-text">영상을 로드할 수 없습니다.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($nextAssignment)
<!-- 다음 영상 안내 -->
<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <div>
        <strong>다음 영상:</strong> {{ $nextAssignment->videoSubmission->student_name_korean }}
        ({{ $nextAssignment->videoSubmission->grade }} / {{ $nextAssignment->videoSubmission->institution_name }})
        <br>
        <small class="text-muted">평가 완료 후 자동으로 다음 영상으로 이동합니다.</small>
    </div>
</div>
@endif

<!-- 심사 폼 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-star"></i> 심사 평가
            @if($assignment->evaluation)
                <span class="badge bg-success ms-2">수정 모드</span>
            @else
                <span class="badge bg-warning ms-2">신규 심사</span>
            @endif
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('judge.evaluation.store', $assignment->id) }}" method="POST" id="evaluation-form">
            @csrf
            
            <!-- 평가 기준 -->
            <div class="row mb-4">
                @php
                    $criteria = [
                        'pronunciation_score' => ['title' => '발음·억양', 'icon' => 'bi-mic'],
                        'vocabulary_score' => ['title' => '어휘·표현', 'icon' => 'bi-book'],
                        'fluency_score' => ['title' => '유창성', 'icon' => 'bi-chat-dots'],
                        'confidence_score' => ['title' => '자신감', 'icon' => 'bi-emoji-smile']
                    ];
                @endphp
                
                @foreach($criteria as $field => $info)
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <h6 class="card-title">
                                <i class="{{ $info['icon'] }}"></i> {{ $info['title'] }}
                            </h6>
                            
                            <div class="mb-2">
                                <div class="range-wrap">
                                    <input type="range" 
                                           class="form-range w-100" 
                                           id="{{ $field }}_range"
                                           min="0" 
                                           max="10" 
                                           step="1"
                                           value="{{ old($field, $assignment->evaluation->$field ?? 0) }}">
                                    <div class="range-ticks mt-1" style="display: flex; justify-content: space-between; padding: 0 8px; margin-top: 4px;">
                                        @for($i = 0; $i <= 10; $i++)
                                            <span class="tick" style="font-size: 9px; color: #6c757d; font-weight: 500; text-align: center;">{{ $i }}</span>
                                        @endfor
                                    </div>
                                </div>
                                <input type="number" 
                                       class="form-control score-input mt-2" 
                                       id="{{ $field }}"
                                       name="{{ $field }}"
                                       min="0" 
                                       max="10" 
                                       value="{{ old($field, $assignment->evaluation->$field ?? '') }}"
                                       placeholder="0-10"
                                       required>
                            </div>
                            
                            <!-- 점수 가이드 -->
                            <div class="text-muted" style="font-size: 0.7rem;">
                                0-2: 매우미흡 | 3-4: 미흡<br>
                                5-6: 보통 | 7-8: 양호 | 9-10: 우수
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- 총점 표시 -->
            <div class="card mb-4">
                <div class="card-body text-center bg-primary bg-opacity-10">
                    <h5 class="card-title">총점</h5>
                    <div class="display-6 fw-bold text-primary">
                        <span id="total-score">{{ $assignment->evaluation ? $assignment->evaluation->total_score : 0 }}</span> / 40점
                    </div>
                    <div class="mt-2">
                        <span id="grade-badge" class="badge fs-6">등급 계산 중...</span>
                    </div>
                </div>
            </div>
            
            <!-- 심사 코멘트 -->
            <div class="mb-4">
                <label for="comments" class="form-label">
                    <i class="bi bi-chat-text"></i> 심사 코멘트
                </label>
                <textarea class="form-control" 
                          id="comments" 
                          name="comments" 
                          rows="4"
                          placeholder="학생의 발표에 대한 구체적인 피드백을 입력해주세요...">{{ old('comments', $assignment->evaluation->comments ?? '') }}</textarea>
                <div class="form-text">
                    학생과 학부모에게 도움이 될 수 있는 피드백을 남겨주세요.
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="d-flex gap-3 justify-content-end">
                <a href="{{ route('judge.video.list') }}" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 취소
                </a>
                <button type="submit" class="btn btn-admin">
                    @if($assignment->evaluation)
                        <i class="bi bi-pencil"></i> 심사 결과 수정
                    @else
                        <i class="bi bi-check-circle"></i> 심사 완료
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 영상 관련 요소들
    const loadVideoBtn = document.getElementById('load-video-btn');
    const videoPlayer = document.getElementById('video-player');
    const videoLoading = document.getElementById('video-loading');
    const videoStatus = document.getElementById('video-status');
    const videoError = document.getElementById('video-error');
    const statusText = document.getElementById('status-text');
    const errorText = document.getElementById('error-text');

    // 로딩 오버레이 숨김/제거 유틸
    function hideLoadingOverlay() {
        if (videoLoading) {
            videoLoading.style.display = 'none';
            // 혹시 남아있지 않도록 DOM에서 제거
            if (videoLoading.parentNode) {
                try { videoLoading.parentNode.removeChild(videoLoading); } catch (_) {}
            }
        }
    }
    
    // 심사 관련 요소들
    const scoreInputs = document.querySelectorAll('input[type="number"]');
    const ranges = document.querySelectorAll('input[type="range"]');
    const totalScoreElement = document.getElementById('total-score');
    const gradeBadge = document.getElementById('grade-badge');
    
        // 영상 로드 버튼 클릭
    loadVideoBtn.addEventListener('click', function() {
        loadVideo();
    });
    
    // 영상 로드 함수
    function loadVideo() {
        loadVideoBtn.disabled = true;
        loadVideoBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 로딩중...';
        
        // 재생 버튼 클릭과 동시에 로딩 문구 숨기기
        hideLoadingOverlay();
        videoPlayer.style.display = 'block';
        videoStatus.style.display = 'none';
        videoError.style.display = 'none';
        
        // S3 스트리밍 URL 요청
        fetch('{{ route("judge.video.stream", $assignment->id) }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 영상 URL 설정
                    const source = videoPlayer.querySelector('source');
                    source.src = data.url;
                    videoPlayer.load();
                    
                    // 영상 로드 완료 시
                    videoPlayer.addEventListener('loadeddata', function() {
                        hideLoadingOverlay();
                        videoStatus.style.display = 'block';
                        statusText.textContent = `영상이 로드되었습니다. (${data.size})`;
                        
                        loadVideoBtn.disabled = false;
                        loadVideoBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 새로고침';
                    });
                    // 사용자 제스처로 재생 시작될 때도 로딩 오버레이 강제 숨김
                    videoPlayer.addEventListener('play', hideLoadingOverlay, { once: true });
                    
                    // 영상 로드 에러 시
                    videoPlayer.addEventListener('error', function() {
                        showVideoError('영상을 재생할 수 없습니다.');
                    });
                    
                } else {
                    showVideoError(data.error || '영상 URL을 가져올 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showVideoError('네트워크 오류가 발생했습니다.');
            });
    }
    
    // 영상 에러 표시
    function showVideoError(message) {
        hideLoadingOverlay();
        videoPlayer.style.display = 'none';
        videoError.style.display = 'block';
        errorText.textContent = message;
        
        loadVideoBtn.disabled = false;
        loadVideoBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 다시 시도';
    }
    
    // 점수 입력과 슬라이더 동기화
    scoreInputs.forEach((input, index) => {
        const range = ranges[index];
        
        // 숫자 입력 시 슬라이더 업데이트
        input.addEventListener('input', function() {
            const value = Math.max(0, Math.min(10, parseInt(this.value) || 0));
            this.value = value;
            range.value = value;
            calculateTotal();
        });
        
        // 슬라이더 변경 시 숫자 입력 업데이트
        range.addEventListener('input', function() {
            input.value = this.value;
            calculateTotal();
        });
    });
    
    // 총점 계산
    function calculateTotal() {
        let total = 0;
        
        scoreInputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        
        totalScoreElement.textContent = total;
        updateGrade(total);
    }
    
    // 등급 업데이트 (0-40점 기준)
    function updateGrade(total) {
        let grade, className;
        
        if (total >= 36) {
            grade = '우수';
            className = 'bg-success text-white';
        } else if (total >= 31) {
            grade = '양호';
            className = 'bg-primary text-white';
        } else if (total >= 26) {
            grade = '보통';
            className = 'bg-info text-white';
        } else if (total >= 21) {
            grade = '미흡';
            className = 'bg-warning text-dark';
        } else {
            grade = '매우 미흡';
            className = 'bg-danger text-white';
        }
        
        gradeBadge.textContent = grade;
        gradeBadge.className = `badge ${className} fs-6`;
    }
    
    // 폼 제출 시 확인
    document.getElementById('evaluation-form').addEventListener('submit', function(e) {
        const scores = Array.from(scoreInputs).map(input => parseInt(input.value));
        const hasInvalidScore = scores.some(score => score < 0 || score > 10 || isNaN(score));
        
        if (hasInvalidScore) {
            e.preventDefault();
            alert('모든 점수는 0-10점 사이여야 합니다.');
            return;
        }
        
        const total = scores.reduce((sum, score) => sum + score, 0);
        const confirmMessage = `심사 결과를 저장하시겠습니까?\n\n총점: ${total}/40점\n` + 
                             `등급: ${gradeBadge.textContent}`;
        
        if (!confirm(confirmMessage)) {
            e.preventDefault();
        } else {
            // 제출 버튼 비활성화
            const submitBtn = document.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 저장 중...';
            }
        }
    });
    
    // 초기 총점 계산
    calculateTotal();
});
</script>
@endsection 
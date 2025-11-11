@extends('admin.layout')

@section('title', '영상 심사')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-clipboard-check"></i> 영상 심사</h1>
    <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="success-alert">
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

@if(isset($otherEvaluation) && $otherEvaluation)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> 
        <strong>주의:</strong> 이 영상은 이미 다른 심사위원에 의해 채점되었습니다. 
        현재 시스템에서는 1개의 영상을 1명의 심사위원만 채점할 수 있습니다.
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
                    <video id="video-player" class="w-100" controls style="display: none; aspect-ratio: 16/9; max-height: 400px;">
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
                            <div class="btn-group-vertical btn-group-sm" role="group">
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
                                
                                <!-- AI 평가 버튼 -->
                                @php
                                    // auth('admin')->id()가 문자열을 반환하는 경우 숫자 ID로 변환
                                    $currentAdminId = auth('admin')->user()->id ?? auth('admin')->id();
                                    
                                    // 관리자가 일괄 채점한 AI 평가가 있는지 확인 (우선순위)
                                    $batchAiEval = $submission->aiEvaluations->where('processing_status', 'completed')->first();
                                    
                                    // 현재 심사위원의 AI 평가
                                    $currentAiEval = $submission->aiEvaluations->where('admin_id', $currentAdminId)->first();
                                    
                                    // 디버깅용 - 브라우저 콘솔에서 확인 가능
                                    echo "<script>console.log('=== PHP 디버깅 ===');</script>";
                                    echo "<script>console.log('PHP currentAdminId: " . $currentAdminId . "');</script>";
                                    echo "<script>console.log('PHP batchAiEval: " . ($batchAiEval ? 'exists' : 'null') . "');</script>";
                                    echo "<script>console.log('PHP currentAiEval: " . ($currentAiEval ? 'exists' : 'null') . "');</script>";
                                @endphp
                                
                                @if($batchAiEval)
                                    <!-- 관리자 일괄 채점 결과가 있는 경우 -->
                                    <div class="btn-group mt-1" role="group">
                                        <button type="button" 
                                                class="btn btn-success btn-sm view-ai-result-btn"
                                                data-ai-evaluation-id="{{ $batchAiEval->id }}"
                                                title="관리자 일괄 채점 AI 평가 결과 보기">
                                            <i class="bi bi-robot"></i> AI 평가 결과
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle"></i> 
                                            관리자가 일괄 채점한 AI 평가 결과가 자동으로 반영됩니다.
                                        </small>
                                    </div>
                                @else
                                    <!-- 관리자 일괄 채점 결과가 없는 경우 기존 로직 -->
                                    <div class="btn-group mt-1" role="group">
                                        @if(!$currentAiEval || $currentAiEval->processing_status === 'failed')
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-sm ai-evaluate-btn"
                                                    data-assignment-id="{{ $assignment->id }}"
                                                    title="AI로 평가하기">
                                                <i class="bi bi-robot"></i> AI 평가
                                            </button>
                                        @elseif($currentAiEval->processing_status === 'processing')
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm" 
                                                    disabled>
                                                <i class="bi bi-arrow-clockwise"></i> 처리중...
                                            </button>
                                        @elseif($currentAiEval->processing_status === 'completed')
                                            <button type="button" 
                                                    class="btn btn-success btn-sm view-ai-result-btn"
                                                    data-ai-evaluation-id="{{ $currentAiEval->id }}"
                                                    title="AI 평가 결과 보기">
                                                <i class="bi bi-check-circle"></i> 결과 보기
                                            </button>
                                        @endif
                                    </div>
                                @endif
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
                        'pronunciation_score' => ['title' => '발음·억양', 'icon' => 'bi-mic', 'description' => 'AI가 평가한 점수입니다.'],
                        'vocabulary_score' => ['title' => '어휘·표현', 'icon' => 'bi-book', 'description' => 'AI가 평가한 점수입니다.'],
                        'fluency_score' => ['title' => '유창성', 'icon' => 'bi-chat-dots', 'description' => 'AI가 평가한 점수입니다.'],
                        'confidence_score' => ['title' => '자신감, 긍정적이고 밝은 태도', 'icon' => 'bi-emoji-smile', 'description' => '☞심사 가이드 : 자신있고 명확히 들리는 목소리로 표현하는 지 여부'],
                        'topic_connection_score' => ['title' => '주제와 발표 내용과의 연결성', 'icon' => 'bi-link-45deg', 'description' => '☞심사 가이드: 원 story 그대로의 문장과 단어를 정확히 전달했는 지 여부'],
                        'structure_flow_score' => ['title' => '자연스러운 구성과 흐름', 'icon' => 'bi-arrow-down-up', 'description' => '☞심사 가이드: 말할 때 흐름이 끊기지 않고 자연스러운 흐름과 목소리 톤으로 표현했는 지 여부'],
                        'creativity_score' => ['title' => '창의적 내용', 'icon' => 'bi-lightbulb', 'description' => '심사 가이드: 소품이나 visual aid, 율동 및 몸짓 등으로 사용 하여 말하는 내용의 이해와 집중을 도운 경우']
                    ];
                @endphp
                
                @foreach($criteria as $field => $info)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
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
                                       required
                                       {{ (isset($otherEvaluation) && $otherEvaluation) ? 'disabled' : '' }}>
                            </div>
                            <div class="text-admin-text" style="font-size: 0.8rem;">
                                @if(isset($info['description']) && !empty($info['description']))
                                    {{ $info['description'] }}
                                @else
                                    AI가 평가한 점수입니다.
                                @endif
                            </div>
                            <!-- 점수 가이드 -->
                            <!-- <div class="text-muted" style="font-size: 0.7rem;">
                                각 항목별 0-10점으로 평가해주세요
                            </div> -->
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
                        <span id="total-score">{{ $assignment->evaluation ? $assignment->evaluation->total_score : 0 }}</span> / 70점
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
                          placeholder="학생의 발표에 대한 구체적인 피드백을 입력해주세요..."
                          {{ (isset($otherEvaluation) && $otherEvaluation) ? 'disabled' : '' }}>{{ old('comments', $assignment->evaluation->comments ?? '') }}</textarea>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="d-flex gap-3 justify-content-end">
                <a href="{{ route('judge.video.list') }}" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 취소
                </a>
                <button type="submit" 
                        class="btn btn-admin"
                        {{ (isset($otherEvaluation) && $otherEvaluation) ? 'disabled' : '' }}>
                    @if(isset($otherEvaluation) && $otherEvaluation)
                        <i class="bi bi-lock"></i> 다른 심사위원이 채점 완료
                    @elseif($assignment->evaluation)
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
    
    // AI 평가 결과 자동 반영
    @if($aiEvaluation && !$assignment->evaluation)
    const aiScores = {
        pronunciation_score: {{ $aiEvaluation->pronunciation_score ?? 0 }},
        vocabulary_score: {{ $aiEvaluation->vocabulary_score ?? 0 }},
        fluency_score: {{ $aiEvaluation->fluency_score ?? 0 }}
    };
    
    console.log('AI 평가 결과 자동 반영:', aiScores);
    
    // AI 점수를 해당 입력 필드에 자동 설정
    Object.keys(aiScores).forEach(scoreType => {
        const scoreInput = document.getElementById(scoreType);
        const scoreRange = document.getElementById(scoreType + '_range');
        
        if (scoreInput && aiScores[scoreType] > 0) {
            scoreInput.value = aiScores[scoreType];
            if (scoreRange) {
                scoreRange.value = aiScores[scoreType];
            }
            
            // 입력 필드에 AI 점수임을 표시
            scoreInput.style.backgroundColor = '#e3f2fd';
            scoreInput.title = 'AI가 평가한 점수가 자동 반영되었습니다. 필요시 수정 가능합니다.';
            
            console.log(`${scoreType}: ${aiScores[scoreType]}점 자동 설정`);
        }
    });
    
    // AI 점수 자동 반영 알림 표시
    const aiNotification = document.createElement('div');
    aiNotification.className = 'alert alert-info alert-dismissible fade show mt-3';
    
    // AI 평가가 관리자에 의해 일괄 채점되었는지 확인
    const isBatchEvaluation = {{ $aiEvaluation->admin_id == 1 ? 'true' : 'false' }};
    const evaluationSource = isBatchEvaluation ? '관리자 일괄 채점' : 'AI 개별 평가';
    
    aiNotification.innerHTML = `
        <i class="bi bi-robot"></i> 
        <strong>AI 평가 결과가 자동 반영되었습니다!</strong><br>
        <small class="text-muted">출처: ${evaluationSource}</small><br>
        발음(${aiScores.pronunciation_score}점), 어휘(${aiScores.vocabulary_score}점), 유창성(${aiScores.fluency_score}점)이 설정되었습니다. 필요시 수정 가능합니다.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // 폼 시작 부분에 알림 추가
    const formCard = document.querySelector('.card.admin-card');
    if (formCard) {
        formCard.insertBefore(aiNotification, formCard.firstChild);
    }
    
    // AI 점수 반영 후 총점 재계산
    setTimeout(() => calculateTotal(), 100);
    @endif
    
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
        const confirmMessage = `심사 결과를 저장하시겠습니까?\n\n총점: ${total}/70점`;
        
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
    
    // AI 평가 관련 변수 설정
    @php
        $currentAdminId = auth('admin')->user()->id ?? auth('admin')->id();
        $currentAiEval = $submission->aiEvaluations->where('admin_id', $currentAdminId)->first();
        $batchAiEval = $submission->aiEvaluations->where('processing_status', 'completed')->first();
    @endphp
    
    const currentAdminId = {{ $currentAdminId ?? 'null' }};
    const assignmentId = {{ $assignment->id }};
    const hasAiEvaluation = {{ $currentAiEval ? 'true' : 'false' }};
    const hasBatchAiEvaluation = {{ $batchAiEval ? 'true' : 'false' }};
    const aiEvaluateUrl = '{{ url("/judge/ai-evaluate") }}';
    
    // 디버깅 정보 출력
    console.log('=== AI 평가 디버깅 정보 ===');
    console.log('currentAdminId:', currentAdminId);
    console.log('assignmentId:', assignmentId);
    console.log('hasAiEvaluation:', hasAiEvaluation);
    console.log('hasBatchAiEvaluation:', hasBatchAiEvaluation);
    console.log('aiEvaluateUrl:', aiEvaluateUrl);
    console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
    
    @if($currentAiEval)
    const aiEvaluationData = {
        id: {{ $currentAiEval->id }},
        status: '{{ $currentAiEval->processing_status }}',
        admin_id: {{ $currentAiEval->admin_id }}
    };
    console.log('aiEvaluationData:', aiEvaluationData);
    @else
    const aiEvaluationData = null;
    console.log('aiEvaluationData:', null);
    @endif
    
    console.log('페이지 로드 - AI 평가 상태 확인 시작');
    
    // 1초 후 버튼 상태 확인 및 동기화 (DOM 로드 완료 후)
    setTimeout(function() {
        const existingViewBtn = document.querySelector('.view-ai-result-btn');
        const existingAiBtn = document.querySelector('.ai-evaluate-btn');
        
        console.log('DOM 로드 후 버튼 상태:');
        console.log('- 결과 보기 버튼:', existingViewBtn);
        console.log('- AI 평가 버튼:', existingAiBtn);
        
        // DOM 구조 디버깅
        const videoControlPanel = document.querySelector('.p-3.bg-light.border-top');
        console.log('- 영상 컨트롤 패널:', videoControlPanel);
        
        const btnGroupVertical = document.querySelector('.btn-group-vertical');
        console.log('- 버튼 그룹 세로:', btnGroupVertical);
        
        const allBtnGroups = document.querySelectorAll('.btn-group');
        console.log('- 모든 버튼 그룹들:', allBtnGroups);
        
        allBtnGroups.forEach((group, index) => {
            console.log(`  - 버튼 그룹 ${index}:`, group);
            console.log(`    - 내용:`, group.innerHTML);
        });
        
        if (hasAiEvaluation && aiEvaluationData) {
            console.log('AI 평가 데이터 로드:', aiEvaluationData);
            
            // completed 상태인데 AI 평가 버튼이 표시되는 경우 강제 동기화
            if (aiEvaluationData.status === 'completed' && existingAiBtn && !existingViewBtn) {
                console.log('⚠️ 상태 불일치 감지 - 강제 동기화 실행');
                syncAiEvaluationButton(aiEvaluationData);
            } else if (aiEvaluationData.status === 'completed' && existingViewBtn) {
                console.log('✅ 버튼 상태 정상 (완료됨)');
            } else if (aiEvaluationData.status === 'processing') {
                console.log('✅ 버튼 상태 정상 (처리중)');
            } else if (aiEvaluationData.status === 'failed' && existingAiBtn) {
                console.log('✅ 버튼 상태 정상 (실패 - 재시도 가능)');
            }
        } else {
            console.log('AI 평가 데이터 없음 - AI 평가 버튼이 표시되어야 함');
            if (existingAiBtn) {
                console.log('✅ AI 평가 버튼 정상 발견');
            } else {
                console.log('⚠️ AI 평가 버튼을 찾을 수 없습니다');
                // 버튼 컨테이너가 있는지 확인
                const buttonContainer = document.querySelector('.btn-group.mt-1[role="group"]');
                console.log('- 버튼 컨테이너:', buttonContainer);
                if (buttonContainer) {
                    console.log('- 컨테이너 내부:', buttonContainer.innerHTML);
                }
            }
        }
    }, 1000);
    
    // AI 평가 버튼 클릭 이벤트
    document.addEventListener('click', function(e) {
        if (e.target.closest('.ai-evaluate-btn')) {
            e.preventDefault();
            
            // 관리자 일괄 채점 결과가 있는 경우 AI 평가 실행 방지
            if (hasBatchAiEvaluation) {
                alert('관리자가 이미 일괄 채점한 AI 평가 결과가 있습니다. AI 평가를 다시 실행할 수 없습니다.');
                return;
            }
            
            const button = e.target.closest('.ai-evaluate-btn');
            const assignmentId = button.dataset.assignmentId;
            
            if (confirm('AI 평가를 시작하시겠습니까? 처리에 시간이 걸릴 수 있습니다.')) {
                // 버튼 비활성화 및 로딩 상태 표시
                button.disabled = true;
                button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 처리중...';
                button.classList.remove('btn-outline-info');
                button.classList.add('btn-warning');
                
                // AI 평가 요청
                console.log('=== AI 평가 요청 시작 ===');
                console.log('요청 URL:', `${aiEvaluateUrl}/${assignmentId}`);
                console.log('Assignment ID:', assignmentId);
                
                fetch(`${aiEvaluateUrl}/${assignmentId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    console.log('=== AI 평가 응답 상태 ===');
                    console.log('Response status:', response.status);
                    console.log('Response statusText:', response.statusText);
                    console.log('Response headers:', response.headers);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('=== AI 평가 응답 데이터 ===');
                    console.log('Response data:', data);
                    if (data.success) {
                        // syncAiEvaluationButton 함수를 사용하여 버튼 상태 업데이트
                        const newAiEvalData = {
                            id: data.ai_evaluation_id,
                            status: 'completed',
                            admin_id: currentAdminId
                        };
                        syncAiEvaluationButton(newAiEvalData);
                        
                        // AI 평가 결과 영역이 없다면 페이지 새로고침
                        if (!document.querySelector('.card.admin-card h5 i.bi-robot')) {
                            setTimeout(() => location.reload(), 1000);
                        }
                        
                        alert('AI 평가가 완료되었습니다! "결과 보기" 버튼을 클릭하여 결과를 확인하세요.');
                    } else {
                        alert('AI 평가 중 오류가 발생했습니다: ' + (data.message || '알 수 없는 오류'));
                        // 버튼 복원
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
                        button.classList.remove('btn-warning');
                        button.classList.add('btn-outline-info');
                    }
                })
                .catch(error => {
                    console.error('=== AI 평가 요청 오류 ===');
                    console.error('Error type:', error.name);
                    console.error('Error message:', error.message);
                    console.error('Error stack:', error.stack);
                    console.error('Full error object:', error);
                    
                    alert('AI 평가 요청 중 오류가 발생했습니다: ' + error.message);
                    // 버튼 복원
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
                    button.classList.remove('btn-warning');
                    button.classList.add('btn-outline-info');
                });
            }
        }
        
        // AI 결과 보기 버튼 클릭 이벤트
        if (e.target.closest('.view-ai-result-btn')) {
            e.preventDefault();
            const button = e.target.closest('.view-ai-result-btn');
            const aiEvaluationId = button.dataset.aiEvaluationId;
            
            // AI 평가 결과 모달 표시
            showAiResultModal(aiEvaluationId);
        }
    });

    // AI 평가 결과 모달 표시 함수
    function showAiResultModal(aiEvaluationId) {
        const aiResultUrl = '{{ url("/judge/ai-result") }}';
        fetch(`${aiResultUrl}/${aiEvaluationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = createAiResultModal(data.aiEvaluation);
                    document.body.appendChild(modal);
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                    
                    // 모달 닫힐 때 DOM에서 제거
                    modal.addEventListener('hidden.bs.modal', function() {
                        document.body.removeChild(modal);
                    });
                } else {
                    alert('AI 평가 결과를 불러올 수 없습니다.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('AI 평가 결과 조회 중 오류가 발생했습니다.');
            });
    }

    // AI 평가 버튼 상태 동기화 함수
    function syncAiEvaluationButton(aiEvalData) {
        console.log('syncAiEvaluationButton 호출:', aiEvalData);
        
        // 모든 AI 관련 버튼 찾기
        const aiButtonContainer = document.querySelector('.btn-group.mt-1[role="group"]');
        console.log('AI 버튼 컨테이너:', aiButtonContainer);
        
        if (!aiButtonContainer) {
            console.error('AI 버튼 컨테이너를 찾을 수 없습니다.');
            return;
        }
        
        // 기존 AI 관련 버튼들 찾기
        const existingButtons = aiButtonContainer.querySelectorAll('button');
        console.log('기존 버튼들:', existingButtons);
        
        // AI 관련 버튼들만 제거
        existingButtons.forEach(btn => {
            if (btn.classList.contains('ai-evaluate-btn') || 
                btn.classList.contains('view-ai-result-btn') ||
                (btn.classList.contains('btn-warning') && btn.disabled && btn.innerHTML.includes('처리중'))) {
                console.log('기존 AI 버튼 제거:', btn.className, btn.innerHTML);
                btn.remove();
            }
        });
        
        // 상태에 따라 적절한 버튼 생성
        let newButton = null;
        
        if (aiEvalData.status === 'completed') {
            newButton = document.createElement('button');
            newButton.type = 'button';
            newButton.className = 'btn btn-success btn-sm view-ai-result-btn';
            newButton.setAttribute('data-ai-evaluation-id', aiEvalData.id);
            newButton.title = 'AI 평가 결과 보기';
            newButton.innerHTML = '<i class="bi bi-check-circle"></i> 결과 보기';
            console.log('완료 상태 - 결과 보기 버튼 생성');
        } else if (aiEvalData.status === 'processing') {
            newButton = document.createElement('button');
            newButton.type = 'button';
            newButton.className = 'btn btn-warning btn-sm';
            newButton.disabled = true;
            newButton.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 처리중...';
            console.log('처리중 상태 버튼 생성');
        } else if (aiEvalData.status === 'failed' || !aiEvalData.status) {
            newButton = document.createElement('button');
            newButton.type = 'button';
            newButton.className = 'btn btn-outline-info btn-sm ai-evaluate-btn';
            newButton.setAttribute('data-assignment-id', assignmentId);
            newButton.title = 'AI로 평가하기';
            newButton.innerHTML = '<i class="bi bi-robot"></i> AI 평가';
            console.log('실패/없음 상태 - AI 평가 버튼 생성');
        }
        
        if (newButton) {
            aiButtonContainer.appendChild(newButton);
            console.log('새 버튼 추가 완료:', newButton.className, newButton.innerHTML);
        } else {
            console.error('새 버튼을 생성하지 못했습니다. 상태:', aiEvalData.status);
        }
    }

    // AI 결과 모달 생성 함수
    function createAiResultModal(aiEvaluation) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-robot"></i> AI 평가 결과
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">발음 및 전달력</h6>
                                        <h3 class="text-primary">${aiEvaluation.pronunciation_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">어휘 및 표현</h6>
                                        <h3 class="text-success">${aiEvaluation.vocabulary_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <h6 class="card-title">유창성</h6>
                                        <h3 class="text-info">${aiEvaluation.fluency_score}/10</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">총점</h6>
                            </div>
                            <div class="card-body text-center">
                                <h2 class="text-primary">${aiEvaluation.total_score}/30</h2>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">AI 심사평</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">${aiEvaluation.ai_feedback || '심사평이 없습니다.'}</p>
                            </div>
                        </div>
                        
                        ${aiEvaluation.transcription ? `
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">음성 전사 결과</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0" style="font-family: monospace; font-size: 0.9em;">${aiEvaluation.transcription}</p>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                    </div>
                </div>
            </div>
        `;
        return modal;
    }
});

// 중복 알림 방지
document.addEventListener('DOMContentLoaded', function() {
    // 성공 알림이 있으면 3초 후 자동으로 숨기기
    const successAlert = document.getElementById('success-alert');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.display = 'none';
        }, 3000);
    }
    
    // 중복 알림 제거 (같은 메시지가 여러 개 있으면 하나만 남기기)
    const alerts = document.querySelectorAll('.alert-success');
    if (alerts.length > 1) {
        for (let i = 1; i < alerts.length; i++) {
            alerts[i].remove();
        }
    }
});
</script>
@endsection 
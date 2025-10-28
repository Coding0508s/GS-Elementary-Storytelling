@extends('admin.layout')

@section('title', '영상 일괄 채점')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-collection-play"></i> 영상 일괄 채점</h1>
    <div class="d-flex gap-2">
        <button id="refresh-progress" class="btn btn-outline-primary">
            <i class="bi bi-arrow-clockwise"></i> 새로고침
        </button>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드로
        </a>
    </div>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($totalSubmissions) }}</h3>
                <p class="card-text text-muted">총 영상 수</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($completedEvaluations) }}</h3>
                <p class="card-text text-muted">AI 채점 완료</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($pendingSubmissions) }}</h3>
                <p class="card-text text-muted">대기 중</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-danger mb-2">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <h3 class="text-danger">{{ number_format($failedEvaluations) }}</h3>
                <p class="card-text text-muted">실패</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-file-x"></i>
                </div>
                <h3 class="text-warning">{{ number_format($noFileEvaluations) }}</h3>
                <p class="card-text text-muted">파일없음</p>
            </div>
        </div>
    </div>
</div>

<!-- 진행률 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart"></i> AI 채점 진행률</h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>전체 진행률</span>
            <span class="fw-bold">{{ $progressPercentage }}%</span>
        </div>
        
        <div class="progress" style="height: 25px;">
            <div class="progress-bar bg-success" 
                 role="progressbar" 
                 style="width: {{ $progressPercentage }}%"
                 aria-valuenow="{{ $progressPercentage }}" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                {{ $progressPercentage }}%
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-2 text-center">
                <small class="text-muted">완료</small><br>
                <strong class="text-success">{{ $completedEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">처리중</small><br>
                <strong class="text-primary">{{ $processingEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">실패</small><br>
                <strong class="text-danger">{{ $failedEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">파일없음</small><br>
                <strong class="text-warning">{{ $noFileEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">대기</small><br>
                <strong class="text-warning">{{ $pendingSubmissions }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">전체</small><br>
                <strong class="text-dark">{{ $totalSubmissions }}개</strong>
            </div>
        </div>
    </div>
</div>

<!-- 일괄 채점 컨트롤 -->
<div class="card admin-card mb-4">
    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="mb-0 text-white">
            <i class="bi bi-robot"></i> AI 일괄 채점 컨트롤
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <p class="text-muted mb-3">
                    <i class="bi bi-info-circle"></i> 
                    모든 제출 영상을 AI Whisper와 GPT-4를 사용하여 자동으로 채점합니다.
                </p>
                <div class="alert alert-info py-2 mb-3">
                    <small>
                        <i class="bi bi-lightbulb"></i> 
                        <strong>처리 과정:</strong> 영상 → 음성 추출 → Whisper 음성인식 → GPT-4 평가 → 점수 저장
                    </small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="d-grid gap-2">
                    <button id="start-batch-evaluation" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> 일괄 AI 채점 시작
                    </button>
                    <button id="retry-failed-evaluations" class="btn btn-warning" style="display: none;">
                        <i class="bi bi-arrow-clockwise"></i> 실패한 평가 재시도
                    </button>
                    <button id="stop-evaluation" class="btn btn-danger" style="display: none;">
                        <i class="bi bi-stop-circle"></i> 채점 중지
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 검색 및 필터 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-search"></i> 검색 및 필터</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.batch.evaluation.list') }}" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">검색</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="{{ $search }}" placeholder="학생명, 기관명으로 검색">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">AI 채점 상태</label>
                <select class="form-select" id="status" name="status">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>전체</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>대기 중</option>
                    <option value="processing" {{ $status === 'processing' ? 'selected' : '' }}>처리 중</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>완료</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>실패</option>
                    <option value="no_file" {{ $status === 'no_file' ? 'selected' : '' }}>파일없음</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="institution" class="form-label">기관</label>
                <select class="form-select" id="institution" name="institution">
                    <option value="">전체 기관</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst }}" {{ $institution === $inst ? 'selected' : '' }}>
                            {{ $inst }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> 검색
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 영상 리스트 -->
<div class="card admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-ul"></i> 영상 목록 ({{ $submissions->total() }}개)</h5>
        <div class="d-flex gap-2">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-sort-down"></i> 정렬
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'desc']) }}">
                        <i class="bi bi-calendar"></i> 최신순
                    </a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'order' => 'asc']) }}">
                        <i class="bi bi-calendar"></i> 오래된순
                    </a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'student_name_korean', 'order' => 'asc']) }}">
                        <i class="bi bi-person"></i> 학생명순
                    </a></li>
                    <li><a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['sort' => 'institution_name', 'order' => 'asc']) }}">
                        <i class="bi bi-building"></i> 기관순
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        @if($submissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>접수번호</th>
                            <th>학생명</th>
                            <th>기관</th>
                            <th>영상 파일</th>
                            <th>AI 채점 상태</th>
                            <th>AI 점수</th>
                            <th>처리 시간</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                        <tr>
                            <td>
                                <small>{{ $submission->receipt_number }}</small>
                            </td>
                            <td>
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $submission->institution_name }}<br>
                                <small class="text-muted">{{ $submission->class_name }}</small>
                            </td>
                            <td>
                                <i class="bi bi-camera-video text-primary"></i>
                                {{ Str::limit($submission->video_file_name, 20) }}<br>
                                <small class="text-muted">{{ $submission->getFormattedFileSizeAttribute() }}</small>
                            </td>
                            <td>
                                @php
                                    $aiEvaluation = $submission->aiEvaluations->first();
                                @endphp
                                @if($aiEvaluation)
                                    @switch($aiEvaluation->processing_status)
                                        @case(\App\Models\AiEvaluation::STATUS_COMPLETED)
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> 완료
                                            </span>
                                            @break
                                        @case(\App\Models\AiEvaluation::STATUS_PROCESSING)
                                            <span class="badge bg-primary">
                                                <i class="bi bi-arrow-clockwise"></i> 처리중
                                            </span>
                                            @break
                                        @case(\App\Models\AiEvaluation::STATUS_FAILED)
                                            @if($aiEvaluation->error_message === '영상 파일이 존재하지 않습니다.')
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-file-x"></i> 파일없음
                                                </span>
                                                <br><small class="text-warning">영상 파일이 존재하지 않습니다</small>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> 실패
                                                </span>
                                                @if($aiEvaluation->error_message)
                                                    <br><small class="text-danger">{{ Str::limit($aiEvaluation->error_message, 30) }}</small>
                                                @endif
                                            @endif
                                            @break
                                        @default
                                            <span class="badge bg-secondary">
                                                <i class="bi bi-question-circle"></i> 알 수 없음
                                            </span>
                                    @endswitch
                                @else
                                    <span class="badge bg-warning">
                                        <i class="bi bi-clock"></i> 대기중
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($aiEvaluation && $aiEvaluation->processing_status === \App\Models\AiEvaluation::STATUS_COMPLETED)
                                    <div class="text-center">
                                        <strong class="text-success">{{ $aiEvaluation->total_score }}점</strong><br>
                                        <small class="text-muted">
                                            발음: {{ $aiEvaluation->pronunciation_score }} | 
                                            어휘: {{ $aiEvaluation->vocabulary_score }} | 
                                            유창성: {{ $aiEvaluation->fluency_score }}
                                        </small>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($aiEvaluation && $aiEvaluation->processed_at)
                                    <small>{{ $aiEvaluation->processed_at->format('m/d H:i') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($aiEvaluation && $aiEvaluation->processing_status === \App\Models\AiEvaluation::STATUS_COMPLETED)
                                        <button class="btn btn-outline-success" onclick="viewAiEvaluation({{ $aiEvaluation->id }})">
                                            <i class="bi bi-eye"></i> 보기
                                        </button>
                                    @elseif($aiEvaluation && $aiEvaluation->processing_status === \App\Models\AiEvaluation::STATUS_FAILED)
                                        @if($aiEvaluation->error_message === '영상 파일이 존재하지 않습니다.')
                                            <button class="btn btn-outline-secondary" disabled title="영상 파일이 없어 재시도할 수 없습니다">
                                                <i class="bi bi-file-x"></i> 파일없음
                                            </button>
                                        @else
                                            <button class="btn btn-outline-warning" onclick="retrySingleEvaluation({{ $submission->id }})">
                                                <i class="bi bi-arrow-clockwise"></i> 재시도
                                            </button>
                                        @endif
                                    @else
                                        <button class="btn btn-outline-primary" onclick="startSingleEvaluation({{ $submission->id }})">
                                            <i class="bi bi-play"></i> 시작
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            @if($submissions->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $submissions->appends(request()->query())->links('custom.pagination') }}
            </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-3">검색 조건에 맞는 영상이 없습니다.</p>
                <a href="{{ route('admin.batch.evaluation.list') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> 전체 목록 보기
                </a>
            </div>
        @endif
    </div>
</div>

<!-- AI 평가 상세 모달 -->
<div class="modal fade" id="aiEvaluationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-robot"></i> AI 평가 결과</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="aiEvaluationContent">
                <!-- 동적으로 로드됨 -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// AI 일괄 채점 관련 변수
let progressInterval = null;
let isEvaluationRunning = false;

// 페이지 로드 시 진행상황 확인
document.addEventListener('DOMContentLoaded', function() {
    checkAiEvaluationProgress();
});

// AI 일괄 채점 시작
document.getElementById('start-batch-evaluation').addEventListener('click', function() {
    if (isEvaluationRunning) {
        alert('이미 AI 채점이 진행 중입니다.');
        return;
    }
    
    if (confirm('모든 제출 영상에 대해 AI 채점을 시작하시겠습니까?\n\n이 작업은 시간이 오래 걸릴 수 있습니다.')) {
        startBatchAiEvaluation();
    }
});

// 실패한 평가 재시도
document.getElementById('retry-failed-evaluations').addEventListener('click', function() {
    if (confirm('실패한 AI 평가들을 재시도하시겠습니까?')) {
        retryFailedEvaluations();
    }
});

// 진행상황 새로고침
document.getElementById('refresh-progress').addEventListener('click', function() {
    checkAiEvaluationProgress();
    location.reload();
});

// AI 일괄 채점 시작 함수
function startBatchAiEvaluation() {
    const button = document.getElementById('start-batch-evaluation');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 시작 중...';
    button.disabled = true;
    
    fetch('{{ route("admin.batch.ai.evaluation.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            isEvaluationRunning = true;
            startProgressMonitoring();
            location.reload();
        } else {
            alert('오류: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// 실패한 평가 재시도 함수
function retryFailedEvaluations() {
    const button = document.getElementById('retry-failed-evaluations');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 재시도 중...';
    button.disabled = true;
    
    fetch('{{ route("admin.batch.ai.evaluation.retry") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            isEvaluationRunning = true;
            startProgressMonitoring();
            location.reload();
        } else {
            alert('오류: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('네트워크 오류가 발생했습니다.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// 진행상황 확인 함수
function checkAiEvaluationProgress() {
    fetch('{{ route("admin.batch.ai.evaluation.progress") }}', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 처리 중인 평가가 있으면 모니터링 시작
            if (data.data.processing_evaluations > 0) {
                isEvaluationRunning = true;
                if (!progressInterval) {
                    startProgressMonitoring();
                }
            } else {
                isEvaluationRunning = false;
                if (progressInterval) {
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
            }
            
            // 실패한 평가가 있으면 재시도 버튼 표시
            if (data.data.failed_evaluations > 0) {
                document.getElementById('retry-failed-evaluations').style.display = 'block';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// 진행상황 모니터링 시작
function startProgressMonitoring() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    progressInterval = setInterval(() => {
        checkAiEvaluationProgress();
    }, 5000); // 5초마다 확인
}

// 개별 영상 AI 채점 시작
function startSingleEvaluation(submissionId) {
    if (confirm('이 영상에 대해 AI 채점을 시작하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 시작 중...';
        button.disabled = true;
        
        fetch(`{{ url('admin/batch-evaluation/start-single') }}/${submissionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// 개별 영상 AI 채점 재시도
function retrySingleEvaluation(submissionId) {
    if (confirm('이 영상의 AI 채점을 재시도하시겠습니까?')) {
        const button = event.target;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 재시도 중...';
        button.disabled = true;
        
        fetch(`{{ url('admin/batch-evaluation/start-single') }}/${submissionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('오류: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// AI 평가 상세 보기
function viewAiEvaluation(evaluationId) {
    const modal = new bootstrap.Modal(document.getElementById('aiEvaluationModal'));
    const content = document.getElementById('aiEvaluationContent');
    
    content.innerHTML = '<div class="text-center"><i class="bi bi-arrow-clockwise"></i> 로딩 중...</div>';
    modal.show();
    
    fetch(`{{ url('admin/ai-evaluation') }}/${evaluationId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const eval = data.data;
            content.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bi bi-person"></i> 학생 정보</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>한국어 이름:</strong></td>
                                <td>${eval.student_name}</td>
                            </tr>
                            <tr>
                                <td><strong>영어 이름:</strong></td>
                                <td>${eval.student_name_english}</td>
                            </tr>
                            <tr>
                                <td><strong>기관:</strong></td>
                                <td>${eval.institution}</td>
                            </tr>
                            <tr>
                                <td><strong>반:</strong></td>
                                <td>${eval.class_name}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bi bi-robot"></i> AI 평가 결과</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>발음 및 억양:</strong></td>
                                <td><span class="badge bg-primary">${eval.pronunciation_score}/10</span></td>
                            </tr>
                            <tr>
                                <td><strong>어휘 및 표현:</strong></td>
                                <td><span class="badge bg-info">${eval.vocabulary_score}/10</span></td>
                            </tr>
                            <tr>
                                <td><strong>유창성:</strong></td>
                                <td><span class="badge bg-success">${eval.fluency_score}/10</span></td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>총점:</strong></td>
                                <td><span class="badge bg-warning fs-6">${eval.total_score}/30</span></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6><i class="bi bi-chat-text"></i> AI 피드백</h6>
                    <div class="alert alert-light">
                        ${eval.ai_feedback || '피드백이 없습니다.'}
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6><i class="bi bi-mic"></i> 음성 인식 결과</h6>
                    <div class="alert alert-info">
                        <small>${eval.transcription || '음성 인식 결과가 없습니다.'}</small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> 처리 시간: ${eval.processed_at || '알 수 없음'} | 
                        <i class="bi bi-person-gear"></i> 처리자: ${eval.admin_name}
                    </small>
                </div>
            `;
        } else {
            content.innerHTML = '<div class="alert alert-danger">AI 평가 정보를 불러올 수 없습니다: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = '<div class="alert alert-danger">네트워크 오류가 발생했습니다.</div>';
    });
}
</script>
@endpush

@endsection

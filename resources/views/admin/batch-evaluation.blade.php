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

<!-- 처리 과정 안내 -->
<!-- <div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-lightbulb me-2"></i>
        <div>
            <strong>처리 과정:</strong> 영상 → 음성 추출 → Whisper 음성인식 → GPT-4 평가 → 점수 저장
        </div>
    </div>
</div> -->

<!-- AI 일괄 채점 컨트롤 -->
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
            </div>
            <div class="col-md-4">
                <div class="d-grid gap-2">
                    <button id="start-batch-evaluation" class="btn btn-primary">
                        <i class="bi bi-play-circle"></i> 일괄 AI 채점 시작
                    </button>
                    <button id="cancel-batch-evaluation" class="btn btn-danger" style="display: none;">
                        <i class="bi bi-x-circle"></i> 일괄 AI 채점 취소
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

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary" data-card="total">{{ number_format($totalSubmissions) }}</h3>
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
                <h3 class="text-success" data-card="completed">{{ number_format($completedEvaluations) }}</h3>
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
                <h3 class="text-warning" data-card="pending">{{ number_format($pendingSubmissions) }}</h3>
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
                <h3 class="text-danger" data-card="failed">{{ number_format($failedEvaluations) }}</h3>
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
                <h3 class="text-warning" data-card="no_file">{{ number_format($noFileEvaluations) }}</h3>
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
                <strong class="text-success" data-stat="completed">{{ $completedEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">처리중</small><br>
                <strong class="text-primary" data-stat="processing">{{ $processingEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">실패</small><br>
                <strong class="text-danger" data-stat="failed">{{ $failedEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">파일없음</small><br>
                <strong class="text-warning" data-stat="no_file">{{ $noFileEvaluations }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">대기</small><br>
                <strong class="text-warning" data-stat="pending">{{ $pendingSubmissions }}개</strong>
            </div>
            <div class="col-2 text-center">
                <small class="text-muted">전체</small><br>
                <strong class="text-dark" data-stat="total">{{ $totalSubmissions }}개</strong>
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
                        @php
                            $aiEvaluation = $submission->aiEvaluations->first();
                            $status = $aiEvaluation ? $aiEvaluation->processing_status : 'pending';
                        @endphp
                        <tr data-status="{{ $status }}">
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
    // 초기 버튼 상태 설정
    initializeButtonStates();
    checkAiEvaluationProgress();
});

// 초기 버튼 상태 설정 함수
function initializeButtonStates() {
    // 서버에서 받은 초기 상태에 따라 버튼 설정
    const hasProcessing = {{ $processingEvaluations }} > 0;
    const hasPending = {{ $pendingSubmissions }} > 0;
    
    if (hasProcessing) {
        // 실제로 처리 중인 작업이 있을 때만 취소 버튼 표시
        document.getElementById('start-batch-evaluation').style.display = 'none';
        document.getElementById('cancel-batch-evaluation').style.display = 'block';
        isEvaluationRunning = true;
        console.log('초기 상태: 처리 중인 작업 있음 - 취소 버튼 표시');
    } else {
        // 처리 중인 작업이 없으면 시작 버튼 표시 (대기 중인 작업이 있어도)
        document.getElementById('start-batch-evaluation').style.display = 'block';
        document.getElementById('cancel-batch-evaluation').style.display = 'none';
        isEvaluationRunning = false;
        console.log('초기 상태: 처리 중인 작업 없음 - 시작 버튼 표시');
    }
}

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

// 일괄 AI 채점 취소
document.getElementById('cancel-batch-evaluation').addEventListener('click', function() {
    if (confirm('진행 중인 일괄 AI 채점을 취소하시겠습니까?\n\n처리 중인 작업이 중단되고 대기 중인 작업이 제거됩니다.')) {
        cancelBatchAiEvaluation();
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
            // 시작 후 자동 모니터링 시작
            isEvaluationRunning = true;
            startProgressMonitoring();
            // 버튼 상태 전환
            document.getElementById('start-batch-evaluation').style.display = 'none';
            document.getElementById('cancel-batch-evaluation').style.display = 'block';
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

// 일괄 AI 채점 취소 함수
function cancelBatchAiEvaluation() {
    const button = document.getElementById('cancel-batch-evaluation');
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 취소 중...';
    button.disabled = true;
    
    fetch('{{ route("admin.batch.ai.evaluation.cancel") }}', {
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
            isEvaluationRunning = false;
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            // 취소 버튼 숨기고 시작 버튼 표시
            document.getElementById('cancel-batch-evaluation').style.display = 'none';
            document.getElementById('start-batch-evaluation').style.display = 'block';
            // 통계 업데이트
            checkAiEvaluationProgress();
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
            // 버튼 상태 전환
            document.getElementById('start-batch-evaluation').style.display = 'none';
            document.getElementById('cancel-batch-evaluation').style.display = 'block';
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
    fetch('{{ route("admin.batch.ai.evaluation.progress") }}')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const progressData = data.data;
            
            // 통계 카드 업데이트
            updateStatisticsCards(progressData);
            
            // 실제로 처리 중인 평가가 있을 때만 취소 버튼 표시
            if (progressData.processing_evaluations > 0) {
                isEvaluationRunning = true;
                if (!progressInterval) {
                    console.log('진행 중인 작업 감지. 자동 모니터링 시작...');
                    startProgressMonitoring();
                }
                // 취소 버튼 표시, 시작 버튼 숨김
                const cancelBtn = document.getElementById('cancel-batch-evaluation');
                const startBtn = document.getElementById('start-batch-evaluation');
                if (cancelBtn) cancelBtn.style.display = 'block';
                if (startBtn) startBtn.style.display = 'none';
                console.log('버튼 상태: 취소 버튼 표시, 시작 버튼 숨김 (처리 중)');
            } else {
                isEvaluationRunning = false;
                if (progressInterval) {
                    console.log('모든 작업 완료. 모니터링 중지.');
                    clearInterval(progressInterval);
                    progressInterval = null;
                }
                // 시작 버튼 표시, 취소 버튼 숨김 (대기 중인 작업이 있어도)
                const startBtn = document.getElementById('start-batch-evaluation');
                const cancelBtn = document.getElementById('cancel-batch-evaluation');
                if (startBtn) startBtn.style.display = 'block';
                if (cancelBtn) cancelBtn.style.display = 'none';
                console.log('버튼 상태: 시작 버튼 표시, 취소 버튼 숨김 (대기 중)');
            }
            
            // 대기 중인 작업이 있으면 모니터링 시작 (버튼 상태와 무관)
            if (progressData.pending_submissions > 0 && !progressInterval) {
                console.log('대기 중인 작업 감지. 자동 모니터링 시작...');
                startProgressMonitoring();
            }
            
            // 실패한 평가가 있으면 재시도 버튼 표시
            const retryButton = document.getElementById('retry-failed-evaluations');
            if (retryButton) {
                if (progressData.failed_evaluations > 0) {
                    retryButton.style.display = 'block';
                } else {
                    retryButton.style.display = 'none';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// 통계 카드 업데이트 함수
function updateStatisticsCards(data) {
    // 상단 카드 업데이트
    const cardElements = {
        'total': data.total_submissions,
        'completed': data.completed_evaluations,
        'pending': data.pending_submissions,
        'failed': data.failed_evaluations,
        'no_file': data.no_file_evaluations || 0
    };
    
    Object.keys(cardElements).forEach(key => {
        const element = document.querySelector(`[data-card="${key}"]`);
        if (element) {
            element.textContent = cardElements[key].toLocaleString();
        }
    });
    
    // 진행률 업데이트
    const progressBar = document.querySelector('.progress-bar.bg-success');
    if (progressBar) {
        const percentage = data.progress_percentage;
        console.log('진행률 업데이트:', {
            completed: data.completed_evaluations,
            total: data.total_submissions,
            percentage: percentage
        });
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.textContent = percentage + '%';
    }
    
    // 하단 통계 업데이트
    updateBottomStatistics(data);
}

// 하단 통계 업데이트 함수
function updateBottomStatistics(data) {
    const statsElements = {
        'completed': data.completed_evaluations,
        'processing': data.processing_evaluations,
        'failed': data.failed_evaluations,
        'no_file': data.no_file_evaluations || 0,
        'pending': data.pending_submissions,
        'total': data.total_submissions
    };
    
    // 각 통계 업데이트
    Object.keys(statsElements).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = statsElements[key] + '개';
        }
    });
    
    console.log('📊 통계 업데이트 완료:', statsElements);
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
    
    fetch(`{{ url('admin/ai-evaluation') }}/${evaluationId}`)
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

// 영상 목록 테이블 실시간 동기화
let tableRefreshInterval = null;
let isTableRefreshEnabled = false;

// 테이블 자동 새로고침 기능 추가
document.addEventListener('DOMContentLoaded', function() {
    // 테이블 새로고침 버튼 추가
    const headerDiv = document.querySelector('.card-header .d-flex.justify-content-between.align-items-center');
    if (headerDiv) {
        const tableRefreshBtn = document.createElement('button');
        tableRefreshBtn.type = 'button';
        tableRefreshBtn.className = 'btn btn-sm btn-outline-info me-2';
        tableRefreshBtn.id = 'table-refresh-btn';
        tableRefreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 테이블 새로고침';
        headerDiv.appendChild(tableRefreshBtn);
        
        // 테이블 새로고침 버튼 이벤트
        tableRefreshBtn.addEventListener('click', function() {
            toggleTableRefresh();
        });
    }
    
    // 처리 중인 평가가 있는지 확인하여 자동 새로고침 시작
    checkForProcessingVideos();
});

function toggleTableRefresh() {
    const btn = document.getElementById('table-refresh-btn');
    
    if (isTableRefreshEnabled) {
        // 테이블 새로고침 중지
        if (tableRefreshInterval) {
            clearInterval(tableRefreshInterval);
            tableRefreshInterval = null;
        }
        isTableRefreshEnabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> 테이블 새로고침';
        btn.className = 'btn btn-sm btn-outline-info me-2';
        console.log('테이블 자동 새로고침 중지');
    } else {
        // 테이블 새로고침 시작
        tableRefreshInterval = setInterval(function() {
            refreshVideoTable();
        }, 3000); // 3초마다 새로고침
        isTableRefreshEnabled = true;
        btn.innerHTML = '<i class="bi bi-pause-circle"></i> 새로고침 중지';
        btn.className = 'btn btn-sm btn-info me-2';
        console.log('테이블 자동 새로고침 시작 (3초 간격)');
    }
}

function checkForProcessingVideos() {
    // 처리 중인 영상이 있는지 확인
    const processingRows = document.querySelectorAll('tr[data-status="processing"]');
    if (processingRows.length > 0) {
        console.log(`${processingRows.length}개의 처리 중인 영상 감지. 테이블 자동 새로고침 시작.`);
        toggleTableRefresh();
    }
}

function refreshVideoTable() {
    // 진행상황 API를 사용하여 더 정확한 데이터 가져오기
    fetch('{{ route("admin.batch.ai.evaluation.progress") }}')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('진행상황 API 응답:', data.data);
            
            // 통계 카드 업데이트
            updateStatisticsCards(data.data);
            
            // 개별 영상 상태는 서버에서 최신 데이터를 가져와서 업데이트
            refreshIndividualVideoStatus();
        }
    })
    .catch(error => {
        console.error('진행상황 API 오류:', error);
        // API 실패 시 기존 방식으로 폴백
        refreshVideoTableFallback();
    });
}

function refreshIndividualVideoStatus() {
    // AJAX로 영상 목록 데이터 새로고침
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        // 새로운 HTML에서 테이블 부분만 추출
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        const newTable = newDoc.querySelector('table tbody');
        const currentTable = document.querySelector('table tbody');
        
        if (newTable && currentTable) {
            // 기존 행들을 ID로 매핑
            const currentRows = Array.from(currentTable.querySelectorAll('tr'));
            const newRows = Array.from(newTable.querySelectorAll('tr'));
            
            let hasChanges = false;
            
            // 각 행을 개별적으로 업데이트
            newRows.forEach((newRow, index) => {
                if (currentRows[index]) {
                    const currentRow = currentRows[index];
                    const newStatus = newRow.getAttribute('data-status');
                    const currentStatus = currentRow.getAttribute('data-status');
                    
                    // 상태가 변경된 경우에만 해당 행 업데이트
                    if (newStatus !== currentStatus) {
                        console.log(`영상 ${index + 1} 상태 변경: ${currentStatus} → ${newStatus}`);
                        hasChanges = true;
                        
                        // AI 채점 상태 셀 업데이트 (5번째 컬럼)
                        const newStatusCell = newRow.children[4];
                        const currentStatusCell = currentRow.children[4];
                        if (newStatusCell && currentStatusCell) {
                            currentStatusCell.innerHTML = newStatusCell.innerHTML;
                        }
                        
                        // AI 점수 셀 업데이트 (6번째 컬럼)
                        const newScoreCell = newRow.children[5];
                        const currentScoreCell = currentRow.children[5];
                        if (newScoreCell && currentScoreCell) {
                            currentScoreCell.innerHTML = newScoreCell.innerHTML;
                        }
                        
                        // 처리 시간 셀 업데이트 (7번째 컬럼)
                        const newTimeCell = newRow.children[6];
                        const currentTimeCell = currentRow.children[6];
                        if (newTimeCell && currentTimeCell) {
                            currentTimeCell.innerHTML = newTimeCell.innerHTML;
                        }
                        
                        // 작업 버튼 셀 업데이트 (8번째 컬럼)
                        const newActionCell = newRow.children[7];
                        const currentActionCell = currentRow.children[7];
                        if (newActionCell && currentActionCell) {
                            currentActionCell.innerHTML = newActionCell.innerHTML;
                        }
                        
                        // 행의 data-status 속성 업데이트
                        currentRow.setAttribute('data-status', newStatus);
                    }
                }
            });
            
            if (hasChanges) {
                console.log('영상 상태 변경 감지 및 업데이트 완료');
            }
            
            // 처리 중인 영상이 없으면 자동 새로고침 중지
            const processingRows = document.querySelectorAll('tr[data-status="processing"]');
            if (processingRows.length === 0 && isTableRefreshEnabled) {
                console.log('모든 영상 처리 완료. 테이블 자동 새로고침 중지.');
                toggleTableRefresh();
            }
            
            console.log('영상 목록 테이블 새로고침 완료');
        }
    })
    .catch(error => {
        console.error('테이블 새로고침 오류:', error);
    });
}

function refreshVideoTableFallback() {
    // 기존 방식으로 폴백
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        const newTable = newDoc.querySelector('table tbody');
        const currentTable = document.querySelector('table tbody');
        
        if (newTable && currentTable) {
            currentTable.innerHTML = newTable.innerHTML;
            console.log('폴백 방식으로 테이블 새로고침 완료');
        }
    })
    .catch(error => {
        console.error('폴백 테이블 새로고침 오류:', error);
    });
}

// 페이지를 떠날 때 테이블 새로고침 중지
window.addEventListener('beforeunload', function() {
    if (tableRefreshInterval) {
        clearInterval(tableRefreshInterval);
    }
});
</script>
@endpush

@endsection

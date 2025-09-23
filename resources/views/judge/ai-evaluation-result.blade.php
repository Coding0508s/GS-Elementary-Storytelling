@extends('admin.layout')

@section('title', 'AI 평가 결과')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-robot"></i> AI 평가 결과</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('judge.evaluation.show', $submission->id) }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 심사 페이지로
        </a>
    </div>
</div>

<!-- 영상 정보 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-person-fill"></i> 영상 정보</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">접수번호</th>
                        <td><code>{{ $submission->receipt_number }}</code></td>
                    </tr>
                    <tr>
                        <th>학생명(한글)</th>
                        <td>{{ $submission->student_name_korean }}</td>
                    </tr>
                    <tr>
                        <th>학생명(영문)</th>
                        <td>{{ $submission->student_name_english }}</td>
                    </tr>
                    <tr>
                        <th>기관명</th>
                        <td>{{ $submission->institution_name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">학년</th>
                        <td>{{ $submission->grade }}</td>
                    </tr>
                    <tr>
                        <th>나이</th>
                        <td>{{ $submission->age }}세</td>
                    </tr>
                    <tr>
                        <th>발표 주제</th>
                        <td>{{ $submission->unit_topic }}</td>
                    </tr>
                    <tr>
                        <th>영상 크기</th>
                        <td>{{ $submission->getFormattedFileSizeAttribute() }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- AI 평가 상태 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> AI 평가 상태</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">처리 상태</th>
                        <td>
                            @if($aiEvaluation->processing_status === 'completed')
                                <span class="badge bg-success fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @elseif($aiEvaluation->processing_status === 'processing')
                                <span class="badge bg-warning fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @elseif($aiEvaluation->processing_status === 'failed')
                                <span class="badge bg-danger fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @else
                                <span class="badge bg-secondary fs-6">{{ $aiEvaluation->getStatusLabel() }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>평가자</th>
                        <td>{{ $aiEvaluation->admin->name ?? '시스템' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">시작 시간</th>
                        <td>{{ $aiEvaluation->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                        <th>완료 시간</th>
                        <td>
                            @if($aiEvaluation->processed_at)
                                {{ $aiEvaluation->processed_at->format('Y-m-d H:i:s') }}
                            @else
                                <span class="text-muted">미완료</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($aiEvaluation->error_message)
        <div class="alert alert-danger mt-3">
            <h6><i class="bi bi-exclamation-triangle"></i> 오류 메시지</h6>
            <p class="mb-0">{{ $aiEvaluation->error_message }}</p>
        </div>
        @endif
    </div>
</div>

@if($aiEvaluation->isCompleted())
<!-- AI 평가 결과 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-award"></i> AI 평가 점수</h5>
    </div>
    <div class="card-body">
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-primary">{{ $aiEvaluation->pronunciation_score }}<small>/10</small></h3>
                        <p class="mb-0">발음/억양/전달력</p>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-primary" 
                                 style="width: {{ ($aiEvaluation->pronunciation_score / 10) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-success">{{ $aiEvaluation->vocabulary_score }}<small>/10</small></h3>
                        <p class="mb-0">어휘/표현</p>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-success" 
                                 style="width: {{ ($aiEvaluation->vocabulary_score / 10) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-info">{{ $aiEvaluation->fluency_score }}<small>/10</small></h3>
                        <p class="mb-0">유창성</p>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-info" 
                                 style="width: {{ ($aiEvaluation->fluency_score / 10) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3>{{ $aiEvaluation->total_score }}<small>/30</small></h3>
                        <p class="mb-0">총점</p>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-white" 
                                 style="width: {{ ($aiEvaluation->total_score / 30) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 음성 인식 텍스트 -->
@if($aiEvaluation->transcription)
<div class="card admin-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-file-text"></i> 음성 인식 텍스트</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('transcription')">
            <i class="bi bi-clipboard"></i> 복사
        </button>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded position-relative">
            <pre id="transcription" class="mb-0" style="white-space: pre-wrap; font-family: inherit; max-height: 300px; overflow-y: auto;">{{ $aiEvaluation->transcription }}</pre>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle"></i> 
            이 텍스트는 OpenAI Whisper가 음성을 자동으로 인식한 결과입니다.
        </small>
    </div>
</div>
@endif

<!-- AI 심사평 -->
@if($aiEvaluation->ai_feedback)
<div class="card admin-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-chat-dots"></i> AI 심사평 (영어)</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('ai-feedback')">
            <i class="bi bi-clipboard"></i> 복사
        </button>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded position-relative">
            <pre id="ai-feedback" class="mb-0" style="white-space: pre-wrap; font-family: inherit; max-height: 400px; overflow-y: auto;">{{ $aiEvaluation->ai_feedback }}</pre>
        </div>
        <small class="text-muted mt-2 d-block">
            <i class="bi bi-info-circle"></i> 
            이 심사평은 GPT-4가 음성 인식 텍스트를 바탕으로 자동 생성한 결과입니다.
        </small>
    </div>
</div>
@endif

@endif

<!-- 평가 기준 안내 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> AI 평가 기준</h5>
    </div>
    <div class="card-body">
        <div class="row">
            @foreach(\App\Models\AiEvaluation::getCriteriaLabels() as $key => $label)
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <h6 class="text-primary">{{ $label }}</h6>
                    <small class="text-muted">0~10점</small>
                    <div class="mt-2">
                        @foreach(\App\Models\AiEvaluation::getScoreGuide() as $range => $desc)
                            <div class="d-flex justify-content-between small">
                                <span class="badge bg-secondary">{{ $range }}점</span>
                                <span>{{ $desc }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <div class="alert alert-info mt-3">
            <h6><i class="bi bi-lightbulb"></i> AI 평가 방식</h6>
            <ul class="mb-0">
                <li><strong>음성 인식:</strong> OpenAI Whisper를 사용하여 영상의 음성을 텍스트로 변환</li>
                <li><strong>평가 분석:</strong> GPT-4를 사용하여 텍스트 품질, 문법, 어휘 사용, 유창성을 분석</li>
                <li><strong>점수 산출:</strong> 각 기준별로 0~10점으로 평가하여 총 30점 만점으로 채점</li>
                <li><strong>심사평 제공:</strong> 구체적이고 건설적인 영어 피드백 제공</li>
            </ul>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent;
    
    navigator.clipboard.writeText(text).then(function() {
        // 성공 메시지 표시
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> 복사됨';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }).catch(function(err) {
        console.error('복사 실패:', err);
        alert('텍스트 복사에 실패했습니다.');
    });
}
</script>
@endsection

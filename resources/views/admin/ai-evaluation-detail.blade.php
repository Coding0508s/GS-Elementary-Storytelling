@extends('admin.layout')

@section('title', 'AI 채점 결과 상세')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-robot"></i> AI 채점 결과 상세</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.ai.evaluation.list') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 목록으로
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
                        <td><code>{{ $aiEvaluation->videoSubmission->receipt_number }}</code></td>
                    </tr>
                    <tr>
                        <th>학생명(한글)</th>
                        <td>{{ $aiEvaluation->videoSubmission->student_name_korean }}</td>
                    </tr>
                    <tr>
                        <th>학생명(영문)</th>
                        <td>{{ $aiEvaluation->videoSubmission->student_name_english }}</td>
                    </tr>
                    <tr>
                        <th>기관명</th>
                        <td>{{ $aiEvaluation->videoSubmission->institution_name }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm">
                    <tr>
                        <th width="30%">학년</th>
                        <td>{{ $aiEvaluation->videoSubmission->grade }}</td>
                    </tr>
                    <tr>
                        <th>나이</th>
                        <td>{{ $aiEvaluation->videoSubmission->age }}세</td>
                    </tr>
                    <tr>
                        <th>발표 주제</th>
                        <td>{{ $aiEvaluation->videoSubmission->unit_topic }}</td>
                    </tr>
                    <tr>
                        <th>영상 크기</th>
                        <td>{{ $aiEvaluation->videoSubmission->getFormattedFileSizeAttribute() }}</td>
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
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-success">{{ $aiEvaluation->vocabulary_score }}<small>/10</small></h3>
                        <p class="mb-0">어휘/표현</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="text-info">{{ $aiEvaluation->fluency_score }}<small>/10</small></h3>
                        <p class="mb-0">유창성</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3>{{ $aiEvaluation->total_score }}<small>/30</small></h3>
                        <p class="mb-0">총점</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 음성 인식 텍스트 -->
@if($aiEvaluation->transcription)
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-text"></i> 음성 인식 텍스트</h5>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded">
            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $aiEvaluation->transcription }}</pre>
        </div>
    </div>
</div>
@endif

<!-- AI 심사평 -->
@if($aiEvaluation->ai_feedback)
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-chat-dots"></i> AI 심사평 (영어)</h5>
    </div>
    <div class="card-body">
        <div class="bg-light p-3 rounded">
            <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $aiEvaluation->ai_feedback }}</pre>
        </div>
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
                            <div class="d-flex justify-content-between">
                                <span class="badge bg-secondary">{{ $range }}점</span>
                                <small>{{ $desc }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

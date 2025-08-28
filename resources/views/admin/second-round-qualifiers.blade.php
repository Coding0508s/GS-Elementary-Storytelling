{{-- 
2차 예선진출 기능이 필요 없어서 전체 파일을 주석처리
@extends('admin.layout')

@section('title', '2차 예선 진출자 목록')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-trophy"></i> 2차 예선 진출자 목록</h1>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-light me-2">
            <i class="bi bi-arrow-left"></i> 대시보드
        </a>
        <small class="text-muted">{{ now()->format('Y년 m월 d일 H:i') }}</small>
    </div>
</div>

<!-- 전체 통계 -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <h3 class="text-primary">{{ $totalQualified }}</h3>
                <p class="mb-0 text-muted">총 진출자</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <h3 class="text-success">{{ $judgesCount }}</h3>
                <p class="mb-0 text-muted">심사위원 수</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <h3 class="text-info">{{ $qualifiedByJudge->count() }}</h3>
                <p class="mb-0 text-muted">평가 완료 심사위원</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card admin-card text-center">
            <div class="card-body">
                <h3 class="text-warning">{{ $totalQualified > 0 ? number_format($totalQualified / $judgesCount, 1) : 0 }}</h3>
                <p class="mb-0 text-muted">심사위원당 평균 진출자</p>
            </div>
        </div>
    </div>
</div>

@if($qualifiedByJudge->count() > 0)
    <!-- 심사위원별 진출자 목록 -->
    @foreach($qualifiedByJudge as $judgeId => $evaluations)
        @php
            $judge = $evaluations->first()->admin;
        @endphp
        <div class="card admin-card mb-4">
            <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h5 class="mb-0 text-white">
                    <i class="bi bi-person-badge"></i> 
                    {{ $judge->name }} 심사위원 
                    <small class="ms-2">({{ $evaluations->count() }}명 선정)</small>
                </h5>
                <small class="text-white-50">※ 총점 순위: 1위부터 {{ $evaluations->count() }}위까지 순서대로 표시</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-admin table-hover">
                        <thead>
                            <tr>
                                <th style="width: 80px">순위</th>
                                <th>학생명</th>
                                <th>기관</th>
                                <th>학년/반</th>
                                <th>나이</th>
                                <th>Unit 주제</th>
                                <th>총점</th>
                                <th>등급</th>
                                <th>제출일시</th>
                                <th>진출 확정일</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($evaluations as $index => $evaluation)
                                @php
                                    $submission = $evaluation->videoSubmission;
                                    $displayRank = $evaluation->rank_by_judge ?? ($index + 1); // rank_by_judge가 null이면 순서 사용
                                    $rankBadgeClass = match($displayRank) {
                                        1 => 'bg-warning text-dark',
                                        2 => 'bg-secondary text-white', 
                                        3 => 'bg-success text-white',
                                        default => 'bg-primary text-white'
                                    };
                                    $rankIcon = match($displayRank) {
                                        1 => '👑',
                                        2 => '🥈', 
                                        3 => '🥉',
                                        default => ''
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $rankBadgeClass }} fs-6">
                                            {{ $rankIcon }} {{ $displayRank }}위
                                        </span>
                                    </td>
                                    <td>
                                        <strong>{{ $submission->student_name_korean }}</strong>
                                        <br><small class="text-muted">{{ $submission->student_name_english }}</small>
                                    </td>
                                    <td>{{ $submission->institution_name }}</td>
                                    <td>{{ $submission->grade }}학년 {{ $submission->class_name }}</td>
                                    <td>{{ $submission->age }}세</td>
                                    <td>
                                        @if($submission->unit_topic)
                                            <span class="badge bg-info">{{ $submission->unit_topic }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ $evaluation->total_score }}/40</strong>
                                    </td>
                                    <td>
                                        @php
                                            $grade = '';
                                            $badgeClass = '';
                                            if ($evaluation->total_score >= 36) {
                                                $grade = 'A+';
                                                $badgeClass = 'bg-success';
                                            } elseif ($evaluation->total_score >= 32) {
                                                $grade = 'A';
                                                $badgeClass = 'bg-success';
                                            } elseif ($evaluation->total_score >= 28) {
                                                $grade = 'B+';
                                                $badgeClass = 'bg-primary';
                                            } elseif ($evaluation->total_score >= 24) {
                                                $grade = 'B';
                                                $badgeClass = 'bg-primary';
                                            } elseif ($evaluation->total_score >= 20) {
                                                $grade = 'C+';
                                                $badgeClass = 'bg-warning text-dark';
                                            } elseif ($evaluation->total_score >= 16) {
                                                $grade = 'C';
                                                $badgeClass = 'bg-warning text-dark';
                                            } else {
                                                $grade = 'D';
                                                $badgeClass = 'bg-danger';
                                            }
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $grade }}</span>
                                    </td>
                                    <td>
                                        <small class="text-info">{{ $submission->created_at->format('m/d H:i') }}</small>
                                        <br><span class="text-muted" style="font-size: 0.7rem;">업로드</span>
                                    </td>
                                    <td>
                                        <small class="text-success">{{ $evaluation->qualified_at->format('m/d H:i') }}</small>
                                        <br><span class="text-muted" style="font-size: 0.7rem;">진출확정</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    <!-- 다운로드 및 액션 버튼 -->
    <div class="card admin-card">
        <div class="card-body text-center">
            <h6 class="mb-3">추가 액션</h6>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.download.second.round.qualifiers') }}" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> 엑셀 다운로드
                </a>
                <button type="button" class="btn btn-info" onclick="printList()">
                    <i class="bi bi-printer"></i> 인쇄
                </button>
                <button type="button" class="btn btn-warning" onclick="refreshData()">
                    <i class="bi bi-arrow-clockwise"></i> 새로고침
                </button>
            </div>
        </div>
    </div>

@else
    <!-- 진출자가 없는 경우 -->
    <div class="card admin-card">
        <div class="card-body text-center py-5">
            <i class="bi bi-exclamation-circle display-1 text-muted mb-3"></i>
            <h4 class="text-muted mb-3">아직 2차 예선 진출자가 선정되지 않았습니다</h4>
            <p class="text-muted mb-4">
                관리자 대시보드에서 "2차 예선 진출자 선정" 버튼을 클릭하여<br>
                각 심사위원별 상위 10명을 자동으로 선정할 수 있습니다.
            </p>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> 대시보드로 이동
            </a>
        </div>
    </div>
@endif

<script>
function printList() {
    window.print();
}

function refreshData() {
    location.reload();
}
</script>

@endsection
--}}

{{-- 2차 예선진출 기능이 필요 없어서 이 파일은 사용하지 않습니다. --}}
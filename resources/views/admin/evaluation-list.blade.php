@extends('admin.layout')

@section('title', '심사 관리')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="bi bi-clipboard-check"></i> 심사 관리</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.download.excel', request()->query()) }}" 
           class="btn btn-success">
            <i class="bi bi-download"></i> 엑셀 다운로드
        </a>
        <a href="{{ route('admin.dashboard') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드
        </a>
    </div>
</div>

<!-- 필터 및 검색 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-funnel"></i> 필터 및 검색</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.evaluation.list') }}" method="GET" class="row">
            <div class="col-md-4 mb-3">
                <label for="search" class="form-label">검색</label>
                <input type="text" 
                       class="form-control" 
                       id="search" 
                       name="search" 
                       placeholder="학생명, 기관명 검색"
                       value="{{ request('search') }}">
            </div>
            
            <div class="col-md-4 mb-3">
                <label for="status" class="form-label">심사 상태</label>
                <select class="form-control" id="status" name="status">
                    <option value="">전체</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>
                        심사 대기
                    </option>
                    <option value="evaluated" {{ request('status') === 'evaluated' ? 'selected' : '' }}>
                        심사 완료
                    </option>
                </select>
            </div>
            
            <div class="col-md-4 mb-3 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex w-100">
                    <button type="submit" class="btn btn-admin flex-fill">
                        <i class="bi bi-search"></i> 검색
                    </button>
                    <a href="{{ route('admin.evaluation.list') }}" 
                       class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> 초기화
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 통계 요약 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-collection"></i>
                </div>
                <h3 class="text-primary">{{ number_format($submissions->total()) }}</h3>
                <p class="card-text text-muted">총 접수</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($submissions->where('evaluation', '!=', null)->count()) }}</h3>
                <p class="card-text text-muted">심사 완료</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($submissions->where('evaluation', null)->count()) }}</h3>
                <p class="card-text text-muted">심사 대기</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3 class="text-info">{{ number_format($submissions->currentPage()) }}</h3>
                <p class="card-text text-muted">현재 페이지</p>
            </div>
        </div>
    </div>
</div>

<!-- 영상 목록 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i> 
            영상 목록 
            <span class="badge bg-light text-dark ms-2">{{ $submissions->total() }}개</span>
        </h5>
    </div>
    <div class="card-body">
        @if($submissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th width="80">접수번호</th>
                            <th>접수일시</th>
                            <th>학생 정보</th>
                            <th>기관 정보</th>
                            <th>영상 정보</th>
                            <th>심사 상태</th>
                            <th width="120">작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($submissions as $submission)
                        <tr>
                            <td><small>{{ $submission->receipt_number }}</small></td>
                            
                            <td>
                                <small>{{ $submission->created_at->format('Y-m-d') }}</small><br>
                                <small class="text-muted">{{ $submission->created_at->format('H:i') }}</small>
                            </td>
                            
                            <td>
                                <strong>{{ $submission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $submission->student_name_english }}</small><br>
                                <small class="text-muted">
                                    {{ $submission->grade }} ({{ $submission->age }}세)
                                </small>
                            </td>
                            
                            <td>
                                <strong>{{ $submission->institution_name }}</strong><br>
                                <small class="text-muted">{{ $submission->class_name }}</small><br>
                                <small class="text-muted">{{ $submission->region }}</small>
                            </td>
                            
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-camera-video text-primary me-2"></i>
                                    <div>
                                        <div class="fw-bold">
                                            {{ Str::limit($submission->video_file_name, 25) }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $submission->getFormattedFileSizeAttribute() }}
                                        </small>
                                        @if($submission->unit_topic)
                                        <br><small class="text-info">{{ $submission->unit_topic }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                @if($submission->evaluation)
                                    <span class="badge badge-evaluated d-block mb-1">
                                        <i class="bi bi-check-circle"></i> 심사완료
                                    </span>
                                    <small class="text-muted">
                                        총점: <strong>{{ $submission->evaluation->total_score }}/70</strong><br>
                                        <div class="row g-1 mt-1">
                                            <div class="col-6">발음 {{ $submission->evaluation->pronunciation_converted }}%</div>
                                            <div class="col-6">어휘 {{ $submission->evaluation->vocabulary_converted }}%</div>
                                            <div class="col-6">유창성 {{ $submission->evaluation->fluency_converted }}%</div>
                                            <div class="col-6">자신감 {{ $submission->evaluation->confidence_converted }}%</div>
                                            <div class="col-6">주제연결성 {{ $submission->evaluation->topic_connection_converted }}%</div>
                                            <div class="col-6">구성흐름 {{ $submission->evaluation->structure_flow_converted }}%</div>
                                            <div class="col-12">창의성 {{ $submission->evaluation->creativity_converted }}%</div>
                                        </div>
                                    </small>
                                @else
                                    <span class="badge badge-pending d-block">
                                        <i class="bi bi-clock"></i> 심사대기
                                    </span>
                                @endif
                            </td>
                            
                            <td>
                                <div class="d-grid gap-1">
                                    <a href="{{ route('admin.evaluation.show', $submission->id) }}" 
                                       class="btn btn-sm {{ $submission->evaluation ? 'btn-outline-primary' : 'btn-admin' }}">
                                        @if($submission->evaluation)
                                            <i class="bi bi-eye"></i> 보기
                                        @else
                                            <i class="bi bi-clipboard-check"></i> 심사
                                        @endif
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            <div class="mt-4">
                {{ $submissions->appends(request()->query())->links('custom.pagination') }}
            </div>
            
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <h4 class="text-muted mt-3">검색 결과가 없습니다</h4>
                <p class="text-muted">다른 검색 조건을 시도해보세요.</p>
                <a href="{{ route('admin.evaluation.list') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> 전체 목록 보기
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 검색 폼 자동 제출 (디바운스)
    let searchTimeout;
    const searchInput = document.getElementById('search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // 빈 검색어가 아닐 때만 자동 제출
                if (this.value.length >= 2) {
                    document.querySelector('form').submit();
                }
            }, 800);
        });
    }
    
    // 상태 선택 시 자동 제출
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    }
});
</script>
@endsection
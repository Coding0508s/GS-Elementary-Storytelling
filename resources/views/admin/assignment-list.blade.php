@extends('admin.layout')

@section('title', '영상 배정 관리')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-person-check"></i> 영상 심사 관리</h1>
        <p class="text-muted mb-0">업로드된 영상을 심사위원에게 배정하여 중복 심사를 방지합니다.</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
    </a>
</div>

<!-- 통계 카드 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($assignments->count() + $unassignedVideos->count()) }}</h3>
                <p class="card-text text-muted">전체 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-check-circle"></i>
                </div>
                <h3 class="text-success">{{ number_format($assignments->count()) }}</h3>
                <p class="card-text text-muted">심사할 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($unassignedVideos->count()) }}</h3>
                <p class="card-text text-muted">미배정 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-people"></i>
                </div>
                <h3 class="text-info">{{ number_format($admins->count()) }}</h3>
                <p class="card-text text-muted">활성 심사위원</p>
            </div>
        </div>
    </div>
</div>

<!-- 자동 배정 버튼 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-lightning"></i> 자동 심사 배정</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <form action="{{ route('admin.assignment.auto') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-admin">
                        <i class="bi bi-shuffle"></i> 자동 배정 (미배정 영상만)
                    </button>
                </form>
                <small class="text-muted d-block mt-2">미배정 영상을 활성 심사위원에게 균등하게 배정합니다.</small>
            </div>
            <div class="col-md-6 mb-3">
                <form action="{{ route('admin.assignment.reassign.all') }}" method="POST" class="d-inline" 
                      onsubmit="return confirm('⚠️ 주의: 모든 기존 배정과 평가 데이터가 삭제되고 전체 영상이 재배정됩니다. 계속하시겠습니까?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise"></i> 전체 재배정
                    </button>
                </form>
                <small class="text-muted d-block mt-2">모든 영상을 삭제 후 균등하게 재배정합니다. (기존 평가 삭제됨)</small>
            </div>
        </div>
    </div>
</div>

<!-- 배정된 영상 목록 -->
<div class="card admin-card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-list-check"></i> 배정된 영상 목록</h5>
    </div>
    <div class="card-body">
        @if($assignments->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>접수번호</th>
                            <th>학생명</th>
                            <th>기관명</th>
                            <th>배정된 심사위원</th>
                            <th>배정 상태</th>
                            <th>배정일</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->videoSubmission->receipt_number }}</td>
                            <td>
                                <strong>{{ $assignment->videoSubmission->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $assignment->videoSubmission->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $assignment->videoSubmission->institution_name }}<br>
                                <small class="text-muted">{{ $assignment->videoSubmission->class_name }}</small>
                            </td>
                            <td>
                                @if($assignment->admin)
                                    <strong>{{ $assignment->admin->name }}</strong><br>
                                    <small class="text-muted">{{ $assignment->admin->username }}</small>
                                @else
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> 미배정
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($assignment->status === 'assigned')
                                    <span class="badge" style="color:rgb(3, 116, 254);">
                                        <i class="bi bi-clock"></i> 배정됨
                                    </span>
                                @elseif($assignment->status === 'in_progress')
                                    <span class="badge" style="color:rgb(226, 41, 4);">
                                        <i class="bi bi-arrow-clockwise"></i> 심사중
                                    </span>
                                @elseif($assignment->status === 'completed')
                                    <span class="badge" style="color:rgb(79, 223, 7);">
                                        <i class="bi bi-check-circle"></i> 완료
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small>{{ $assignment->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                @if($assignment->status !== 'completed')
                                <form action="{{ route('admin.assignment.cancel', $assignment->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('배정을 취소하시겠습니까?')">
                                        <i class="bi bi-x-circle"></i> 취소
                                    </button>
                                </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 배정된 영상 페이지네이션 -->
            @if($assignments->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $assignments->appends(request()->query())->links('custom.pagination') }}
            </div>
            @endif
        @else
            <div class="text-center py-4">
                <i class="bi bi-inbox display-4 text-muted"></i>
                <p class="text-muted mt-2">배정된 영상이 없습니다.</p>
            </div>
        @endif
    </div>
</div>

<!-- 미배정 영상 목록 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-clock-history"></i> 미배정 영상 목록</h5>
    </div>
    <div class="card-body">
        @if($unassignedVideos->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>접수번호</th>
                            <th>학생명</th>
                            <th>기관명</th>
                            <th>업로드일</th>
                            <th>배정</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unassignedVideos as $video)
                        <tr>
                            <td>{{ $video->receipt_number }}</td>
                            <td>
                                <strong>{{ $video->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $video->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $video->institution_name }}<br>
                                <small class="text-muted">{{ $video->class_name }}</small>
                            </td>
                            <td>
                                <small>{{ $video->created_at->format('m/d H:i') }}</small>
                            </td>
                            <td>
                                <form action="{{ route('admin.assignment.assign') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="video_submission_id" value="{{ $video->id }}">
                                    <div class="input-group input-group-sm" style="width: 200px;">
                                        <select name="admin_id" class="form-select">
                                            <option value="">심사위원 선택</option>
                                            @foreach($admins as $admin)
                                                <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->username }})</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="bi bi-check"></i> 배정
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 미배정 영상 페이지네이션 -->
            @if($unassignedVideos->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $unassignedVideos->appends(request()->query())->links('custom.pagination') }}
            </div>
            @endif
        @else
            <div class="text-center py-4">
                <i class="bi bi-check-circle display-4 text-success"></i>
                <p class="text-muted mt-2">미배정 영상이 없습니다.</p>
                <p class="text-muted">모든 영상이 심사위원에게 배정되었습니다.</p>
            </div>
        @endif
    </div>
</div>

<!-- 빠른 작업 버튼 -->
<div class="text-center mt-4">
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
        <i class="bi bi-speedometer2"></i> 대시보드로 돌아가기
    </a>
    <a href="{{ route('admin.evaluation.list') }}" class="btn btn-outline-info">
        <i class="bi bi-clipboard-check"></i> 심사 관리
    </a>
</div>
@endsection 
@extends('admin.layout')

@section('title', '영상 배정 관리')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-person-check"></i> 영상 심사 관리</h1>
        <p class="text-muted mb-0">업로드된 영상을 심사위원에게 배정하여 중복 심사를 방지합니다. (영상당 최대 2명)</p>
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
                <h3 class="text-primary">{{ number_format($assignedVideos->count() + $unassignedVideos->count()) }}</h3>
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
                <h3 class="text-success">{{ number_format($assignedVideos->count()) }}</h3>
                <p class="card-text text-muted">배정된 영상</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-clock"></i>
                </div>
                <h3 class="text-warning">{{ number_format($unassignedVideos->count() + $partiallyAssignedVideos->count()) }}</h3>
                <p class="card-text text-muted">배정 필요</p>
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
                        <i class="bi bi-shuffle"></i> 자동 배정 (2명씩)
                    </button>
                </form>
                <small class="text-muted d-block mt-2">각 영상을 2명의 심사위원에게 자동으로 배정합니다.</small>
            </div>
            <div class="col-md-6 mb-3">
                <form action="{{ route('admin.assignment.reassign.all') }}" method="POST" class="d-inline" 
                      onsubmit="return confirm('⚠️ 주의: 모든 기존 배정과 평가 데이터가 삭제되고 전체 영상이 재배정됩니다. 계속하시겠습니까?')">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-arrow-clockwise"></i> 전체 재배정
                    </button>
                </form>
                <small class="text-muted d-block mt-2">모든 영상을 삭제 후 랜덤하게 재배정합니다. (기존 평가 삭제됨)</small>
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
        @if($assignedVideos->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>접수번호</th>
                            <th>학생명</th>
                            <th>기관명</th>
                            <th>배정된 심사위원</th>
                            <th>배정 상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignedVideos as $video)
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
                                @if($video->assignments->count() > 0)
                                    @foreach($video->assignments as $assignment)
                                        <div class="mb-1">
                                            <strong>{{ $assignment->admin->name }}</strong>
                                            @if($assignment->status === 'assigned')
                                                <span class="badge bg-primary">배정됨</span>
                                            @elseif($assignment->status === 'in_progress')
                                                <span class="badge bg-warning">심사중</span>
                                            @elseif($assignment->status === 'completed')
                                                <span class="badge bg-success">완료</span>
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-danger">
                                        <i class="bi bi-exclamation-triangle"></i> 미배정
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $assignmentCount = $video->assignments->count();
                                    $completedCount = $video->assignments->where('status', 'completed')->count();
                                @endphp
                                @if($assignmentCount == 2 && $completedCount == 2)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> 모든 심사 완료
                                    </span>
                                @elseif($assignmentCount == 2)
                                    <span class="badge bg-info">
                                        <i class="bi bi-people"></i> 2명 배정 완료
                                    </span>
                                @elseif($assignmentCount == 1)
                                    <span class="badge bg-warning">
                                        <i class="bi bi-person"></i> 1명만 배정됨
                                    </span>
                                @else
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> 미배정
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($video->assignments->count() < 2)
                                    <!-- 추가 배정 가능 -->
                                    <form action="{{ route('admin.assignment.assign') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="video_submission_id" value="{{ $video->id }}">
                                        <select name="admin_id" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                            <option value="">심사위원 선택</option>
                                            @foreach($admins as $admin)
                                                @if(!$video->assignments->contains('admin_id', $admin->id))
                                                    <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus"></i> 배정
                                        </button>
                                    </form>
                                @endif
                                
                                <!-- 배정 취소 버튼들 -->
                                @foreach($video->assignments as $assignment)
                                    <form action="{{ route('admin.assignment.cancel', $assignment->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('{{ $assignment->admin->name }} 심사위원의 배정을 취소하시겠습니까?')">
                                            <i class="bi bi-x"></i> {{ $assignment->admin->name }} 취소
                                        </button>
                                    </form>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- 페이지네이션 -->
            @if($assignedVideos->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $assignedVideos->appends(request()->query())->links('custom.pagination') }}
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

<!-- 부분 배정된 영상 (1명만 배정됨) -->
@if($partiallyAssignedVideos->count() > 0)
<div class="card admin-card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> 추가 배정 필요한 영상 (1명만 배정됨)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-admin table-hover">
                <thead>
                    <tr>
                        <th>접수번호</th>
                        <th>학생명</th>
                        <th>기관명</th>
                        <th>현재 배정된 심사위원</th>
                        <th>추가 배정</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partiallyAssignedVideos as $video)
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
                            @foreach($video->assignments as $assignment)
                                <div class="mb-1">
                                    <strong>{{ $assignment->admin->name }}</strong>
                                    <span class="badge bg-primary">배정됨</span>
                                </div>
                            @endforeach
                        </td>
                        <td>
                            <form action="{{ route('admin.assignment.assign') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="video_submission_id" value="{{ $video->id }}">
                                <select name="admin_id" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                    <option value="">심사위원 선택</option>
                                    @foreach($admins as $admin)
                                        @if(!$video->assignments->contains('admin_id', $admin->id))
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="bi bi-plus"></i> 2번째 심사위원 배정
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- 미배정 영상 목록 -->
<div class="card admin-card">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="bi bi-exclamation-circle"></i> 미배정 영상 목록</h5>
    </div>
    <div class="card-body">
        @if($unassignedVideos->count() > 0)
            <div class="table-responsive">
                <table class="table table-admin table-hover">
                    <thead>
                        <tr>
                            <th>접수번호</th>
                            <th>접수일</th>
                            <th>학생명</th>
                            <th>기관명</th>
                            <th>파일</th>
                            <th>배정</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unassignedVideos as $video)
                        <tr>
                            <td>{{ $video->receipt_number }}</td>
                            <td>{{ $video->created_at->format('m/d H:i') }}</td>
                            <td>
                                <strong>{{ $video->student_name_korean }}</strong><br>
                                <small class="text-muted">{{ $video->student_name_english }}</small>
                            </td>
                            <td>
                                {{ $video->institution_name }}<br>
                                <small class="text-muted">{{ $video->class_name }}</small>
                            </td>
                            <td>
                                <i class="bi bi-camera-video text-primary"></i>
                                {{ Str::limit($video->video_file_name, 20) }}<br>
                                <small class="text-muted">{{ $video->getFormattedFileSizeAttribute() }}</small>
                            </td>
                            <td>
                                <!-- 첫 번째 심사위원 배정 -->
                                <form action="{{ route('admin.assignment.assign') }}" method="POST" class="d-inline mb-2">
                                    @csrf
                                    <input type="hidden" name="video_submission_id" value="{{ $video->id }}">
                                    <select name="admin_id" class="form-select form-select-sm" style="width: 150px;">
                                        <option value="">1차 심사위원 선택</option>
                                        @foreach($admins as $admin)
                                            <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary mt-1">
                                        <i class="bi bi-person-plus"></i> 1차 배정
                                    </button>
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
                <p class="text-success mt-2">모든 영상이 배정되었습니다!</p>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 자동 새로고침 (2분마다)
    setTimeout(function() {
        location.reload();
    }, 120000); // 2분
});
</script>
@endsection
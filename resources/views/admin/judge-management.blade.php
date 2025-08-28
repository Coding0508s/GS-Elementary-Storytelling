@extends('admin.layout')

@section('title', '심사위원 관리')

@section('content')
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="bi bi-people-fill me-2 text-primary"></i>심사위원 관리
                </h2>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 대시보드로 돌아가기
                </a>
            </div>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">전체 심사위원</h6>
                            <h3 class="mb-0">{{ $stats['total_judges'] }}</h3>
                        </div>
                        <i class="bi bi-people-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">활성 심사위원</h6>
                            <h3 class="mb-0">{{ $stats['active_judges'] }}</h3>
                        </div>
                        <i class="bi bi-person-check-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">비활성 심사위원</h6>
                            <h3 class="mb-0">{{ $stats['inactive_judges'] }}</h3>
                        </div>
                        <i class="bi bi-person-x-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">배정 보유</h6>
                            <h3 class="mb-0">{{ $stats['judges_with_assignments'] }}</h3>
                        </div>
                        <i class="bi bi-clipboard-check-fill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 메시지는 layout.blade.php에서 처리됩니다 -->

    <div class="row">
        <!-- 심사위원 추가 폼 -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-person-plus-fill me-2"></i>새 심사위원 추가
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.judge.create') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="bi bi-person-fill me-1"></i>이름
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name') }}"
                                   placeholder="심사위원 이름을 입력하세요" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-at me-1"></i>사용자명
                            </label>
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" 
                                   value="{{ old('username') }}"
                                   placeholder="로그인에 사용할 사용자명" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <small>영문, 숫자, 언더스코어(_)만 사용 가능</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope-fill me-1"></i>이메일
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" 
                                   value="{{ old('email') }}"
                                   placeholder="example@domain.com" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>비밀번호
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" 
                                   placeholder="최소 6자 이상의 비밀번호"
                                   minlength="6" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">
                                <i class="bi bi-lock-fill me-1"></i>비밀번호 확인
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" name="password_confirmation" 
                                   placeholder="비밀번호를 다시 입력하세요"
                                   minlength="6" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-person-plus-fill me-1"></i>심사위원 추가
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 심사위원 목록 -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>심사위원 목록
                    </h5>
                </div>
                <div class="card-body">
                    @if($judges->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>이름</th>
                                        <th>사용자명</th>
                                        <th>이메일</th>
                                        <th>상태</th>
                                        <th>배정</th>
                                        <th>평가</th>
                                        <th>마지막 로그인</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($judges as $judge)
                                    <tr>
                                        <td>{{ $judge->id }}</td>
                                        <td>
                                            <strong>{{ $judge->name }}</strong>
                                        </td>
                                        <td>
                                            <code>{{ $judge->username }}</code>
                                        </td>
                                        <td>{{ $judge->email }}</td>
                                        <td>
                                            @if($judge->is_active)
                                                <span class="badge bg-success">활성</span>
                                            @else
                                                <span class="badge bg-secondary">비활성</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $judge->video_assignments_count }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $judge->evaluations_count }}</span>
                                        </td>
                                        <td>
                                            @if($judge->last_login_at)
                                                <small class="text-muted">
                                                    {{ $judge->last_login_at->format('m/d H:i') }}
                                                </small>
                                            @else
                                                <small class="text-muted">없음</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <!-- 상태 토글 버튼 -->
                                                <form method="POST" 
                                                      action="{{ route('admin.judge.toggle.status', $judge->id) }}" 
                                                      style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm {{ $judge->is_active ? 'btn-warning' : 'btn-success' }}"
                                                            title="{{ $judge->is_active ? '비활성화' : '활성화' }}">
                                                        @if($judge->is_active)
                                                            <i class="bi bi-pause-fill"></i>
                                                        @else
                                                            <i class="bi bi-play-fill"></i>
                                                        @endif
                                                    </button>
                                                </form>

                                                <!-- 삭제 버튼 -->
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal{{ $judge->id }}"
                                                        title="삭제">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </div>

                                            <!-- 삭제 확인 모달 -->
                                            <div class="modal fade" id="deleteModal{{ $judge->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">
                                                                <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                                                                심사위원 삭제 확인
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>정말로 다음 심사위원을 삭제하시겠습니까?</p>
                                                            <div class="alert alert-info">
                                                                <strong>{{ $judge->name }}</strong> ({{ $judge->username }})<br>
                                                                <small>{{ $judge->email }}</small>
                                                            </div>
                                                            
                                                            @if($judge->video_assignments_count > 0 || $judge->evaluations_count > 0)
                                                                <div class="alert alert-warning">
                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                    <strong>주의:</strong> 이 심사위원은 
                                                                    @if($judge->video_assignments_count > 0)
                                                                        <strong>{{ $judge->video_assignments_count }}개의 영상 배정</strong>
                                                                    @endif
                                                                    @if($judge->video_assignments_count > 0 && $judge->evaluations_count > 0)
                                                                        과 
                                                                    @endif
                                                                    @if($judge->evaluations_count > 0)
                                                                        <strong>{{ $judge->evaluations_count }}개의 평가</strong>
                                                                    @endif
                                                                    이 있어 삭제할 수 없습니다.
                                                                </div>
                                                            @else
                                                                <div class="alert alert-danger">
                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                    이 작업은 되돌릴 수 없습니다!
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                                                            @if($judge->video_assignments_count == 0 && $judge->evaluations_count == 0)
                                                                <form method="POST" action="{{ route('admin.judge.delete', $judge->id) }}" style="display: inline;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger">
                                                                        <i class="bi bi-trash-fill me-1"></i>삭제
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <button type="button" class="btn btn-danger" disabled>삭제 불가</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-people-fill fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">등록된 심사위원이 없습니다</h5>
                            <p class="text-muted">왼쪽 폼을 사용하여 새 심사위원을 추가해보세요.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 비밀번호 확인 실시간 체크
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    function checkPasswordMatch() {
        if (confirmPassword.value && password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('비밀번호가 일치하지 않습니다.');
            confirmPassword.classList.add('is-invalid');
        } else {
            confirmPassword.setCustomValidity('');
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    password.addEventListener('input', checkPasswordMatch);
    confirmPassword.addEventListener('input', checkPasswordMatch);

    // 사용자명 입력 시 소문자 및 특수문자 제한
    const usernameField = document.getElementById('username');
    usernameField.addEventListener('input', function() {
        this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
    });
});
</script>
@endsection

@extends('admin.layout')

@section('title', '데이터 초기화')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-exclamation-triangle text-danger"></i> 데이터 초기화</h1>
        <p class="text-muted mb-0">모든 영상, 심사, 배정 데이터가 영구적으로 삭제됩니다</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.dashboard') }}" 
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> 대시보드로
        </a>
    </div>
</div>

<!-- 알림 메시지는 layout.blade.php에서 처리됩니다 -->

<!-- 경고 카드 -->
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0">
            <i class="bi bi-exclamation-triangle"></i> 
            ⚠️ 경고: 이 작업은 되돌릴 수 없습니다!
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <strong>주의사항:</strong>
            <ul class="mb-0 mt-2">
                <li>모든 영상 제출 데이터가 영구적으로 삭제됩니다</li>
                <li>모든 심사 결과가 영구적으로 삭제됩니다</li>
                <li>모든 배정 정보가 영구적으로 삭제됩니다</li>
                <li>S3에 저장된 모든 영상 파일이 삭제됩니다</li>
                <li>관리자 계정은 유지됩니다</li>
                <li><strong class="text-danger">이 작업은 되돌릴 수 없습니다!</strong></li>
            </ul>
        </div>
    </div>
</div>

<!-- 현재 데이터 통계 -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100 border-primary">
            <div class="card-body text-center">
                <div class="display-4 text-primary mb-2">
                    <i class="bi bi-camera-video"></i>
                </div>
                <h3 class="text-primary">{{ number_format($stats['total_submissions']) }}</h3>
                <p class="card-text text-muted">영상 제출</p>
                <small class="text-danger">삭제될 항목</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100 border-success">
            <div class="card-body text-center">
                <div class="display-4 text-success mb-2">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <h3 class="text-success">{{ number_format($stats['total_evaluations']) }}</h3>
                <p class="card-text text-muted">심사 결과</p>
                <small class="text-danger">삭제될 항목</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100 border-info">
            <div class="card-body text-center">
                <div class="display-4 text-info mb-2">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h3 class="text-info">{{ number_format($stats['total_assignments']) }}</h3>
                <p class="card-text text-muted">영상 배정</p>
                <small class="text-danger">삭제될 항목</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card stats-card h-100 border-warning">
            <div class="card-body text-center">
                <div class="display-4 text-warning mb-2">
                    <i class="bi bi-cloud"></i>
                </div>
                <h3 class="text-warning">{{ number_format($stats['s3_files']) }}</h3>
                <p class="card-text text-muted">S3 파일</p>
                <small class="text-danger">삭제될 항목</small>
            </div>
        </div>
    </div>
</div>

<!-- 확인 절차 폼 -->
<div class="card admin-card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-shield-lock"></i> 
            보안 확인 절차
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.reset.execute') }}" method="POST" id="reset-form">
            @csrf
            
            <!-- 1단계: 확인 문구 입력 -->
            <div class="mb-4">
                <label for="confirmation_text" class="form-label">
                    <strong>1단계: 확인 문구 입력</strong>
                </label>
                <p class="text-muted">
                    아래 문구를 정확히 입력해주세요:
                    <br><code class="text-danger">"모든 데이터를 영구적으로 삭제합니다"</code>
                </p>
                <input type="text" 
                       class="form-control @error('confirmation_text') is-invalid @enderror" 
                       id="confirmation_text" 
                       name="confirmation_text"
                       placeholder="확인 문구를 정확히 입력하세요"
                       value="{{ old('confirmation_text') }}"
                       required>
                @error('confirmation_text')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- 2단계: 관리자 비밀번호 입력 -->
            <div class="mb-4">
                <label for="admin_password" class="form-label">
                    <strong>2단계: 관리자 비밀번호 확인</strong>
                </label>
                <p class="text-muted">
                    현재 로그인된 관리자 계정의 비밀번호를 입력해주세요.
                </p>
                <input type="password" 
                       class="form-control @error('admin_password') is-invalid @enderror" 
                       id="admin_password" 
                       name="admin_password"
                       placeholder="관리자 비밀번호"
                       required>
                @error('admin_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <!-- 최종 확인 체크박스 -->
            <div class="mb-4">
                <div class="form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="final_confirmation" 
                           required>
                    <label class="form-check-label text-danger" for="final_confirmation">
                        <strong>위의 모든 내용을 이해했으며, 데이터 초기화에 동의합니다.</strong>
                    </label>
                </div>
            </div>
            
            <!-- 제출 버튼 -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="{{ route('admin.dashboard') }}" 
                   class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> 취소
                </a>
                <button type="submit" 
                        class="btn btn-danger btn-lg" 
                        id="submit-btn"
                        disabled>
                    <i class="bi bi-trash"></i> 데이터 초기화 실행
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmationText = document.getElementById('confirmation_text');
    const adminPassword = document.getElementById('admin_password');
    const finalConfirmation = document.getElementById('final_confirmation');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('reset-form');
    
    // 입력 필드 검증
    function validateForm() {
        const isTextValid = confirmationText.value === '모든 데이터를 영구적으로 삭제합니다';
        const isPasswordFilled = adminPassword.value.length > 0;
        const isChecked = finalConfirmation.checked;
        
        submitBtn.disabled = !(isTextValid && isPasswordFilled && isChecked);
        
        // 확인 문구 검증 시각적 피드백
        if (confirmationText.value.length > 0) {
            if (isTextValid) {
                confirmationText.classList.remove('is-invalid');
                confirmationText.classList.add('is-valid');
            } else {
                confirmationText.classList.remove('is-valid');
                confirmationText.classList.add('is-invalid');
            }
        } else {
            confirmationText.classList.remove('is-valid', 'is-invalid');
        }
    }
    
    // 입력 이벤트 리스너
    confirmationText.addEventListener('input', validateForm);
    adminPassword.addEventListener('input', validateForm);
    finalConfirmation.addEventListener('change', validateForm);
    
    // 폼 제출 시 최종 확인
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const confirmMessage = 
            '정말로 모든 데이터를 초기화하시겠습니까?\n\n' +
            '삭제될 데이터:\n' +
            `- 영상 제출: {{ $stats['total_submissions'] }}개\n` +
            `- 심사 결과: {{ $stats['total_evaluations'] }}개\n` +
            `- 영상 배정: {{ $stats['total_assignments'] }}개\n` +
            `- S3 파일: {{ $stats['s3_files'] }}개\n\n` +
            '이 작업은 되돌릴 수 없습니다!';
        
        if (confirm(confirmMessage)) {
            // 제출 버튼 비활성화
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 초기화 중...';
            
            // 실제 폼 제출
            form.submit();
        }
    });
    
    // 초기 검증
    validateForm();
});
</script>
@endsection

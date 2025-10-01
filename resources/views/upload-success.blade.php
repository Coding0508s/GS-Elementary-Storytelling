@extends('layouts.app')

@section('title', '업로드 완료 - GS Elementary Speech Contest')

@section('content')
<div class="progress-indicator">
    <div class="progress-step inactive">1</div>
    <div class="progress-line"></div>
    <div class="progress-step inactive">2</div>
    <div class="progress-line"></div>
    <div class="progress-step active">3</div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="text-center">
            <!-- 성공 아이콘 -->
            <div class="mb-4">
                <div style="font-size: 4rem; color: #28a745;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
            
            <!-- 성공 메시지 -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <h2 class="text-success mb-4">
                        <i class="bi bi-party-popper"></i> 업로드 완료!
                    </h2>
                    
                    @if($submission)
                    <div class="alert alert-info mb-4">
                        <h5 class="mb-2"><i class="bi bi-receipt"></i> 접수번호</h5>
                        <h3 class="mb-0 text-primary">{{ $submission->receipt_number }}</h3>
                        <small class="text-muted">접수번호를 기록해두시면 문의 시 도움이 됩니다.</small>
                    </div>
                    @endif
                    
                    <p class="lead mb-4">
                        Storytelling 영상이 성공적으로 업로드되었습니다.
                    </p>
                    
                    <div class="alert alert-success">
                        <i class="bi bi-chat-dots"></i>
                        <strong>SMS 알림 발송 완료</strong><br>
                        입력해주신 학부모 전화번호로 업로드 완료 SMS를 발송했습니다.<br>
                        <small class="text-muted">접수번호가 포함된 SMS를 몇 분 내에 받으실 수 있습니다.</small>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-14">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-clock"></i> 업로드 시간</h6>
                                    <p class="mb-0">{{ now()->format('Y년 m월 d일 H:i') }}</p>
                                </div>
                            </div>
                        </div>
                       <!--  <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6><i class="bi bi-hash"></i> 접수번호</h6>
                                    <p class="mb-0">
                                        @if(session('submission_id'))
                                            #{{ str_pad(session('submission_id'), 6, '0', STR_PAD_LEFT) }}
                                        @else
                                            #{{ str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div> -->
                    </div>
                    
                    <!-- <div class="mt-5">
                        <h5><i class="bi bi-trophy"></i> Speech Contest 안내</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-calendar-check fs-2 text-primary"></i>
                                        <h6 class="mt-2">심사 기간</h6>
                                        <p class="small">업로드 마감 후<br>2주간 심사 진행</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-award fs-2 text-warning"></i>
                                        <h6 class="mt-2">시상</h6>
                                        <p class="small">우수상, 장려상<br>다양한 상품 준비</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-bell fs-2 text-info"></i>
                                        <h6 class="mt-2">결과 발표</h6>
                                        <p class="small">개별 연락 및<br>홈페이지 공지</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    
                    <!-- <div class="mt-4">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>중요 안내</strong><br>
                            • 접수된 영상은 수정 또는 재업로드가 불가능합니다.<br>
                            • 문의사항은 각 교육기관을 통해 연락해주세요.<br>
                            • 개인정보는 대회 종료 후 안전하게 폐기됩니다.
                        </div>
                    </div> -->
                    
                    <!-- 자동 리다이렉트 안내 -->
                    <!-- <div class="mt-4 mb-3">
                        <div class="alert alert-success">
                            <i class="bi bi-clock"></i>
                            <span id="redirect-message">10초 후 자동으로 GrapeSEED 스토리텔링 콘테스트 페이지로 이동합니다...</span>
                            <div class="progress mt-2" style="height: 4px;">
                                <div id="redirect-progress" class="progress-bar bg-success" style="width: 0%;"></div>
                            </div>
                        </div>
                    </div> -->
                    
                    <div class="mt-4">
                        <a href="{{ route('privacy.consent') }}" class="btn btn-primary me-3">
                            <i class="bi bi-plus-circle"></i> 다른 자녀 추가 업로드
                        </a>
                      <!--   <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="bi bi-printer"></i> 확인증 인쇄
                        </button> -->
                    </div>
                </div>
            </div>
            
            <!-- 감사 메시지 -->
            <div class="mt-4">
                <p class="text-muted">
                    <i class="bi bi-heart-fill text-danger"></i>
                   참여해주셔서 감사합니다!
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 페이지 로드 시 축하 애니메이션
    setTimeout(function() {
        const successIcon = document.querySelector('.bi-check-circle-fill');
        if (successIcon) {
            successIcon.style.animation = 'bounce 0.6s ease-in-out';
        }
    }, 500);
    
    // 자동 리다이렉트 기능
    /* let redirectTimer;
    let countdownSeconds = 10;
    
    const redirectMessage = document.getElementById('redirect-message');
    const redirectProgress = document.getElementById('redirect-progress');
    
    // 카운트다운 시작
    function startCountdown() {
        redirectTimer = setInterval(function() {
            countdownSeconds--;
            redirectMessage.textContent = countdownSeconds + '초 후 자동으로 GrapeSEED 스토리텔링 콘테스트 페이지로 이동합니다...';
            
            // 프로그레스 바 업데이트
            const progress = ((10 - countdownSeconds) / 10) * 100;
            redirectProgress.style.width = progress + '%';
            
            if (countdownSeconds <= 0) {
                clearInterval(redirectTimer);
                window.location.href = 'https://grapeseed.com/kr/storytelling';
            }
        }, 1000);
    }
    
    // 2초 후 카운트다운 시작 (사용자가 성공 메시지를 읽을 시간 제공)
    setTimeout(startCountdown, 2000);
    
    // 페이지 새로고침 방지 (뒤로가기 시)
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function() {
        window.history.pushState(null, null, window.location.href);
    };
});
 */
// CSS 애니메이션 추가
const style = document.createElement('style');
style.textContent = `
    @keyframes bounce {
        0%, 20%, 60%, 100% {
            transform: translateY(0);
        }
        40% {
            transform: translateY(-10px);
        }
        80% {
            transform: translateY(-5px);
        }
    }
    
    @media print {
        .btn, .alert-warning, .progress-indicator {
            display: none !important;
        }
        .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
        }
    }
`;
document.head.appendChild(style);
</script>
@endsection 
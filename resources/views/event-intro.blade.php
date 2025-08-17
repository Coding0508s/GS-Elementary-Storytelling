@extends('layouts.event')

@section('title', '2025 GrapeSEED Speak and Shine')

@section('content')
<div class="container-fluid p-0">
    <div class="row justify-content-center min-vh-100">
        <div class="col-12 d-flex align-items-center justify-content-center">
            <div class="d-flex flex-column align-items-center justify-content-center gap-4">
                <!-- 이벤트 소개 텍스트 -->
                <div class="event-intro-text">
                    <div class="text-center">
                        <h1 class="display-4 fw-bold text-white mb-4">
                            <span class="typing-text">Welcome to 2025 GrapeSEED</span><br>
                            <span class="text-warning wave-text">
                                <span>S</span><span>p</span><span>e</span><span>a</span><span>k</span>
                                <span>&nbsp;</span>
                                <span>a</span><span>n</span><span>d</span>
                                <span>&nbsp;</span>
                                <span>S</span><span>h</span><span>i</span><span>n</span><span>e</span><span>!</span>
                            </span>
                        </h1>
                        <!-- div class="subtitle-box rounded-3 p-4 mb-4 shadow-lg"> -->
                            <p class="h5 text-white mb-3 fade-in-up">
                                <strong class="fw-bold pulse-text">Speak and Shine</strong>에서 그동안 배운<br>
                                말하기 실력을 마음 껏 뽐내보세요!
                            </p>
                            <div class="participant-info rounded-2 p-2 slide-in-left">
                                <h6 class="text-white mb-2">
                                    <i class="bi bi-people-fill me-2 bounce-icon"></i>참가 대상:
                                </h6>
                                <p class="mb-0 text-white">
                                    GrapeSEED를 배우는 초등부 학생 전체
                                </p>
                            </div>
                        <!-- </div> -->
                    </div>
                </div>
                
                <!-- 참여하기 버튼 -->
                <div class="participation-section">
                    <a href="{{ route('privacy.consent') }}" 
                       class="btn btn-primary px-4 py-2 rounded-pill shadow participation-btn float-in">
                        <i class="bi bi-play-circle-fill me-2 spin-on-hover"></i>
                        <strong>참여하기</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .min-vh-100 {
        min-height: 100vh;
    }
    
    .event-intro-text {
        animation: fadeInUp 1s ease-out;
        max-width: 800px;
    }
    
    .event-intro-text h1 {
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        font-family: 'Noto Sans KR', sans-serif;
    }
    
    .subtitle-box {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .participant-info {
        background: rgba(255,255,255,0.1);
        border-left: 4px solid #ffdd59;
        backdrop-filter: blur(5px);
        
    }
    
    .participation-btn {
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: linear-gradient(45deg, #ff6b35, #f7931e);
        border: none;
        min-width: 160px;
        align-self: center;
        color: white !important;
        font-weight: bold;
        position: relative;
        overflow: hidden;
    }
    
    .participation-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .participation-btn:hover::before {
        left: 100%;
    }
    
    .participation-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 10px 25px rgba(247, 147, 30, 0.5) !important;
        background: linear-gradient(45deg, #f7931e, #e8850f);
        color: white !important;
    }
    
    /* 버튼 플로트인 효과 */
    .float-in {
        animation: floatIn 1s ease-out 2.5s both;
    }
    
    @keyframes floatIn {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.8);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    /* 아이콘 회전 효과 */
    .spin-on-hover {
        transition: transform 0.3s ease;
    }
    
    .participation-btn:hover .spin-on-hover {
        transform: rotate(360deg);
    }
    
    .participation-section {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .text-warning {
        -webkit-text-stroke-width: 1px;
        -webkit-text-stroke-color:rgb(242, 203, 27);
        
       
    }
    
    /* 타이핑 효과 */
    .typing-text {
        animation: typing 3s steps(30, end), blink-cursor 0.75s step-end infinite;
        border-right: 3px solid #ffdd59;
        white-space: nowrap;
        overflow: hidden;
        display: inline-block;
    }
    
    @keyframes typing {
        from { width: 0; }
        to { width: 100%; }
    }
    
    @keyframes blink-cursor {
        from, to { border-color: transparent; }
        50% { border-color: #ffdd59; }
    }
    
        /* 파도 효과 */
    .wave-text {
        display: inline-block;
        animation: fadeInUp 1s ease-out 0.5s both;
    }
    
    .wave-text span {
        display: inline-block;
        animation: wave 2s ease-in-out infinite;
        animation-delay: calc(var(--i) * 0.1s);
        text-shadow: 2px 2px 4px rgba(255, 221, 89, 0.5);
    }
    
    .wave-text span:nth-child(1) { --i: 0; }
    .wave-text span:nth-child(2) { --i: 1; }
    .wave-text span:nth-child(3) { --i: 2; }
    .wave-text span:nth-child(4) { --i: 3; }
    .wave-text span:nth-child(5) { --i: 4; }
    .wave-text span:nth-child(6) { --i: 5; }
    .wave-text span:nth-child(7) { --i: 6; }
    .wave-text span:nth-child(8) { --i: 7; }
    .wave-text span:nth-child(9) { --i: 8; }
    .wave-text span:nth-child(10) { --i: 9; }
    .wave-text span:nth-child(11) { --i: 10; }
    .wave-text span:nth-child(12) { --i: 11; }
    .wave-text span:nth-child(13) { --i: 12; }
    .wave-text span:nth-child(14) { --i: 13; }
    .wave-text span:nth-child(15) { --i: 14; }
    .wave-text span:nth-child(16) { --i: 15; }
    
    @keyframes wave {
        0%, 40%, 100% {
            transform: translateY(0) scale(1);
            color: #ffdd59;
        }
        20% {
            transform: translateY(-10px) scale(1.1);
            color: #ffc658;
            text-shadow: 0 0 15px #ffdd59, 0 0 25px #ffdd59;
        }
    }
    
    /* 펄스 효과 */
    .pulse-text {
        animation: pulse 2s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* 페이드인 업 */
    .fade-in-up {
        animation: fadeInUp 1s ease-out 1.5s both;
    }
    
    /* 왼쪽에서 슬라이드인 */
    .slide-in-left {
        animation: slideInLeft 1s ease-out 2s both;
    }
    
    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-50px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    /* 아이콘 바운스 */
    .bounce-icon {
        animation: bounce 2s ease-in-out infinite;
    }
    
    @keyframes bounce {
        0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
        40% { transform: translateY(-5px); }
        60% { transform: translateY(-3px); }
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 768px) {
        .d-flex.flex-column {
            gap: 2rem !important;
        }
        
        .event-intro-text {
            max-width: 90%;
        }
        
        .event-intro-text h1 {
            font-size: 2.5rem !important;
        }
        
        .participation-btn {
            font-size: 0.9rem;
            padding: 0.6rem 1.5rem !important;
            min-width: 120px;
        }
    }
    
    @media (max-width: 480px) {
        .event-intro-text h1 {
            font-size: 2rem !important;
        }
        
        .subtitle-box {
            padding: 1.5rem !important;
        }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 참여하기 버튼 클릭 시 부드러운 전환 효과
    document.querySelector('.participation-btn').addEventListener('click', function(e) {
        e.preventDefault(); // 기본 링크 동작 방지
        
        const targetUrl = this.href;
        
        // 버튼 클릭 효과
        this.style.transform = 'scale(0.95)';
        this.style.opacity = '0.7';
        
        // 페이지 페이드아웃 효과
        document.body.style.transition = 'opacity 0.5s ease-out';
        document.body.style.opacity = '0';
        
        // 0.5초 후 페이지 이동
        setTimeout(function() {
            window.location.href = targetUrl;
        }, 500);
        
        console.log('참여하기 버튼 클릭됨 - 부드러운 전환 시작');
    });
    
    // 페이지 로드 시 페이드인 효과
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease-in';
    
    setTimeout(function() {
        document.body.style.opacity = '1';
    }, 100);
});
</script>
@endsection

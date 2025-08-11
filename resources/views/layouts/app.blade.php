<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GS Elementary Speech Contest')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
       /*  body {
            background: linear-gradient(to top left,rgb(93, 14, 122),rgb(253, 253, 253),rgb(117, 57, 201),rgb(254, 254, 255), #8b5cf6,rgb(255, 255, 255),rgb(255, 255, 255),rgb(192, 118, 226));
            background-size: 100%;
            animation: gradientShift 12s ease infinite;
            min-height: 100vh;
            font-family: 'Noto Sans KR', sans-serif;
            position: relative;
            overflow-x: hidden;
        }
         */
        /* 애니메이션 그라데이션 */
        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            25% {
                background-position: 100% 0%;
            }
            50% {
                background-position: 100% 100%;
            }
            75% {
                background-position: 0% 100%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        /* 떠다니는 기하학적 요소들 */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }
        
        .shape:nth-child(1) {
            top: 20%;
            left: 10%;
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
            border-radius: 50%;
            animation-delay: 0s;
            animation-duration: 25s;
        }
        
        .shape:nth-child(2) {
            top: 80%;
            left: 80%;
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #96ceb4, #ffecd2);
            transform: rotate(45deg);
            animation-delay: 5s;
            animation-duration: 30s;
        }
        
        .shape:nth-child(3) {
            top: 40%;
            left: 90%;
            width: 100px;
            height: 100px;
            background: linear-gradient(45deg, #a8edea, #fed6e3);
            border-radius: 20px;
            animation-delay: 10s;
            animation-duration: 35s;
        }
        
        .shape:nth-child(4) {
            top: 60%;
            left: 5%;
            width: 70px;
            height: 70px;
            background: linear-gradient(45deg, #d299c2, #fef9d7);
            border-radius: 50%;
            animation-delay: 15s;
            animation-duration: 28s;
        }
        
        .shape:nth-child(5) {
            top: 10%;
            left: 70%;
            width: 90px;
            height: 90px;
            background: linear-gradient(45deg, #89f7fe, #66a6ff);
            clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: 8s;
            animation-duration: 32s;
        }
        
        .shape:nth-child(6) {
            top: 90%;
            left: 30%;
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #ec4899, #c084fc);
            border-radius: 50% 10px;
            animation-delay: 12s;
            animation-duration: 26s;
            box-shadow: 0 0 18px rgba(236, 72, 153, 0.4);
        }
        
        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.1;
            }
            90% {
                opacity: 0.1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        /* 파티클 효과 */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }
        
        .particle:nth-child(odd) {
            background: rgba(255, 255, 255, 0.6);
            animation-duration: 20s;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(1);
                opacity: 0;
            }
        }
        .contest-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            box-shadow: 0 25px 80px rgba(168,85,247,0.3), 0 15px 40px rgba(236,72,153,0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem;
            margin: 1rem auto;
            max-width: 700px;
            position: relative;
            z-index: 10;
        }
        .header-section {
            text-align: center;
            padding: 0.5rem 0 1rem 0;
            border-bottom: 2px solid #f8f9fa;
            margin-bottom: 1rem;
        }
        .logo-container {
            margin-bottom: 0.5rem;
        }
        .logo-container img {
            max-height: 60px;
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .header-section h1 {
            color: #2c3e50;
            font-weight: bold;
            margin-bottom: 0.3rem;
            font-size: 1.5rem;
        }
        .header-section p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 1rem;
        }
        .contest-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        .form-section {
            padding: 0.5rem 0;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 8px 12px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .progress-step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 8px;
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }
        .progress-step.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }
        .progress-step.inactive {
            background: #dee2e6;
            color: #6c757d;
        }
        .progress-line {
            height: 2px;
            width: 40px;
            background: #dee2e6;
            align-self: center;
        }
        .file-upload-area {
            border: 3px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .file-upload-area:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .file-upload-area.dragover {
            border-color: #667eea;
            background-color: #f0f4ff;
        }
        .footer-section {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.5rem;
            border-top: 1px solid #f8f9fa;
            color: #6c757d;
            font-size: 0.85rem;
        }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- 떠다니는 기하학적 요소들 -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>
    
    <!-- 파티클 효과 -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <div class="contest-container">
            <div class="header-section">
                <div class="logo-container">
                    <img src="{{ asset('images/grape-seed-logo.png') }}" alt="GrapeSEED English for Children" class="grape-seed-logo">
                </div>
                <h1><!-- <i class="bi bi-camera-video"></i> --> <span class="contest-title">예비 초등 Storytelling Contest</span></h1>
                <p>GrapeSEED 학생들의 특별한 2025 Speech Contest</p>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <div class="form-section">
                @yield('content')
            </div>
            
            <div class="footer-section">
                <p>&copy; 2025 GrapeSEED English for Children. All rights reserved.</p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 파티클 효과 스크립트 -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;
            
            function createParticle() {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // 랜덤 위치 설정
                particle.style.left = Math.random() * 100 + '%';
                
                // 랜덤 애니메이션 지연
                particle.style.animationDelay = Math.random() * 15 + 's';
                
                // 랜덤 크기
                const size = Math.random() * 3 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                particlesContainer.appendChild(particle);
                
                // 애니메이션 완료 후 파티클 제거 및 재생성
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.parentNode.removeChild(particle);
                    }
                    createParticle();
                }, 15000 + Math.random() * 5000);
            }
            
            // 초기 파티클 생성
            for (let i = 0; i < particleCount; i++) {
                setTimeout(() => createParticle(), i * 300);
            }
        });
        
        // 마우스 이동에 따른 추가 효과 (선택사항)
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.5;
                const x = (mouseX * speed);
                const y = (mouseY * speed);
                
                shape.style.transform += ` translate(${x}px, ${y}px)`;
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html> 
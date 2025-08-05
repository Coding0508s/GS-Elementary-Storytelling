<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '관리자 페이지') - GS Elementary Speech Contest</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Noto Sans KR', sans-serif;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .logo-container {
            text-align: center;
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 1rem;
        }
        
        .logo-container img {
            max-height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
            margin-bottom: 0.5rem;
        }
        
        .logo-container .brand-text {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            margin: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.2);
        }
        
        .main-content {
            padding: 2rem;
        }
        
        .admin-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .admin-card .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }
        
        .stats-card {
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .btn-admin {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .table-admin {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .table-admin th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
        }
        
        .badge-evaluated {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        .badge-pending {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
        }
        
        .score-input {
            width: 80px;
            text-align: center;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .user-info .user-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-info .user-role {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }
    </style>
    
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 사이드바 -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="logo-container">
                        <img src="{{ asset('images/grape-seed-logo.png') }}" alt="GrapeSEED English for Children" class="grape-seed-logo">
                        <p class="brand-text">Speech Contest</p>
                    </div>
                    
                    @auth('admin')
                    <div class="user-info">
                        <div class="user-name">{{ Auth::guard('admin')->user()->name }}</div>
                        <div class="user-role">
                            @if(Auth::guard('admin')->user()->isAdmin())
                                <i class="bi bi-shield-check"></i> 관리자
                            @else
                                <i class="bi bi-person-check"></i> 심사위원
                            @endif
                        </div>
                        <small class="text-muted d-block mt-1">
                            마지막 로그인: {{ Auth::guard('admin')->user()->last_login_at ? Auth::guard('admin')->user()->last_login_at->format('m/d H:i') : '처음' }}
                        </small>
                    </div>
                    @endauth
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> 대시보드
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.evaluation.*') ? 'active' : '' }}" 
                           href="{{ route('admin.evaluation.list') }}">
                            <i class="bi bi-clipboard-check"></i> 심사 관리
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.assignment.*') ? 'active' : '' }}" 
                           href="{{ route('admin.assignment.list') }}">
                            <i class="bi bi-person-check"></i> 영상 배정 관리
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.statistics') ? 'active' : '' }}" 
                           href="{{ route('admin.statistics') }}">
                            <i class="bi bi-graph-up"></i> 통계 보기
                        </a>
                        
                        <a class="nav-link" href="{{ route('admin.download.excel') }}">
                            <i class="bi bi-download"></i> 데이터 다운로드
                        </a>
                        
                        <hr class="text-white-50">
                        
                        <a class="nav-link" href="{{ url('/') }}" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> 대회 페이지
                        </a>
                        
                        <form action="{{ route('admin.logout') }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-light btn-sm w-100">
                                <i class="bi bi-box-arrow-right"></i> 로그아웃
                            </button>
                        </form>
                    </nav>
                </div>
            </div>
            
            <!-- 메인 콘텐츠 -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <!-- 알림 메시지 -->
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
                    
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>
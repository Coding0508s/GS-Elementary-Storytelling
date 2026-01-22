<!DOCTYPE html>
<html class="light" lang="ko">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>관리자 로그인 - GrapeSEED Staff Portal</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Work Sans", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        /* 에러 메시지 스타일 */
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-close {
            float: right;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 1.25rem;
            line-height: 1;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white h-screen overflow-hidden">
<div class="flex h-full w-full flex-col md:flex-row">
    <div class="relative hidden md:block md:w-1/2 lg:w-1/2 h-full bg-slate-200">
        <img alt="GrapeSEED Education" class="absolute inset-0 h-full w-full object-cover" src="{{ asset('images/KakaoTalk_20210105_143627244.png') }}" onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, #667eea 0%, #764ba2 100%)';"/>
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
        <div class="absolute bottom-12 left-12 text-white max-w-md">
            <p class="text-lg font-medium tracking-wide mb-2 opacity-90">Empowering Education</p>
            <h2 class="text-3xl font-bold leading-tight">Bringing English teaching materials to every school.</h2>
        </div>
    </div>
    <div class="flex w-full md:w-1/2 lg:w-1/2 h-full flex-col justify-center items-center bg-white dark:bg-background-dark p-6 md:p-12 lg:p-24 relative overflow-y-auto">
        <div class="absolute top-6 right-6 flex items-center gap-2 text-slate-500 dark:text-slate-400 text-sm font-medium cursor-pointer hover:text-primary transition-colors">
            <span class="material-symbols-outlined text-[20px]">language</span>
            <span>English / 한국어</span>
        </div>
        <div class="w-full max-w-sm flex flex-col gap-8">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 mb-2 text-primary">
                    <span class="material-symbols-outlined text-4xl">school</span>
                    <span class="text-xl font-black tracking-tight text-slate-900 dark:text-white">GrapeSEED English Education</span>
                </div>
                <h1 class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">
                    Staff Portal
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-base font-normal">
                    관리자/심사위원 페이지에 접속하세요.
                </p>
            </div>

            <!-- 에러 메시지 표시 -->
            @if(session('error'))
                <div class="alert-error">
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1rem;">error</span>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert-success">
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    <span class="material-symbols-outlined" style="vertical-align: middle; font-size: 1rem;">check_circle</span>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert-error">
                    <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.login.process') }}" method="POST" id="loginForm" class="flex flex-col gap-5">
                @csrf
                
                <label class="flex flex-col gap-1.5">
                    <span class="text-slate-900 dark:text-slate-200 text-sm font-medium leading-normal">Admin ID</span>
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 material-symbols-outlined text-[20px]">badge</span>
                        <input 
                            class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/20 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary h-12 placeholder:text-slate-400 pl-11 pr-4 text-base font-normal leading-normal transition-all duration-200" 
                            placeholder="admin.id" 
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            required 
                            autocomplete="username"
                            autofocus/>
                    </div>
                </label>
                
                <label class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-900 dark:text-slate-200 text-sm font-medium leading-normal">Password</span>
                    </div>
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 material-symbols-outlined text-[20px]">lock</span>
                        <input 
                            class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/20 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary h-12 placeholder:text-slate-400 pl-11 pr-4 text-base font-normal leading-normal transition-all duration-200" 
                            placeholder="••••••••••••" 
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"/>
                    </div>
                </label>

                <label class="flex flex-col gap-1.5">
                    <span class="text-slate-900 dark:text-slate-200 text-sm font-medium leading-normal">Role (역할)</span>
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 material-symbols-outlined text-[20px]">person</span>
                        <select 
                            class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/20 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 focus:border-primary h-12 pl-11 pr-4 text-base font-normal leading-normal transition-all duration-200 appearance-none cursor-pointer" 
                            name="role"
                            required>
                            <option value="">역할을 선택하세요</option>
                            <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>관리자</option>
                            <option value="judge" {{ old('role') == 'judge' ? 'selected' : '' }}>심사위원</option>
                        </select>
                        <span class="absolute right-4 text-slate-400 material-symbols-outlined text-[20px] pointer-events-none">arrow_drop_down</span>
                    </div>
                </label>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input class="peer h-4 w-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-primary focus:ring-0 focus:ring-offset-0 transition-colors cursor-pointer" type="checkbox" name="remember"/>
                        </div>
                        <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 transition-colors">Keep me logged in</span>
                    </label>
                    <a class="text-sm font-semibold text-primary hover:text-blue-700 transition-colors" href="#">
                        Forgot password?
                    </a>
                </div>
                
                <button type="submit" id="loginButton" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-12 px-5 bg-primary hover:bg-blue-600 active:scale-[0.99] transition-all text-white text-base font-bold leading-normal tracking-[0.015em] shadow-sm">
                    <span class="truncate">Login</span>
                </button>
            </form>
            
            <div class="flex flex-col gap-4 mt-4 border-t border-slate-100 dark:border-slate-800 pt-6">
                <p class="text-center text-sm text-slate-500 dark:text-slate-400">
                    Having trouble? <a class="font-medium text-slate-700 dark:text-slate-200 underline hover:text-primary transition-colors" href="{{ url('/') }}">대회 페이지로 돌아가기</a>
                </p>
                <div class="flex items-center justify-center gap-1 text-xs text-slate-400 dark:text-slate-600">
                    <span class="material-symbols-outlined text-[14px]">lock</span>
                    <span>Secure Connection | 256-bit SSL Encrypted</span>
                </div>
                <p class="text-center text-xs text-slate-300 dark:text-slate-700 mt-2">
                    © 2024 Education Korea Inc. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // 폼 제출 시 버튼 비활성화
    document.getElementById('loginForm').addEventListener('submit', function() {
        const submitBtn = document.getElementById('loginButton');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="truncate">로그인 중...</span>';
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
    });
</script>
</body>
</html>

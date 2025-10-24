<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class RegisterMonitoringMiddleware
{
    /**
     * 모니터링 미들웨어를 글로벌 미들웨어로 등록
     */
    public function handle(Request $request, \Closure $next)
    {
        // 모니터링 미들웨어를 글로벌 미들웨어 스택에 추가
        if (!in_array(MonitoringMiddleware::class, App::make('Illuminate\Contracts\Http\Kernel')->getMiddleware())) {
            App::make('Illuminate\Contracts\Http\Kernel')->pushMiddleware(MonitoringMiddleware::class);
        }
        
        return $next($request);
    }
}

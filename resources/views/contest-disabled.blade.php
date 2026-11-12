@extends('layouts.event')

@section('title', '2026 GrapeSEED 학부모 웨비나 신청 마감')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/grapeseed-tokens.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" crossorigin>
<link rel="stylesheet" href="{{ asset('css/webinar-closed.css') }}">
@endsection

@section('content')
<div class="webinar-closed">
    <div class="closed-content">
        <section class="closed-card" aria-labelledby="closed-heading">
            <img class="closed-illustration" src="{{ asset('images/webinar-registration-closed.png') }}"
                 width="1536" height="1024" alt="" decoding="async">
            <h1 id="closed-heading">
                <span class="closed-brand">2026 GrapeSEED</span>
                <span class="closed-title">학부모 웨비나 신청 마감</span>
            </h1>
            <p class="closed-description">학부모 웨비나 참가 신청이 마감되었습니다.</p>
            <div class="closed-thanks">
                <p>학부모 웨비나에 관심을 가져주셔서<br class="closed-mobile-break"> 감사합니다!</p>
                <i class="bi bi-heart" aria-hidden="true"></i>
            </div>
        </section>
        <p class="closed-updated">
            <i class="bi bi-clock" aria-hidden="true"></i>
            마지막 확인: <time datetime="{{ now()->toIso8601String() }}">{{ now()->format('Y년 m월 d일 H:i') }}</time>
        </p>
    </div>
</div>
@endsection

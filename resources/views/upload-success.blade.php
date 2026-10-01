@extends('layouts.app')

@section('title', '접수 완료 - GrapeSEED 웨비나')

@section('content')
@include('partials.application-progress', ['currentStep' => 3])

<div class="row justify-content-center">
    <div class="col-12 col-lg-10 text-center">
        @if($submission)
            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;" aria-hidden="true"></i>
            <h2 class="mt-3 mb-3">웨비나 신청이 완료되었습니다</h2>
            <p class="text-muted">접수번호를 보관해주세요. 문의하실 때 사용할 수 있습니다.</p>
            <div class="card my-4">
                <div class="card-body py-4">
                    <h3 class="h6 text-muted">접수번호</h3>
                    <p class="h3 mb-3" style="color: var(--seminar-accent)">{{ $submission->receipt_number }}</p>
                    <p class="small text-muted mb-0">접수 일시: {{ $submission->created_at->format('Y년 m월 d일 H:i') }}</p>
                </div>
            </div>
            <p class="text-muted small">입력하신 전화번호로 접수번호를 안내합니다.<br>문자 수신이 지연되더라도 신청은 접수되었습니다.</p>
            <p class="mt-3 mb-0">웨비나 링크는 행사 시작 30분 전에 위에 입력하신 전화번호로 전송됩니다.</p>
            <p class="text-muted mt-4">참여해주셔서 감사합니다.</p>
        @else
            <h2 class="mb-3">접수 정보를 확인할 수 없습니다</h2>
            <p class="text-muted">이미 신청하셨다면 수신한 문자의 접수번호를 확인해주세요.</p>
            <a href="{{ url('/') }}" class="btn btn-primary mt-3">처음 화면으로</a>
        @endif
    </div>
</div>
@endsection

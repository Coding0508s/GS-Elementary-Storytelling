@extends('admin.layout')

@section('title', '접수 내역')

@section('content')
<style>
    .application-desk { display: grid; gap: 16px; }
    .application-desk h1 { font-size: 1.65rem; letter-spacing: -0.03em; margin: 0; }
    .desk-head, .dispatch-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
    .desk-kicker { margin: 6px 0 0; color: #5c6570; font-size: 0.92rem; }
    .desk-count { font-variant-numeric: tabular-nums; font-weight: 700; color: #541b86; }
    .dispatch { padding: 22px; border: 1px solid #e2e5e9; border-radius: 12px; background: #fff; }
    .dispatch h2 { margin: 0; font-size: 1.05rem; }
    .dispatch p { margin: 0; color: #69717d; font-size: 0.82rem; line-height: 1.5; }
    .dispatch [hidden] { display: none !important; }
    .dispatch-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; }
    .dispatch-recipients { display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; }
    .dispatch-recipients > * { white-space: nowrap; }
    .dispatch-select-all { background: #541b86; border-color: #541b86; color: #fff; }
    .dispatch-select-all:hover, .dispatch-select-all:focus-visible { background: #42156c; border-color: #42156c; color: #fff; }
    .dispatch-clear { background: #eaf0f7; border-color: #d7e1ed; color: #34516f; }
    .dispatch-clear:hover, .dispatch-clear:focus-visible { background: #dce7f3; border-color: #bfcee0; color: #263f59; }
    .selected-pill { display: inline-flex; gap: 4px; font-size: 0.85rem; font-weight: 700; color: #541b86; }
    .dispatch-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px 18px; max-width: 800px; }
    .dispatch-field { display: grid; gap: 6px; margin: 0; font-size: 0.82rem; font-weight: 600; }
    .dispatch-optional, .dispatch-footer { grid-column: 1 / -1; }
    .dispatch-footer { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; padding-top: 2px; }
    .dispatch-note { max-width: 540px; }
    .dispatch .js-schedule-time { max-width: 360px; }
    .dispatch .js-schedule-time span { font-size: 0.77rem; font-weight: 400; }
    .dispatch-history [hidden] { display: none !important; }
    .dispatch-record { padding: 16px 0; border-top: 1px solid #e9ecf0; }
    .dispatch-record:first-of-type { margin-top: 14px; }
    .dispatch-record:last-child { padding-bottom: 0; }
    .dispatch-record-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
    .dispatch-record h3 { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 5px; font-size: 0.95rem; font-weight: 700; }
    .dispatch-scope { color: #78818c; font-size: 0.76rem; font-weight: 400; }
    .dispatch-record-meta { margin: 0; color: #606a76; font-size: 0.8rem; line-height: 1.7; }
    .dispatch-meta-separator { margin: 0 5px; color: #b2bac4; }
    .dispatch-timezone { color: #8a929d; font-size: 0.72rem; }
    .dispatch-status { flex-shrink: 0; padding: 4px 9px; border-radius: 6px; background: #f0f2f5; color: #596371; font-size: 0.75rem; font-weight: 600; }
    [data-status="queued"] .dispatch-status, [data-status="processing"] .dispatch-status, [data-status="cancelling"] .dispatch-status { background: #fff4dc; color: #79500a; }
    [data-status="scheduled"] .dispatch-status, [data-status="completed"] .dispatch-status { background: #eaf5ef; color: #276346; }
    [data-status="needs_review"] .dispatch-status, [data-status="partial_scheduled"] .dispatch-status { background: #fff0e7; color: #8e471c; }
    .dispatch-counts { display: flex; flex-wrap: wrap; gap: 5px 12px; margin-top: 8px; }
    .dispatch-count { color: #6b7580; font-size: 0.78rem; }
    [data-count-item="failed"], [data-count-item="unknown"], [data-count-item="cancel_unknown"] { color: #b33838; }
    .dispatch-warning { margin: 8px 0 0; color: #a34125; font-size: 0.8rem; line-height: 1.5; }
    .dispatch-reservation-note { margin: 8px 0 0; color: #7b6645; font-size: 0.76rem; }
    .dispatch-details { margin-top: 8px; font-size: 0.76rem; color: #78818c; }
    .dispatch-details summary { width: fit-content; cursor: pointer; padding: 3px 0; }
    .dispatch-details summary:hover { color: #541b86; }
    .dispatch-details-body { padding: 10px 12px; margin-top: 6px; background: #f8f9fb; border-radius: 6px; line-height: 1.6; }
    .dispatch-details-body p { margin: 0 0 5px; }
    .dispatch-details-body p:last-child { margin-bottom: 0; }
    .dispatch-preview { margin-top: 12px; }
    .dispatch-confirm-form, .dispatch-cancel-form { display: inline-block; vertical-align: bottom; margin-right: 6px; margin-bottom: 4px; }
    .dispatch-schedule-controls { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .dispatch-crud-actions, .dispatch-editor-buttons { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .dispatch-crud-actions form { margin: 0; }
    .dispatch-editor { max-width: 600px; padding: 14px; background: #f8f9fb; border: 1px solid #e5e9ee; border-radius: 8px; margin-top: 10px; }
    .application-filters { padding: 18px 20px; border: 1px solid #e2e5e9; border-radius: 12px; background: #fff; }
    .application-filter-form { display: grid; grid-template-columns: 150px 210px minmax(180px, 1fr) auto; gap: 12px; align-items: end; }
    .application-filter-field { display: grid; gap: 7px; min-width: 0; margin: 0; }
    .application-filter-label { color: #5c6570; font-size: 0.78rem; font-weight: 600; }
    .application-filter-field .form-select,
    .application-filter-field .form-control { height: 42px; border-color: #dce1e7; border-radius: 8px; font-size: 0.875rem; }
    .application-filter-field .form-select { padding-right: 34px; }
    .application-search-input { position: relative; min-width: 0; }
    .application-search-input > i { position: absolute; top: 50%; left: 13px; transform: translateY(-50%); color: #7b8490; pointer-events: none; }
    .application-search-input .form-control { padding-left: 38px; width: 100%; }
    .application-search-input .form-control::placeholder { color: #89919d; }
    .application-filter-actions { display: flex; align-items: center; gap: 8px; }
    .application-filter-actions .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 42px; padding: 9px 18px; border-radius: 8px; white-space: nowrap; font-size: 0.875rem; }
    .application-filter-reset { color: #606a76; border: 1px solid #dce1e7; background: #fff; }
    .application-filter-reset:hover { color: #343b45; background: #f4f6f8; border-color: #bbc4ce; }
    .application-filters :is(input, select, .btn):focus-visible { outline: 3px solid #b9cdf9; outline-offset: 2px; }
    @media (max-width: 1200px) {
        .application-filter-form { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto; }
        .application-filter-search { grid-column: 1 / 3; }
        .application-filter-actions { grid-column: 3; grid-row: 2; }
    }
    @media (max-width: 540px) {
        .application-filters { padding: 16px; }
        .application-filter-form { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .application-filter-search { grid-column: 1 / -1; }
        .application-filter-actions { grid-column: 1 / -1; grid-row: auto; }
        .application-filter-actions .btn-primary { flex: 1; }
    }
    .application-scroll { max-height: 65vh; overflow: auto; }
    .application-ledger thead { position: sticky; top: 0; z-index: 2; }
    .application-ledger { min-width: 1180px; }
    .application-ledger th,
    .application-ledger td { text-align: left; white-space: nowrap; word-break: keep-all; }
    .application-ledger .is-check,
    .application-ledger .is-action { text-align: center; width: 44px; }
    .application-ledger .is-question {
        white-space: normal;
        min-width: 160px;
        max-width: 220px;
        color: #3d4450;
        font-size: 0.82rem;
        line-height: 1.4;
    }
    .question-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .cell-sub { display: block; margin-top: 2px; color: #7b8490; font-size: 0.78rem; font-weight: 500; }
    .application-ledger .is-send { white-space: normal; min-width: 168px; }
    .send-line { display: flex; align-items: flex-start; gap: 8px; margin: 4px 0; }
    .send-line .send-label { flex: 0 0 4.4rem; color: #7b8490; font-size: 0.72rem; font-weight: 700; line-height: 1.6; }
    .receipt-code { font-variant-numeric: tabular-nums; letter-spacing: -0.02em; }
    @media (max-width: 900px) {
        .desk-head { display: block; }
        .desk-head .btn { margin-top: 12px; }
    }
    @media (max-width: 540px) {
        .dispatch { padding: 16px; }
        .dispatch-form { grid-template-columns: 1fr; }
        .dispatch-footer { align-items: stretch; flex-direction: column; }
        .dispatch-footer .btn { width: 100%; }
    }
</style>
<div class="application-desk">
<div class="desk-head">
    <div>
        <h1>접수 내역</h1>
        <p class="desk-kicker">{{ $resultStatus !== '' || $searchQuery !== '' ? '조회된 참가자' : '접수된 참가자' }} <span class="desk-count">{{ number_format($applicationCount) }}명</span>. 신청 완료 알림은 접수되는 순간 자동으로 갑니다.</p>
    </div>
    <a href="{{ route('admin.applications.excel', request()->only('search')) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-excel"></i> 엑셀 다운로드
    </a>
</div>

<section class="dispatch" aria-labelledby="dispatch-heading">
    <div class="dispatch-top">
        <h2 id="dispatch-heading">알림 보내기</h2>
        <div class="dispatch-recipients">
            <span class="selected-pill" aria-live="polite">선택 <span data-selected-count>0명</span></span>
            <button type="button" class="btn btn-sm dispatch-select-all" id="select-every-recipient"
                    data-failed="{{ $resultStatus === 'failed' ? 'true' : 'false' }}" data-total="{{ $resultStatus === 'failed' ? $applicationCount : $allRecipientCount }}" aria-label="{{ $resultStatus === 'failed' ? '검색된 실패건 전체 선택' : '등록자 전체 '.number_format($allRecipientCount).'명 선택' }}" @disabled(!$dispatchReady || ($resultStatus === 'failed' ? $applicationCount : $allRecipientCount) === 0)>{{ $resultStatus === 'failed' ? '검색된 실패건 전체 선택' : '전체 '.number_format($allRecipientCount).'명 선택' }}</button>
            <button type="button" class="btn btn-sm dispatch-clear" id="clear-recipients">선택 해제</button>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form dispatch-form" data-ready="{{ $dispatchReady && $allRecipientCount > 0 ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="search" value="{{ $searchQuery }}">
        @if($resultStatus === 'failed')
            <input type="hidden" name="retry_failed" value="1">
            <input type="hidden" name="notice_kind" value="{{ $noticeKind }}">
            <input type="hidden" name="result_status" value="failed">
        @endif
        <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <label class="dispatch-field">안내 종류
            <select name="kind" class="form-select js-notice-kind" required>
                <option value="morning" @selected($noticeKind === 'morning') @disabled($resultStatus === 'failed' && $noticeKind !== 'morning')>오전 안내</option>
                <option value="entry" @selected($noticeKind === 'entry') @disabled($resultStatus === 'failed' && $noticeKind !== 'entry')>입장 안내</option>
            </select>
        </label>
        @include('admin.partials.alimtalk-schedule-options')
        <label class="dispatch-field dispatch-optional js-entry-link" hidden>유튜브 입장 링크
            <input type="url" name="zoom_url" class="form-control" value="{{ $zoomUrl }}"
                   placeholder="https://www.youtube.com/watch?v=..." disabled>
        </label>
        <div class="dispatch-footer">
            <p id="selection-scope-note" class="dispatch-note">목록에서 받는 사람을 선택해주세요. 같은 번호에는 한 번만 보냅니다.</p>
            <button type="submit" class="btn btn-primary px-4" disabled>{{ $resultStatus === 'failed' ? '재발송 대상 확인' : '대상 확인' }}</button>
        </div>
    </form>
    @unless($dispatchReady)<p class="text-danger mt-2">기능 업데이트가 필요합니다. 관리자에게 문의해주세요.</p>@endunless
</section>

@include('admin.partials.alimtalk-dispatches')

<section class="application-filters" aria-label="접수 내역 검색">
    <form method="GET" action="{{ route('admin.applications') }}" class="application-filter-form" role="search">
        <label class="application-filter-field">
            <span class="application-filter-label">안내 종류</span>
            <select name="notice_kind" class="form-select">
                @if($resultStatus !== 'failed')<option value="">전체 안내</option>@endif
                <option value="morning" @selected($noticeKind === 'morning')>오전 안내</option>
                <option value="entry" @selected($noticeKind === 'entry')>입장 링크</option>
            </select>
        </label>
        <label class="application-filter-field">
            <span class="application-filter-label">발송 상태</span>
            <select name="result_status" class="form-select">
                <option value="">전체 상태</option>
                <option value="failed" @selected($resultStatus === 'failed')>발송 실패 · 전달 실패</option>
                <option value="unknown" @selected($resultStatus === 'unknown')>확인 필요</option>
            </select>
        </label>
        <label class="application-filter-field application-filter-search">
            <span class="application-filter-label">검색어</span>
            <span class="application-search-input">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" class="form-control"
                       placeholder="학생명, 기관명, 학부모, 전화번호, 접수번호"
                       value="{{ $searchQuery }}">
            </span>
        </label>
        <div class="application-filter-actions">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i>검색</button>
            @if($searchQuery !== '' || $resultStatus !== '' || $noticeKind !== '')
                <a href="{{ route('admin.applications') }}" class="btn application-filter-reset" aria-label="검색어와 필터 초기화">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>초기화
                </a>
            @endif
        </div>
    </form>
</section>

@if($resultStatus === 'failed')
<p class="small text-muted mb-0">실패한 안내만 재발송합니다. 조회할 안내 종류는 검색 조건에서 변경할 수 있습니다. 안내 종류를 선택하고 대상을 확인해주세요. 상태가 바뀐 대상은 제외됩니다.</p>
@endif
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3 pb-2"
             data-results-sync-url="{{ route('admin.applications.alimtalk.results.sync') }}" data-csrf="{{ csrf_token() }}">
            <button type="button" class="btn btn-sm btn-outline-primary" data-sync-results>최종 결과 조회</button>
            <span class="small text-muted" data-sync-note role="status">30초마다 자동 조회 · 예약은 예약 시각 이후 확인</span>
        </div>
        <p class="small text-muted px-3 mb-2" data-results-error role="status" hidden>발송 결과를 갱신하지 못했습니다. 로그인 상태를 확인하거나 새로고침해주세요.</p>
        <div class="table-responsive application-scroll" id="application-scroll" tabindex="0" aria-label="접수 내역 목록">
            <table class="table table-hover mb-0 align-middle application-ledger" data-results-url="{{ route('admin.applications.alimtalk.results') }}">
                <thead class="table-light">
                    <tr>
                        <th class="is-check">
                            <input type="checkbox" id="select-all-recipients" aria-label="불러온 항목 전체 선택" @disabled($applications->isEmpty())>
                        </th>
                        <th>접수번호</th>
                        <th>접수일시</th>
                        <th>학생 이름</th>
                        <th>학년 / 연령</th>
                        <th>기관 / 지역</th>
                        <th>학부모</th>
                        <th>Day 1 · 김상균 교수님</th>
                        <th>Day 2 · 윤윤구 강사님</th>
                        <th>마케팅 수신</th>
                        <th>발송 결과</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @include('admin.partials.application-rows')
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex align-items-center justify-content-between gap-2">
        <span id="application-load-status" role="status" aria-live="polite">전체 {{ number_format($applicationCount) }}건 중 {{ $applications->count() }}건 표시@if(!$applications->hasMorePages()) · 모든 접수 내역을 불러왔습니다@endif</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="load-applications"
                data-next="{{ $applications->nextPageUrl() }}" @if(!$applications->hasMorePages()) hidden @endif>더 불러오기</button>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-alimtalk-dispatch.js') }}" defer></script>
<script src="{{ asset('js/admin-application-scroll.js') }}" defer></script>
@endpush

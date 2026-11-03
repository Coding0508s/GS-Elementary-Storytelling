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
        <p class="desk-kicker">접수된 참가자 <span class="desk-count">{{ number_format($applications->total()) }}명</span>. 신청 완료 알림은 접수되는 순간 자동으로 갑니다.</p>
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
                    data-total="{{ $allRecipientCount }}" aria-label="등록자 전체 {{ number_format($allRecipientCount) }}명 선택" @disabled(!$dispatchReady || $allRecipientCount === 0)>전체 {{ number_format($allRecipientCount) }}명 선택</button>
            <button type="button" class="btn btn-sm dispatch-clear" id="clear-recipients">선택 해제</button>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form dispatch-form" data-ready="{{ $dispatchReady && $allRecipientCount > 0 ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="search" value="{{ $searchQuery }}">
        <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
        <label class="dispatch-field">안내 종류
            <select name="kind" class="form-select js-notice-kind">
                <option value="morning">오전 안내</option>
                <option value="entry">입장 안내</option>
            </select>
        </label>
        @include('admin.partials.alimtalk-schedule-options')
        <label class="dispatch-field dispatch-optional js-entry-link" hidden>유튜브 입장 링크
            <input type="url" name="zoom_url" class="form-control" value="{{ $zoomUrl }}"
                   placeholder="https://www.youtube.com/watch?v=..." disabled>
        </label>
        <div class="dispatch-footer">
            <p id="selection-scope-note" class="dispatch-note">목록에서 받는 사람을 선택해주세요. 같은 번호에는 한 번만 보냅니다.</p>
            <button type="submit" class="btn btn-primary px-4" disabled>대상 확인</button>
        </div>
    </form>
    @unless($dispatchReady)<p class="text-danger mt-2">기능 업데이트가 필요합니다. 관리자에게 문의해주세요.</p>@endunless
</section>

@include('admin.partials.alimtalk-dispatches')

<div class="card admin-card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.applications') }}" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="학생명, 기관명, 학부모, 전화번호, 접수번호"
                       value="{{ $searchQuery }}">
                @if($searchQuery !== '')
                    <a href="{{ route('admin.applications') }}" class="btn btn-outline-secondary" title="검색 초기화">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
                <button type="submit" class="btn btn-primary">검색</button>
            </div>
        </form>
    </div>
</div>

<div class="card admin-card">
    <div class="card-body p-0">
        <div class="d-flex flex-wrap align-items-center gap-2 px-3 pt-3 pb-2"
             data-results-sync-url="{{ route('admin.applications.alimtalk.results.sync') }}" data-csrf="{{ csrf_token() }}">
            <button type="button" class="btn btn-sm btn-outline-primary" data-sync-results>최종 결과 조회</button>
            <span class="small text-muted" data-sync-note role="status">30초마다 자동 조회 · 예약은 예약 시각 이후 확인</span>
        </div>
        <p class="small text-muted px-3 mb-2" data-results-error role="status" hidden>발송 결과를 갱신하지 못했습니다. 로그인 상태를 확인하거나 새로고침해주세요.</p>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle application-ledger" data-results-url="{{ route('admin.applications.alimtalk.results') }}">
                <thead class="table-light">
                    <tr>
                        <th class="is-check">
                            <input type="checkbox" id="select-all-recipients" aria-label="이 페이지 전체 선택" @disabled($applications->isEmpty())>
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
                    @forelse($applications as $application)
                        <tr>
                            <td class="is-check">
                                <input type="checkbox"
                                       class="recipient-checkbox"
                                       value="{{ $application->id }}"
                                       aria-label="{{ $application->receipt_number }} {{ $application->student_name_korean }} 선택">
                            </td>
                            <td><strong class="receipt-code">{{ $application->receipt_number }}</strong></td>
                            <td><small>{{ $application->created_at?->format('Y-m-d H:i') }}</small></td>
                            <td>{{ $application->student_name_korean }}</td>
                            <td>{{ $application->grade }}</td>
                            <td>
                                {{ $application->institution_name }}
                                <span class="cell-sub">{{ $application->region }}</span>
                            </td>
                            <td>
                                {{ $application->parent_name }}
                                <span class="cell-sub">{{ $application->parent_phone }}</span>
                            </td>
                            <td class="is-question">
                                @php $day1Question = $application->instructorQuestion('day1'); @endphp
                                @if($day1Question !== '')
                                    <span class="question-clamp" title="{{ $day1Question }}">{{ $day1Question }}</span>
                                @else
                                    <span class="text-muted">없음</span>
                                @endif
                            </td>
                            <td class="is-question">
                                @php $day2Question = $application->instructorQuestion('day2'); @endphp
                                @if($day2Question !== '')
                                    <span class="question-clamp" title="{{ $day2Question }}">{{ $day2Question }}</span>
                                @else
                                    <span class="text-muted">없음</span>
                                @endif
                            </td>
                            <td>{{ $application->marketing_consent ? '동의' : '미동의' }}</td>
                            <td class="is-send">
                                @foreach(['morning' => '오전 안내', 'entry' => '입장 링크'] as $sendKind => $sendLabel)
                                    @php $sendResult = $applicationResults[$application->id][$sendKind]; @endphp
                                    <div class="send-line" data-result-id="{{ $application->id }}" data-result-kind="{{ $sendKind }}">
                                        <span class="send-label">{{ $sendLabel }}</span>
                                        <div>
                                            <span class="{{ $sendResult['class'] }}" title="{{ $sendResult['title'] }}" data-result-badge>{{ $sendResult['label'] }}</span>
                                            <div class="small text-muted" data-result-reason @if(!$sendResult['reason']) hidden @endif>{{ $sendResult['reason'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                            <td class="is-action">
                                <form method="POST"
                                      action="{{ route('admin.applications.destroy', $application) }}"
                                      onsubmit="return confirm(@json($application->receipt_number.' '.$application->student_name_korean.' 접수를 휴지통으로 옮길까요? 휴지통에서 다시 복원할 수 있습니다.'))">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-5">
                                @if($searchQuery !== '')
                                    검색 결과가 없습니다.
                                @else
                                    아직 접수된 참가자가 없습니다.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($applications->hasPages())
        <div class="card-footer">
            {{ $applications->links('custom.pagination') }}
        </div>
    @endif
</div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin-alimtalk-dispatch.js') }}" defer></script>
@endpush

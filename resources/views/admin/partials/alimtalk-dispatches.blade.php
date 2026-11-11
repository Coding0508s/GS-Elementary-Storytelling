@if($dispatchReady)
<section class="card admin-card dispatch-history" aria-labelledby="dispatch-progress-heading"
         id="alimtalk-progress" data-url="{{ route('admin.applications.alimtalk.progress') }}">
    <div class="card-body">
        <div class="dispatch-history-heading">
            <h2 id="dispatch-progress-heading">발송 내역</h2>
            <p>진행 중 <strong data-history-summary="active">{{ collect($dispatches)->where('history_group', 'active')->count() }}</strong>건 · 확인 필요 <strong data-history-summary="review">{{ collect($dispatches)->where('history_group', 'review')->count() }}</strong>건</p>
            <span class="dispatch-auto-note"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> 5초마다 상태 갱신</span>
        </div>
        <p data-poll-error class="dispatch-warning" role="status" hidden>진행 상황을 가져오지 못했습니다. 잠시 후 다시 확인합니다. 로그인 상태를 확인하거나 새로고침해주세요.</p>
        <div class="dispatch-history-tabs" role="tablist" aria-label="발송 상태별 내역">
            @foreach(['active' => '예약·진행 중', 'review' => '확인 필요', 'done' => '완료·취소'] as $group => $label)
                <button type="button" id="dispatch-tab-{{ $group }}" role="tab" aria-selected="{{ $group === 'active' ? 'true' : 'false' }}" aria-controls="dispatch-history-panel" tabindex="{{ $group === 'active' ? '0' : '-1' }}" data-history-tab="{{ $group }}">
                    {{ $label }} <span data-history-count="{{ $group }}">{{ collect($dispatches)->where('history_group', $group)->count() }}</span>
                </button>
            @endforeach
        </div>
        <div id="dispatch-history-panel" role="tabpanel" aria-labelledby="dispatch-tab-active" tabindex="0">
            <div class="dispatch-history-scroll" tabindex="0" aria-label="발송 작업 목록">
                <table class="table mb-0 dispatch-history-table">
                    <thead><tr><th scope="col">안내 종류</th><th scope="col">발송 대상</th><th scope="col">예약 시각</th><th scope="col">상태</th><th scope="col">상세</th></tr></thead>
                    <tbody>
                        @foreach($dispatches as $dispatch)
                            @php
                                $expired = $dispatch['status'] === 'preview' && now()->gte($dispatch['expires_at']);

                            @endphp
                            <tr data-dispatch-row="{{ $dispatch['id'] }}" data-history-group="{{ $dispatch['history_group'] }}" data-status="{{ $expired ? 'expired' : $dispatch['status'] }}" @if($dispatch['history_group'] !== 'active') hidden @endif>
                                <td><strong>{{ $dispatch['kind'] === 'morning' ? '오전 안내' : '입장 안내' }}</strong></td>
                                <td>{{ number_format($dispatch['total']) }}개 번호</td>
                                <td>{{ $dispatch['scheduled_at'] ? \Illuminate\Support\Carbon::parse($dispatch['scheduled_at'])->format('m.d H:i') : '즉시 발송' }}</td>
                                <td><span class="dispatch-status" data-history-status>{{ $dispatch['status_label'] }}</span></td>
                                <td><button type="button" class="dispatch-view" data-dispatch-open="{{ $dispatch['id'] }}" aria-haspopup="dialog" aria-controls="dispatch-dialog-{{ $dispatch['id'] }}" aria-label="{{ $dispatch['kind'] === 'morning' ? '오전 안내' : '입장 안내' }} {{ $dispatch['scheduled_at'] ?: '즉시 발송' }} 상세 보기">보기 <i class="bi bi-chevron-right" aria-hidden="true"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="dispatch-history-empty" data-history-empty @if(collect($dispatches)->where('history_group', 'active')->isNotEmpty()) hidden @endif>예약·진행 중인 발송 작업이 없습니다.</p>
            </div>
        </div>
        <p class="dispatch-history-footnote">현재 탭 <strong data-history-visible>{{ collect($dispatches)->where('history_group', 'active')->count() }}</strong>건 · 최근 작업순 · 종료된 작업은 최근 8건까지 표시 · 예약 시각은 한국 시간입니다.</p>
        @foreach($dispatches as $dispatch)
            @php
                $expired = $dispatch['status'] === 'preview' && now()->gte($dispatch['expires_at']);
                $ours = (int) $dispatch['admin_id'] === (int) auth('admin')->user()->id;
                $reservationPending = $dispatch['scheduled_at'] && in_array($dispatch['status'], ['preview', 'queued', 'processing'], true);
            @endphp
            <dialog class="dispatch-dialog" id="dispatch-dialog-{{ $dispatch['id'] }}" aria-labelledby="dispatch-dialog-title-{{ $dispatch['id'] }}" aria-describedby="dispatch-title-{{ $dispatch['id'] }}" data-auto-open="{{ $ours && $dispatch['status'] === 'preview' && !$expired ? 'true' : 'false' }}">
                <article class="dispatch-record" data-dispatch-id="{{ $dispatch['id'] }}" data-status="{{ $dispatch['status'] }}" data-history-group="{{ $dispatch['history_group'] }}" data-awaiting-results="{{ $dispatch['awaiting_results'] ? 'true' : 'false' }}">
                    @include('admin.partials.alimtalk-dispatch-detail')
                </article>
            </dialog>
        @endforeach
    </div>
</section>
@endif

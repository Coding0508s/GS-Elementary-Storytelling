@if($dispatchReady)
<section class="card admin-card dispatch-history" aria-labelledby="dispatch-progress-heading"
         id="alimtalk-progress" data-url="{{ route('admin.applications.alimtalk.progress') }}">
    <div class="card-body">
        <h2 id="dispatch-progress-heading" class="h5 mb-1">발송 내역</h2>
        <p class="small text-muted mb-0">최종 결과를 자동 조회합니다. 전달 완료는 읽음 확인과 다릅니다.</p>
        <p data-poll-error class="dispatch-warning" role="status" hidden>진행 상황을 가져오지 못했습니다. 잠시 후 다시 확인합니다. 로그인 상태를 확인하거나 새로고침해주세요.</p>
        @forelse($dispatches as $dispatch)
            @php
                $labels = ['preview' => '발송 전 확인', 'queued' => '발송 대기', 'processing' => '처리 중', 'completed' => '처리 완료', 'needs_review' => '확인 필요', 'closed' => '종료 확인', 'cancelled' => '취소', 'expired' => '확인 만료', 'scheduled' => '예약 접수 완료', 'partial_scheduled' => '일부 예약 접수', 'cancelling' => '예약 취소 확인 중'];
                if ($dispatch['scheduled_at']) { $labels['queued'] = '예약 등록 대기'; $labels['processing'] = '예약 등록 중'; }
                $expired = $dispatch['status'] === 'preview' && now()->gte($dispatch['expires_at']);
                $ours = (int) $dispatch['admin_id'] === (int) auth('admin')->user()->id;
                $reservationPending = $dispatch['scheduled_at'] && in_array($dispatch['status'], ['preview', 'queued', 'processing'], true);
            @endphp
            <article class="dispatch-record" data-dispatch-id="{{ $dispatch['id'] }}" data-status="{{ $dispatch['status'] }}" data-awaiting-results="{{ $dispatch['awaiting_results'] ? 'true' : 'false' }}">
                <div class="dispatch-record-head">
                    <div>
                        <h3>{{ $dispatch['kind'] === 'morning' ? '오전 안내' : '입장 안내' }} <span class="dispatch-scope">{{ $dispatch['scope'] === 'all' ? '등록자 전체' : '개별 선택' }}</span></h3>
                        <p class="dispatch-record-meta">발송 대상 <strong>{{ number_format($dispatch['total']) }}개 번호</strong>
                            @if($dispatch['scheduled_at'])
                                <span class="dispatch-meta-separator">·</span> 예약 {{ \Illuminate\Support\Carbon::parse($dispatch['scheduled_at'])->format('Y.m.d H:i') }} <span class="dispatch-timezone">한국 시간</span>
                            @endif
                        </p>
                    </div>
                    <span class="dispatch-status" data-dispatch-status>{{ $labels[$dispatch['status']] }}</span>
                </div>
                <div class="dispatch-counts" aria-live="polite">
                    @foreach(['pending' => '대기', 'processing' => '처리 중', 'accepted' => '요청 접수', 'delivering' => '발송 중', 'delivered' => '전달 완료', 'delivery_failed' => '전달 실패', 'failed' => '요청 실패', 'unknown' => '확인 필요', 'skipped' => '제외', 'scheduled' => '예약 접수', 'cancel_pending' => '취소 중', 'cancel_unknown' => '취소 확인 필요', 'cancelled' => '취소 완료'] as $state => $label)
                        <span class="dispatch-count" data-count-item="{{ $state }}" @if(($dispatch['counts'][$state] ?? 0) === 0 || ($state === 'pending' && in_array($dispatch['status'], ['cancelled', 'expired'], true))) hidden @endif>{{ $label }} <strong data-count="{{ $state }}">{{ $dispatch['counts'][$state] ?? 0 }}</strong>건</span>
                    @endforeach
                </div>
                <p class="dispatch-reservation-note" data-reservation-note @if(!$reservationPending) hidden @endif>예약 접수가 끝날 때까지 발송 처리 프로그램을 켜두세요.</p>
                <p class="dispatch-warning" data-stalled @if(!$dispatch['stalled']) hidden @endif>처리가 지연되고 있습니다. 다시 발송하지 말고 작업 상태를 확인해주세요.</p>
                <p class="dispatch-warning" data-review @if($dispatch['status'] !== 'needs_review') hidden @endif>접수 또는 취소 여부 확인이 필요합니다. 자동 재발송하지 않습니다. 제공사 기록을 확인해주세요.</p>
                <p class="dispatch-reservation-note" data-change-pending @if(!$dispatch['change_pending']) hidden @endif>{{ $dispatch['change_message'] }}</p>
                <p class="dispatch-warning" data-change-error @if(!$dispatch['change_error']) hidden @endif>{{ $dispatch['change_error'] }}</p>

                @if($dispatch['status'] === 'preview')
                    <div class="dispatch-preview" data-preview-actions>
                        @if($dispatch['entry_link'])
                            <p class="small text-break mb-2">입장 링크: <a href="https://{{ $dispatch['entry_link'] }}" target="_blank" rel="noopener noreferrer">https://{{ $dispatch['entry_link'] }}</a></p>
                        @endif
                        @if($ours)
                            @if($dispatch['invalid_count'] > 0 || $dispatch['selected_count'] - $dispatch['invalid_count'] - $dispatch['total'] > 0)
                                <p class="small text-muted mb-2">접수 {{ number_format($dispatch['selected_count']) }}건에서 번호 오류 {{ number_format($dispatch['invalid_count']) }}건을 제외하고 같은 번호를 통합했습니다.</p>
                            @endif
                            @if($expired)<p class="dispatch-warning">확인 시간이 만료되었습니다. 대상을 다시 선택해주세요.</p>@endif
                            <form method="POST" action="{{ route('admin.applications.alimtalk.confirm', $dispatch['id']) }}" class="js-dispatch-confirm dispatch-confirm-form">
                                @csrf
                                @if($dispatch['previous_count'] > 0)
                                    <label class="d-block small mb-2"><input type="checkbox" name="confirm_resend" value="1" required> 이전 발송 이력을 확인했으며 같은 안내의 재발송에 동의합니다.</label>
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm" @disabled($expired || $dispatch['total'] === 0)>{{ number_format($dispatch['total']) }}개 번호에 {{ $dispatch['scheduled_at'] ? '예약 확정' : '발송 확정' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.applications.alimtalk.cancel', $dispatch['id']) }}" class="dispatch-cancel-form">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">확인 취소</button>
                            </form>
                        @else
                            <p class="small text-muted">다른 관리자가 대상을 확인 중입니다.</p>
                        @endif
                    </div>
                @endif
                @if($dispatch['scheduled_at'] && $ours)
                    <div data-schedule-actions @if(!$dispatch['can_cancel_schedule']) hidden @endif>
                        <form method="POST" action="{{ route('admin.applications.alimtalk.schedule', $dispatch['id']) }}" class="dispatch-schedule-controls js-schedule-management">
                            @csrf
                            <button name="action" value="refresh" class="btn btn-sm btn-outline-primary">상태 새로고침</button>
                            <button name="action" value="cancel" @disabled($dispatch['status'] === 'cancelling') class="btn btn-sm btn-outline-danger">예약 취소</button>
                        </form>
                    </div>
                @endif
                @if($ours)
                    <div class="dispatch-crud-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-edit-toggle
                                aria-expanded="false" aria-controls="edit-{{ $dispatch['id'] }}" @disabled(!$dispatch['can_edit'])
                                title="{{ $dispatch['can_edit'] ? '발송 방식·예약 시간·입장 링크 수정' : '처리 중이거나 이미 전송된 작업은 수정할 수 없습니다.' }}">수정</button>
                        <form method="POST" action="{{ route('admin.applications.alimtalk.destroy', $dispatch['id']) }}" class="js-dispatch-delete"
                              data-confirm="{{ ($dispatch['counts']['scheduled'] ?? 0) > 0 ? '외부 예약을 취소한 뒤 이 내역을 삭제할까요?' : '이 작업을 삭제할까요? 대기 중이면 발송을 취소합니다. 이미 보낸 메시지는 취소되지 않습니다.' }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="revision" value="{{ $dispatch['revision'] }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger" data-delete-button @disabled(!$dispatch['can_delete'])>삭제</button>
                        </form>
                    </div>
                    <div id="edit-{{ $dispatch['id'] }}" class="dispatch-editor" data-editor hidden>
                        <form method="POST" action="{{ route('admin.applications.alimtalk.update', $dispatch['id']) }}" class="js-dispatch-edit">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="revision" value="{{ $dispatch['revision'] }}">
                            <p class="small text-muted mb-2">{{ $dispatch['kind'] === 'morning' ? '오전 안내' : '입장 안내' }} 수정 · 저장 후 대상을 다시 확인하고 확정해주세요.</p>
                            @if($dispatch['scope'] === 'all')<p class="small text-muted mb-2">전체 선택 대상은 저장 시점의 등록자로 다시 계산됩니다.</p>@endif
                            <label class="dispatch-field mb-2">발송 방식
                                <select name="delivery_mode" class="form-select form-select-sm">
                                    <option value="scheduled" @selected($dispatch['scheduled_at'])>예약 발송</option>
                                    <option value="immediate" @selected(!$dispatch['scheduled_at'])>즉시 발송</option>
                                </select>
                            </label>
                            <label class="dispatch-field mb-2" data-edit-time @if(!$dispatch['scheduled_at']) hidden @endif>예약 날짜·시간 (한국 시간)
                                <input type="datetime-local" name="scheduled_at" class="form-control form-control-sm"
                                       value="{{ $dispatch['scheduled_at'] ? \Illuminate\Support\Carbon::parse($dispatch['scheduled_at'])->format('Y-m-d\TH:i') : '' }}" @disabled(!$dispatch['scheduled_at'])>
                                <small class="text-muted">현재보다 5분 이후로 지정해주세요.</small>
                            </label>
                            @if($dispatch['kind'] === 'entry')
                                <label class="dispatch-field mb-2">유튜브 입장 링크
                                    <input type="url" name="zoom_url" class="form-control form-control-sm" value="{{ $dispatch['entry_link'] ? 'https://'.$dispatch['entry_link'] : '' }}" required>
                                </label>
                            @endif
                            <div class="dispatch-editor-buttons">
                                <button type="submit" class="btn btn-sm btn-primary">수정 저장</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-edit-close>닫기</button>
                            </div>
                        </form>
                    </div>
                @endif
                <details class="dispatch-details">
                    <summary>상세 보기</summary>
                    <div class="dispatch-details-body">
                        <p>선택한 접수 {{ number_format($dispatch['selected_count']) }}건 · 번호 오류 {{ number_format($dispatch['invalid_count']) }}건 · 같은 번호로 통합 {{ number_format($dispatch['selected_count'] - $dispatch['invalid_count'] - $dispatch['total']) }}건 · 이전 요청 이력 {{ number_format($dispatch['previous_count']) }}건</p>
                        @if($dispatch['scheduled_at'])
                            <p>예약 접수 완료 후에는 컴퓨터를 꺼도 발송됩니다. 이후 접수 삭제·번호 변경은 외부 예약에 자동 반영되지 않습니다. 시간·링크 변경은 취소 완료 후 새로 예약해주세요.</p>
                        @endif
                        @if($dispatch['entry_link'])
                            <p class="text-break">입장 링크: <a href="https://{{ $dispatch['entry_link'] }}" target="_blank" rel="noopener noreferrer">https://{{ $dispatch['entry_link'] }}</a></p>
                        @endif
                        <div class="text-danger" data-dispatch-errors>@foreach($dispatch['errors'] as $error)<p>{{ $error }}</p>@endforeach</div>
                        @if($dispatch['status'] === 'preview')<p>확인 유효 시간: {{ $dispatch['expires_at'] }}</p>@endif
                        <p>마지막 갱신 <span data-updated-at>{{ $dispatch['updated_at'] }}</span></p>
                        <p class="text-break">작업 {{ $dispatch['id'] }}</p>
                    </div>
                </details>
            </article>
        @empty
            <p class="text-muted small mb-0 mt-3">최근 발송 작업이 없습니다.</p>
        @endforelse
    </div>
</section>
@endif

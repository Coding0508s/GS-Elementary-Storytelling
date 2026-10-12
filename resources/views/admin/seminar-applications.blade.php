@extends('admin.layout')

@section('title', '접수 내역')

@section('content')
<style>
    .application-desk { display: grid; gap: 16px; }
    .application-desk h1 { font-size: 1.65rem; letter-spacing: -0.03em; margin: 0; }
    .desk-head, .dispatch-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
    .desk-kicker { margin: 6px 0 0; color: #5c6570; font-size: 0.92rem; }
    .desk-count { font-variant-numeric: tabular-nums; font-weight: 700; color: #541b86; }
    .dispatch {
        display: grid;
        grid-template-columns: minmax(220px, 280px) 1fr;
        gap: 20px;
        padding: 20px 22px;
        border: 1px solid #e6e1ef;
        border-radius: 16px;
        background:
            linear-gradient(180deg, rgba(84, 27, 134, 0.05), transparent 90px),
            #fff;
    }
    .dispatch h2 { margin: 0 0 6px; font-size: 1.05rem; }
    .dispatch p { margin: 0; color: #5c6570; font-size: 0.86rem; line-height: 1.45; }
    .selected-pill {
        display: inline-flex;
        margin-top: 12px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #f3eef8;
        color: #541b86;
        font-size: 0.82rem;
        font-weight: 700;
    }
    .dispatch-actions { display: grid; gap: 10px; align-content: center; }
    .dispatch-entry { display: flex; gap: 8px; }
    .dispatch-entry .form-control { min-width: 0; }
    .dispatch-note { margin: 0; color: #7b8490; font-size: 0.78rem; }
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
        .desk-head, .dispatch { display: block; }
        .dispatch-entry { flex-direction: column; }
        .desk-head .btn { margin-top: 12px; }
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

<div class="dispatch">
    <div>
        <h2>알림 보내기</h2>
        <p>표에서 체크한 사람에게만 당일 안내와 입장 링크를 보냅니다.</p>
        <div class="selected-pill">선택 <span data-selected-count>0명</span></div>
    </div>
    <div class="dispatch-actions">
        <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form" data-action-label="당일 오전 안내">
            @csrf
            <input type="hidden" name="search" value="{{ $searchQuery }}">
            <input type="hidden" name="kind" value="morning">
            <button type="submit" class="btn btn-warning" @disabled($applications->total() === 0)>
                <i class="bi bi-sunrise"></i> 당일 오전 안내 보내기
            </button>
        </form>
        <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form dispatch-entry" data-action-label="입장 안내">
            @csrf
            <input type="hidden" name="search" value="{{ $searchQuery }}">
            <input type="hidden" name="kind" value="entry">
            <input type="url"
                   name="zoom_url"
                   class="form-control"
                   value="{{ $zoomUrl }}"
                   placeholder="https://us02web.zoom.us/j/..."
                   aria-label="줌 입장 링크"
                   required>
            <button type="submit" class="btn btn-primary text-nowrap" @disabled($applications->total() === 0)>
                <i class="bi bi-camera-video"></i> 입장 안내 보내기
            </button>
        </form>
        <p class="dispatch-note">줌 주소 전체를 붙여 넣으면 됩니다. 오전 안내와 입장 링크 결과는 각각 남습니다.</p>
    </div>
</div>

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
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle application-ledger">
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
                                    @php $sendResult = $application->alimtalkResult($sendKind); @endphp
                                    <div class="send-line">
                                        <span class="send-label">{{ $sendLabel }}</span>
                                        <div>
                                            @if(($sendResult['status'] ?? null) === 'sent')
                                                <span class="badge text-bg-success">성공</span>
                                            @elseif(($sendResult['status'] ?? null) === 'failed')
                                                <span class="badge text-bg-danger">실패</span>
                                                @if(! empty($sendResult['reason']))
                                                    <div class="small text-danger">{{ $sendResult['reason'] }}</div>
                                                @endif
                                            @elseif(($sendResult['status'] ?? null) === 'skipped')
                                                <span class="badge text-bg-secondary">번호 오류</span>
                                            @else
                                                <span class="text-muted small">미발송</span>
                                            @endif
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
            {{ $applications->links() }}
        </div>
    @endif
</div>
</div>
@endsection

@push('scripts')
<script>
    const recipientBoxes = () => Array.from(document.querySelectorAll('.recipient-checkbox'));
    const selectedRecipientIds = () => recipientBoxes().filter((box) => box.checked).map((box) => box.value);

    const updateSelectedCount = () => {
        const count = selectedRecipientIds().length;
        document.querySelectorAll('[data-selected-count]').forEach((element) => {
            element.textContent = count + '명';
        });
        const selectAll = document.getElementById('select-all-recipients');
        const boxes = recipientBoxes();
        if (selectAll && boxes.length > 0) {
            selectAll.checked = count === boxes.length;
            selectAll.indeterminate = count > 0 && count < boxes.length;
        }
    };

    document.getElementById('select-all-recipients')?.addEventListener('change', (event) => {
        recipientBoxes().forEach((box) => {
            box.checked = event.target.checked;
        });
        updateSelectedCount();
    });

    recipientBoxes().forEach((box) => box.addEventListener('change', updateSelectedCount));

    document.querySelectorAll('.js-alimtalk-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const ids = selectedRecipientIds();
            if (ids.length === 0) {
                event.preventDefault();
                window.alert('보낼 사람을 선택해주세요.');
                return;
            }

            const label = form.dataset.actionLabel || '알림';
            if (!window.confirm('선택한 ' + ids.length + '명에게 ' + label + '를 보낼까요?')) {
                event.preventDefault();
                return;
            }

            form.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
            ids.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
        });
    });
</script>
@endpush

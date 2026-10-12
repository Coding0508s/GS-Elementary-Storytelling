@extends('admin.layout')

@section('title', '접수 내역')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-person-lines-fill"></i> 접수 내역</h1>
        <p class="text-muted mb-0">접수된 참가자 {{ number_format($applications->total()) }}명</p>
        <p class="mb-0 mt-2">신청이 완료되면 접수번호 알림톡이 바로 갑니다. 당일 안내와 입장 링크는 아래에서 보냅니다.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.applications.excel', request()->only('search')) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> 엑셀 다운로드
        </a>
    </div>
</div>

<div class="card admin-card mb-4">
    <div class="card-body">
        <h2 class="h5 mb-2">알림톡</h2>
        <p class="text-muted small mb-3">신청 완료 알림은 접수되는 순간 자동으로 나갑니다. 아래 버튼은 표에서 체크한 사람에게만 보냅니다. 선택한 인원 <strong data-selected-count>0명</strong></p>
        <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form mb-3" data-action-label="당일 오전 안내">
            @csrf
            <input type="hidden" name="search" value="{{ $searchQuery }}">
            <input type="hidden" name="kind" value="morning">
            <button type="submit" class="btn btn-warning" @disabled($applications->total() === 0)>
                <i class="bi bi-sunrise"></i> 당일 오전 안내 보내기
            </button>
        </form>
        <form method="POST" action="{{ route('admin.applications.alimtalk') }}" class="js-alimtalk-form d-flex flex-column flex-md-row gap-2" data-action-label="입장 안내">
            @csrf
            <input type="hidden" name="search" value="{{ $searchQuery }}">
            <input type="hidden" name="kind" value="entry">
            <input type="url"
                   name="zoom_url"
                   class="form-control"
                   value="{{ $zoomUrl }}"
                   placeholder="https://us02web.zoom.us/j/..."
                   required>
            <button type="submit" class="btn btn-primary text-nowrap" @disabled($applications->total() === 0)>
                <i class="bi bi-camera-video"></i> 입장 안내 보내기
            </button>
        </form>
        <p class="form-text mb-0 mt-2">줌에서 복사한 주소 전체를 넣으면 됩니다. 발송할 때 https:// 는 템플릿 변수 규칙에 맞게 빼고 넣습니다.</p>
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
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 42px;">
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>
                                <input type="checkbox"
                                       class="recipient-checkbox"
                                       value="{{ $application->id }}"
                                       aria-label="{{ $application->receipt_number }} {{ $application->student_name_korean }} 선택">
                            </td>
                            <td><strong>{{ $application->receipt_number }}</strong></td>
                            <td><small>{{ $application->created_at?->format('Y-m-d H:i') }}</small></td>
                            <td>{{ $application->student_name_korean }}</td>
                            <td>{{ $application->grade }}</td>
                            <td>
                                {{ $application->institution_name }}<br>
                                <small class="text-muted">{{ $application->region }}</small>
                            </td>
                            <td>
                                {{ $application->parent_name }}<br>
                                <small class="text-muted">{{ $application->parent_phone }}</small>
                            </td>
                            <td style="min-width: 180px;">
                                @php $day1Question = $application->instructorQuestion('day1'); @endphp
                                @if($day1Question !== '')
                                    <span class="small">{{ $day1Question }}</span>
                                @else
                                    <span class="text-muted small">없음</span>
                                @endif
                            </td>
                            <td style="min-width: 180px;">
                                @php $day2Question = $application->instructorQuestion('day2'); @endphp
                                @if($day2Question !== '')
                                    <span class="small">{{ $day2Question }}</span>
                                @else
                                    <span class="text-muted small">없음</span>
                                @endif
                            </td>
                            <td>{{ $application->marketing_consent ? '동의' : '미동의' }}</td>
                            <td class="text-nowrap">
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
                            <td colspan="11" class="text-center text-muted py-5">
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

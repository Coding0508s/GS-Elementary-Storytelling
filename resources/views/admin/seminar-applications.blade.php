@extends('admin.layout')

@section('title', '접수 내역')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-person-lines-fill"></i> 접수 내역</h1>
        <p class="text-muted mb-0">접수된 참가자 {{ number_format($applications->total()) }}명</p>
        <p class="mb-0 mt-2">웨비나 링크는 행사 시작 30분 전에 신청자 전화번호로 전송됩니다.</p>
    </div>
    <div class="d-flex gap-2">
        @if($applications->total() > 0)
            <form method="POST" action="{{ route('admin.applications.alimtalk') }}"
                  onsubmit="return confirm(@json(($searchQuery !== '' ? '검색된 ' : '접수된 ').number_format($applications->total()).'명에게 알림톡을 보낼까요?'))">
                @csrf
                <input type="hidden" name="search" value="{{ $searchQuery }}">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-chat-dots"></i> 알림톡 보내기
                </button>
            </form>
        @endif
        <a href="{{ route('admin.applications.excel', request()->only('search')) }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> 엑셀 다운로드
        </a>
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
                            <td colspan="10" class="text-center text-muted py-5">
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

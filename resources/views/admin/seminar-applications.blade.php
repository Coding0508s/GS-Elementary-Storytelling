@extends('admin.layout')

@section('title', '접수 내역')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-person-lines-fill"></i> 접수 내역</h1>
        <p class="text-muted mb-0">접수된 참가자 {{ number_format($applications->total()) }}명</p>
    </div>
    <a href="{{ route('admin.applications.excel', request()->only('search')) }}" class="btn btn-success">
        <i class="bi bi-file-earmark-excel"></i> 엑셀 다운로드
    </a>
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
                        <th>학생</th>
                        <th>학년</th>
                        <th>기관 / 지역</th>
                        <th>학부모</th>
                        <th>궁금한 점</th>
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
                            <td style="min-width: 220px;">
                                @php
                                    $question = $application->teacher_question ?: $application->unit_topic;
                                @endphp
                                @if($question)
                                    <span class="small">{{ $question }}</span>
                                @else
                                    <span class="text-muted small">없음</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
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

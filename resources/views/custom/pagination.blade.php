@if ($paginator->hasPages())
    <nav aria-label="페이지 네비게이션">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <!-- 페이지 정보 -->
            <div class="pagination-info mb-2 mb-md-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    전체 <strong>{{ number_format($paginator->total()) }}</strong>개 중 
                    <strong>{{ number_format(($paginator->currentPage() - 1) * $paginator->perPage() + 1) }}~{{ number_format(min($paginator->currentPage() * $paginator->perPage(), $paginator->total())) }}</strong>개 표시
                    <span class="badge bg-primary ms-1">{{ $paginator->currentPage() }}/{{ $paginator->lastPage() }} 페이지</span>
                </small>
            </div>
            
            <!-- 페이지네이션 버튼 -->
            <ul class="pagination pagination-sm mb-0">
                {{-- 첫 페이지 링크 --}}
                @if ($paginator->currentPage() > 1)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url(1) }}" rel="first" aria-label="첫 페이지">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                    </li>
                @endif

                {{-- 이전 페이지 링크 --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="이전 페이지">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                @endif

                {{-- 페이지 번호 링크들 --}}
                @php
                    $start = max($paginator->currentPage() - 2, 1);
                    $end = min($start + 4, $paginator->lastPage());
                    $start = max($end - 4, 1);
                @endphp

                @if($start > 1)
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                    </li>
                    @if($start > 2)
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    @endif
                @endif

                @for ($i = $start; $i <= $end; $i++)
                    @if ($i == $paginator->currentPage())
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">{{ $i }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                        </li>
                    @endif
                @endfor

                @if($end < $paginator->lastPage())
                    @if($end < $paginator->lastPage() - 1)
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    @endif
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
                    </li>
                @endif

                {{-- 다음 페이지 링크 --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="다음 페이지">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                    </li>
                @endif

                {{-- 마지막 페이지 링크 --}}
                @if ($paginator->currentPage() < $paginator->lastPage())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="마지막 페이지">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
        
        <!-- 모바일용 간단한 페이지네이션 -->
        <div class="d-md-none mt-3">
            <div class="d-flex justify-content-between">
                @if ($paginator->onFirstPage())
                    <span class="btn btn-outline-secondary disabled">
                        <i class="bi bi-chevron-left"></i> 이전
                    </span>
                @else
                    <a class="btn btn-outline-primary" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <i class="bi bi-chevron-left"></i> 이전
                    </a>
                @endif

                <span class="btn btn-outline-secondary disabled">
                    {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
                </span>

                @if ($paginator->hasMorePages())
                    <a class="btn btn-outline-primary" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        다음 <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span class="btn btn-outline-secondary disabled">
                        다음 <i class="bi bi-chevron-right"></i>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif

<style>
/* 커스텀 페이지네이션 스타일 */
.pagination .page-link {
    color: #667eea;
    border-color: #dee2e6;
    padding: 0.5rem 0.75rem;
    margin: 0 2px;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

.pagination .page-link:hover {
    color: #fff;
    background-color: #667eea;
    border-color: #667eea;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
    color: #fff;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    background-color: #fff;
    border-color: #dee2e6;
}

.pagination-info {
    font-size: 0.875rem;
}

/* 반응형 디자인 */
@media (max-width: 768px) {
    .d-md-none .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
}
</style>

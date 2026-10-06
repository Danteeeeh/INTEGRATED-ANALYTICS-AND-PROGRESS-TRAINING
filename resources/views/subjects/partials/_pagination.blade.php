@php
    /**
     * Compact paginator matching the subjects table footer.
     *
     * Laravel's default `links()` renders Bootstrap/Tailwind markup that does
     * not belong in this layout, so the pager is built by hand.
     *
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     */
    $paginator = $paginator ?? $subjects;
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    // Window the page numbers so a 200-row list does not print 20 buttons.
    $window = 2;
    $start = max(1, $current - $window);
    $end = min($last, $current + $window);
@endphp

<nav class="subjects-pager" aria-label="Subjects pages">
    @if ($paginator->onFirstPage())
        <span class="subjects-pager-btn is-disabled" aria-hidden="true">&laquo;</span>
    @else
        <a class="subjects-pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="First page">&laquo;</a>
    @endif

    <a class="subjects-pager-btn {{ $current === 1 ? 'is-active' : '' }}"
       href="{{ $paginator->url(1) }}">1</a>

    @if ($start > 2)
        <span class="subjects-pager-gap">&hellip;</span>
    @endif

    @for ($page = max(2, $start); $page <= min($last - 1, $end); $page++)
        <a class="subjects-pager-btn {{ $page === $current ? 'is-active' : '' }}"
           href="{{ $paginator->url($page) }}">{{ $page }}</a>
    @endfor

    @if ($end < $last - 1)
        <span class="subjects-pager-gap">&hellip;</span>
    @endif

    @if ($last > 1)
        <a class="subjects-pager-btn {{ $current === $last ? 'is-active' : '' }}"
           href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    @if ($paginator->hasMorePages())
        <a class="subjects-pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&raquo;</a>
    @else
        <span class="subjects-pager-btn is-disabled" aria-hidden="true">&raquo;</span>
    @endif
</nav>

<style>
    .subjects-pager{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
    .subjects-pager-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:.78rem;font-weight:600;text-decoration:none}
    .subjects-pager-btn:hover{border-color:#c7d2fe;color:#4338ca}
    .subjects-pager-btn.is-active{background:#4f46e5;border-color:#4f46e5;color:#fff}
    .subjects-pager-btn.is-disabled{opacity:.4;cursor:not-allowed}
    .subjects-pager-gap{padding:0 4px;color:#94a3b8}
</style>
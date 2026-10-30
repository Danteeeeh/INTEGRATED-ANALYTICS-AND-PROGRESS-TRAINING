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
    /* Same --dash-* tokens as the list it pages through, so the pager stays legible
       on the dark dashboard shell and follows light mode with the rest of the app. */
    .subjects-pager{display:flex;align-items:center;gap:4px;flex-wrap:wrap}
    .subjects-pager-btn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px;border:1px solid var(--dash-line,rgba(153,174,214,.18));border-radius:8px;background:var(--dash-surface-raised,#1b2437);color:var(--dash-text,#eef4ff);font-size:.78rem;font-weight:600;text-decoration:none}
    .subjects-pager-btn:hover{border-color:rgba(98,201,245,.45);color:var(--dash-cyan,#62c9f5);background:rgba(77,143,240,.14)}
    .subjects-pager-btn.is-active{background:linear-gradient(135deg,#2449c6,#173aa8);border-color:#3159d1;color:#fff}
    .subjects-pager-btn.is-disabled{opacity:.4;cursor:not-allowed}
    .subjects-pager-gap{padding:0 4px;color:var(--dash-muted,#98a7c4)}
</style>
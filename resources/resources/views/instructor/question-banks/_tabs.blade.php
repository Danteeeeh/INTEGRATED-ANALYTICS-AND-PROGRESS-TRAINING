{{--
    Test Bank tab bar (§1).

    The whole section lives under the existing question-banks area rather than
    in a parallel copy of it, so each tab is one route deep and the sidebar link
    that already exists keeps working.

    Expects:
      $activeTab   = 'questions'|'banks'|'categories'|'statistics'
      $routePrefix = 'instructor.' (default) | 'admin.'
--}}
@php($activeTab = $activeTab ?? 'banks')
@php($routePrefix = $routePrefix ?? (auth()->user()?->isAdmin() ? 'admin.' : 'instructor.'))

<style>
    .tb-tabs{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 18px;padding:6px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:12px;}
    .tb-tabs a{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:8px;font-size:.875rem;font-weight:600;color:#475569;text-decoration:none;transition:background .15s,color .15s;white-space:nowrap;}
    .tb-tabs a:hover{background:#e2e8f0;color:#1e293b;}
    .tb-tabs a.is-active{background:#2563eb;color:#fff;box-shadow:0 2px 6px rgba(37,99,235,.28);}
    .tb-tabs .tb-count{font-size:.75rem;font-weight:700;padding:1px 7px;border-radius:99px;background:rgba(255,255,255,.22);}
    .tb-tabs a:not(.is-active) .tb-count{background:#e2e8f0;color:#64748b;}
</style>

<nav class="tb-tabs" aria-label="Test Bank sections">
    <a href="{{ route($routePrefix.'test_bank.index') }}" class="{{ $activeTab === 'questions' ? 'is-active' : '' }}">
        <i class="fa-solid fa-list-check"></i> Questions
    </a>
    <a href="{{ route($routePrefix.'question_banks.index') }}" class="{{ $activeTab === 'banks' ? 'is-active' : '' }}">
        <i class="fa-solid fa-database"></i> Banks
    </a>
    <a href="{{ route($routePrefix.'question_banks.categories.index') }}" class="{{ $activeTab === 'categories' ? 'is-active' : '' }}">
        <i class="fa-solid fa-tag"></i> Categories
    </a>
    <a href="{{ route($routePrefix.'question_banks.statistics.index') }}" class="{{ $activeTab === 'statistics' ? 'is-active' : '' }}">
        <i class="fa-solid fa-chart-column"></i> Question Statistics
    </a>
</nav>

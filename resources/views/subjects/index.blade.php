@php
    /**
     * Shared by the admin, instructor and student panels.
     *
     * @var \Illuminate\Pagination\LengthAwarePaginator $subjects
     */
    $viewer = auth()->user();
    $canVerify = $viewer->isAdmin() || $viewer->isInstructor();
    $perPage = (int) request('per_page', 10) ?: 10;
    $prefix = $routePrefix ?? 'admin.';
@endphp

<div class="subjects-page">
    <div class="subjects-header">
        <p class="subjects-eyebrow">LMS Subjects</p>
        <h1 class="subjects-title">LMS Subjects</h1>
        <p class="subjects-sub">
            @if ($viewer->isStudent())
                Every subject you are currently enrolled in.
            @elseif ($viewer->isInstructor())
                The subjects you are teaching.
            @else
                All subject offerings across every section and academic period.
            @endif
        </p>
    </div>

    <div class="subjects-card">
        <div class="subjects-card-head">
            <h2 class="subjects-card-title">List of Subjects</h2>

            <span class="subjects-count">
                {{ $subjects->firstItem() }}–{{ $subjects->lastItem() }} of {{ $subjects->total() }}
            </span>
        </div>

        <form method="GET" action="{{ url()->current() }}" class="subjects-filters">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Search section code, subject name or shortname…" aria-label="Search subjects">

            <select name="subject_status" aria-label="Filter subject status">
                <option value="">All subject status</option>
                <option value="verified" @selected(($filters['subject_status'] ?? '') === 'verified')>Verified</option>
                <option value="draft" @selected(($filters['subject_status'] ?? '') === 'draft')>Draft</option>
            </select>

            <select name="enrollment_status" aria-label="Filter enrollment status">
                <option value="">All enrollment status</option>
                @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['enrollment_status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="section_id" aria-label="Filter section">
                <option value="">All sections</option>
                @foreach ($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>
                        {{ $section->code }}@if ($section->program) — {{ $section->program->name }}@endif
                    </option>
                @endforeach
            </select>

            <select name="period_id" aria-label="Filter academic period">
                <option value="">All periods</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected((string) ($filters['period_id'] ?? '') === (string) $period->id)>
                        {{ $period->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="subjects-btn subjects-btn-primary">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <a href="{{ url()->current() }}" class="subjects-btn">Clear</a>
        </form>

        <div class="subjects-table-wrap">
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>Section Code</th>
                        <th class="num">Students Count</th>
                        <th>Subject Status</th>
                        <th>Shortname</th>
                        <th>Enrollment Status</th>
                        <th>Subject Name</th>
                        <th class="act">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subjects as $subject)
                        @php
                            $verified = $subject->isSubjectVerified();
                            $enrolled = (int) $subject->enrolled_count;
                            $limit = $subject->capacity;
                        @endphp
                        <tr>
                            <td class="mono">{{ $subject->code }}</td>

                            <td class="num">
                                <span @class(['subjects-count-cell', 'is-empty' => $enrolled === 0])>
                                    {{ $enrolled }}{{ $limit ? ' / '.$limit : '' }}
                                </span>
                            </td>

                            <td>
                                <span @class(['subjects-pill', $verified ? 'is-verified' : 'is-draft'])>
                                    {{ $verified ? 'Verified' : 'Draft' }}
                                </span>
                            </td>

                            <td class="mono">{{ $subject->subject_shortname ?: '—' }}</td>

                            <td>
                                <span @class(['subjects-pill', 'is-status-'.$subject->status])>
                                    {{ ucfirst($subject->status) }}
                                </span>
                            </td>

                            <td>
                                <span class="subjects-name">{{ $subject->subject_name ?: '—' }}</span>
                                @if ($subject->instructor)
                                    <span class="subjects-sub-cell">{{ $subject->instructor->name }}</span>
                                @endif
                            </td>

                            <td class="act">
                                <div class="subjects-actions">
                                    <a href="{{ route($prefix.'subjects.scores', $subject) }}" class="subjects-btn subjects-btn-primary">
                                        <i class="fa-solid fa-clipboard-check"></i> Check Available Scores
                                    </a>

                                    @if ($canVerify)
                                        @if ($verified)
                                            @if ($canUnverify ?? false)
                                                <form method="POST" action="{{ route($prefix.'subjects.unverify', $subject) }}">
                                                    @csrf
                                                    <button type="submit" class="subjects-btn subjects-btn-ghost"
                                                            title="Return to draft"
                                                            onclick="return confirm('Reopen {{ $subject->code }}? Grades will be editable again.')">
                                                        <i class="fa-solid fa-lock-open"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <form method="POST" action="{{ route($prefix.'subjects.verify', $subject) }}">
                                                @csrf
                                                <button type="submit" class="subjects-btn subjects-btn-verify"
                                                        title="Verify subject"
                                                        onclick="return confirm('Verify {{ $subject->code }} as final?')">
                                                    <i class="fa-solid fa-badge-check"></i> Verify
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="subjects-empty">
                                <i class="fa-solid fa-inbox"></i>
                                <p>No subjects found.</p>
                                <a href="{{ url()->current() }}">Clear the filters</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subjects->hasPages())
            <div class="subjects-foot">
                <form method="GET" action="{{ url()->current() }}" class="subjects-perpage">
                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="per_page">Items per page:</label>
                    <select id="per_page" name="per_page" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>

                <nav class="subjects-pagination" aria-label="Subjects pages">
                    @include('subjects.partials._pagination', ['paginator' => $subjects])
                </nav>
            </div>
        @endif
    </div>
</div>

<style>
    .subjects-page{font-family:inherit;color:#0f172a}
    .subjects-header{margin:0 0 22px}
    .subjects-eyebrow{margin:0;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b}
    .subjects-title{margin:4px 0 0;font-size:1.75rem;font-weight:800;color:#0b1220}
    .subjects-sub{margin:8px 0 0;font-size:.85rem;color:#64748b}
    .subjects-card{background:#fff;border:1px solid #e6edf7;border-radius:16px;padding:22px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .subjects-card-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px}
    .subjects-card-title{margin:0;font-size:1rem;font-weight:700;color:#0b1220}
    .subjects-count{font-size:.78rem;color:#64748b}
    .subjects-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin-bottom:18px}
    .subjects-filters input,.subjects-filters select{width:100%;padding:9px 11px;border:1px solid #dbe3ef;border-radius:9px;font-size:.83rem;background:#fff;color:#0f172a}
    .subjects-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border:1px solid #dbe3ef;border-radius:9px;background:#fff;color:#334155;font-size:.8rem;font-weight:600;text-decoration:none;cursor:pointer;white-space:nowrap;transition:background .15s,border-color .15s}
    .subjects-btn:hover{background:#f1f5f9}
    .subjects-btn-primary{background:#4f46e5;border-color:#4f46e5;color:#fff}
    .subjects-btn-primary:hover{background:#4338ca;border-color:#4338ca}
    .subjects-btn-verify{background:#059669;border-color:#059669;color:#fff}
    .subjects-btn-verify:hover{background:#047857;border-color:#047857}
    .subjects-btn-ghost{padding:9px 11px}
    .subjects-table-wrap{overflow-x:auto}
    .subjects-table{width:100%;border-collapse:collapse;font-size:.83rem}
    .subjects-table th{padding:10px;text-align:left;font-size:.7rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#64748b;border-bottom:1px solid #e6edf7;white-space:nowrap}
    .subjects-table td{padding:12px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
    .subjects-table tbody tr:hover{background:#f8fafc}
    .subjects-table .num{text-align:center}
    .subjects-table .act{text-align:right}
    .subjects-table .mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem;white-space:nowrap}
    .subjects-count-cell{font-variant-numeric:tabular-nums;font-weight:600}
    .subjects-count-cell.is-empty{color:#94a3b8;font-weight:500}
    .subjects-name{display:block;color:#0f172a}
    .subjects-sub-cell{display:block;margin-top:2px;font-size:.72rem;color:#94a3b8}
    .subjects-pill{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.68rem;font-weight:700;letter-spacing:.03em;text-transform:uppercase}
    .subjects-pill.is-verified{background:#dcfce7;color:#15803d}
    .subjects-pill.is-draft{background:#fef3c7;color:#b45309}
    .subjects-pill.is-status-active{background:#dcfce7;color:#15803d}
    .subjects-pill.is-status-inactive{background:#f1f5f9;color:#64748b}
    .subjects-pill.is-status-archived,.subjects-pill.is-status-cancelled{background:#fee2e2;color:#b91c1c}
    .subjects-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px}
    .subjects-actions form{display:inline}
    .subjects-empty{text-align:center;padding:44px 12px;color:#94a3b8}
    .subjects-empty i{font-size:1.8rem;margin-bottom:10px;display:block}
    .subjects-empty p{margin:0 0 6px}
    .subjects-empty a{color:#4f46e5;font-size:.8rem}
    .subjects-foot{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:18px}
    .subjects-perpage{display:flex;align-items:center;gap:8px;font-size:.78rem;color:#64748b}
    .subjects-perpage select{padding:6px 9px;border:1px solid #dbe3ef;border-radius:7px;font-size:.78rem}
    .subjects-pagination nav{display:flex}
    .subjects-pagination svg{width:15px;height:15px}
    .dash-scope .subjects-page,.subjects-page{--x:0}
</style>
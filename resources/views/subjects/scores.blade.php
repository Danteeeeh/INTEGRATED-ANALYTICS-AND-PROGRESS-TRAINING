@php
    /**
     * @var \App\Models\ClassModel $class
     * @var \Illuminate\Support\Collection $items
     * @var array<string, mixed> $summaries
     */
    $viewer = auth()->user();
    $canVerify = $viewer->isAdmin() || $viewer->isInstructor();
    $missing = collect($missing_student_ids);
@endphp

<div class="subjects-page">
    <div class="subjects-header">
        <p class="subjects-eyebrow">Check Available Scores</p>
        <h1 class="subjects-title">{{ $class->course?->title ?? $class->code }}</h1>
        <p class="subjects-sub">
            {{ $class->code }}
            @if ($class->section) · {{ $class->section->code }} @endif
            @if ($class->academicPeriod) · {{ $class->academicPeriod->name }} @endif
            @if ($class->instructor) · {{ $class->instructor->name }} @endif
        </p>
    </div>

    <div class="subjects-card">
        <div class="subjects-card-head">
            <h2 class="subjects-card-title">Score Availability</h2>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span @class(['subjects-pill', $class->isSubjectVerified() ? 'is-verified' : 'is-draft'])>
                    {{ $class->isSubjectVerified() ? 'Verified' : 'Draft' }}
                </span>
                <a href="{{ url()->previous() ?: url()->current() }}" class="subjects-btn">Back to list</a>
            </div>
        </div>

        <div class="subjects-kpis">
            <div class="subjects-kpi">
                <span class="subjects-kpi-value">{{ $enrollment_count }}</span>
                <span class="subjects-kpi-label">Students enrolled</span>
            </div>
            <div class="subjects-kpi">
                <span class="subjects-kpi-value">{{ $released_count }} / {{ $item_count }}</span>
                <span class="subjects-kpi-label">Grade items released</span>
            </div>
            <div class="subjects-kpi">
                <span class="subjects-kpi-value">{{ $graded_count }} / {{ $enrollment_count }}</span>
                <span class="subjects-kpi-label">Students with a grade</span>
            </div>
            <div class="subjects-kpi">
                <span class="subjects-kpi-value">{{ $class_average > 0 ? number_format($class_average, 1).'%' : '—' }}</span>
                <span class="subjects-kpi-label">Class average</span>
            </div>
        </div>

        @if ($is_complete)
            <div class="subjects-note is-ok">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <strong>Complete.</strong> Every grade item is released and every enrolled student has a
                    computed grade. This subject can be verified.
                </div>
            </div>
        @else
            <div class="subjects-note is-warn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <strong>Not ready to verify.</strong>
                    <ul class="subjects-blockers">
                        @foreach ($blockers as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <h3 class="subjects-subhead">Grade items</h3>

        <div class="subjects-table-wrap">
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Type</th>
                        <th class="num">Max points</th>
                        <th class="num">Weight</th>
                        <th class="num">Graded</th>
                        <th>Released</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td><span class="subjects-pill is-status-inactive">{{ ucfirst($item->item_type) }}</span></td>
                            <td class="num">{{ $item->max_points }}</td>
                            <td class="num">{{ rtrim(rtrim(number_format((float) ($item->factor ?? 1), 2), '0'), '.') }}</td>
                            <td class="num">{{ $item->graded_count ?? 0 }} / {{ $enrollment_count }}</td>
                            <td>
                                @if ($item->is_released)
                                    <span class="subjects-pill is-verified">Released</span>
                                @else
                                    <span class="subjects-pill is-draft">Not released</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="subjects-empty">
                                <i class="fa-solid fa-list"></i>
                                <p>No grade items in this subject yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3 class="subjects-subhead">
            Per-student scores
            @if ($missing->isNotEmpty())
                <span class="subjects-count-inline">{{ $missing->count() }} pending</span>
            @endif
        </h3>

        <div class="subjects-table-wrap">
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student No</th>
                        <th class="num">Earned</th>
                        <th class="num">Possible</th>
                        <th class="num">Final</th>
                        <th>Letter</th>
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($class->enrollments()->countable()->with('student:id,first_name,last_name,identifier')->get() as $enrollment)
                        @php
                            $summary = $summaries[$enrollment->student_id] ?? null;
                            $isGraded = (bool) ($summary['is_graded'] ?? false);
                        @endphp
                        <tr>
                            <td>{{ $enrollment->student?->name ?? '—' }}</td>
                            <td class="mono">{{ $enrollment->student?->identifier ?? '—' }}</td>
                            <td class="num">{{ $isGraded ? rtrim(rtrim(number_format((float) $summary['earned_points'], 2), '0'), '.') : '—' }}</td>
                            <td class="num">{{ $isGraded ? rtrim(rtrim(number_format((float) $summary['max_points'], 2), '0'), '.') : '—' }}</td>
                            <td class="num">{{ $isGraded ? number_format((float) $summary['percent'], 2).'%' : '—' }}</td>
                            <td>{{ $isGraded ? $summary['letter_grade'] : '—' }}</td>
                            <td>
                                @if ($isGraded)
                                    <span class="subjects-pill is-verified">Graded</span>
                                @else
                                    <span class="subjects-pill is-draft">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="subjects-empty">
                                <i class="fa-solid fa-user-slash"></i>
                                <p>No students are enrolled in this subject.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($canVerify)
            <div class="subjects-verify-bar">
                @if ($class->isSubjectVerified())
                    <div class="subjects-verify-meta">
                        <i class="fa-solid fa-shield-halved"></i>
                        Verified
                        @if ($class->subject_verified_at)
                            on {{ $class->subject_verified_at->format('M d, Y g:i A') }}
                            @if ($class->subjectVerifiedBy) by {{ $class->subjectVerifiedBy->name }} @endif
                        @endif
                    </div>

                    @if ($viewer->isAdmin())
                        <form method="POST" action="{{ route(($routePrefix ?? 'admin.').'subjects.unverify', $class) }}">
                            @csrf
                            <button type="submit" class="subjects-btn"
                                    onclick="return confirm('Reopen {{ $class->code }}? Grades will be editable again.')">
                                <i class="fa-solid fa-lock-open"></i> Return to Draft
                            </button>
                        </form>
                    @endif
                @else
                    <form method="POST" action="{{ route(($routePrefix ?? 'admin.').'subjects.verify', $class) }}">
                        @csrf
                        <button type="submit" class="subjects-btn subjects-btn-verify" @disabled(! $is_complete)>
                            <i class="fa-solid fa-badge-check"></i>
                            {{ $is_complete ? 'Verify Subject' : 'Cannot Verify Yet' }}
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
    .subjects-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
    .subjects-kpi{display:flex;flex-direction:column;gap:4px;padding:14px;border:1px solid #e6edf7;border-radius:12px;background:#f8fafc}
    .subjects-kpi-value{font-size:1.3rem;font-weight:800;color:#0b1220;font-variant-numeric:tabular-nums}
    .subjects-kpi-label{font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em}
    .subjects-note{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:12px;margin-bottom:20px;font-size:.84rem}
    .subjects-note.is-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534}
    .subjects-note.is-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
    .subjects-note i{margin-top:2px}
    .subjects-blockers{margin:6px 0 0;padding-left:18px}
    .subjects-subhead{margin:24px 0 10px;font-size:.85rem;font-weight:700;color:#0b1220;display:flex;align-items:center;gap:10px}
    .subjects-count-inline{font-size:.7rem;font-weight:600;color:#b45309;background:#fef3c7;padding:2px 8px;border-radius:999px}
    .subjects-verify-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #e6edf7}
    .subjects-verify-meta{font-size:.8rem;color:#166534;background:#f0fdf4;border:1px solid #bbf7d0;padding:8px 12px;border-radius:9px}
    .subjects-btn[disabled]{opacity:.55;cursor:not-allowed}
</style>
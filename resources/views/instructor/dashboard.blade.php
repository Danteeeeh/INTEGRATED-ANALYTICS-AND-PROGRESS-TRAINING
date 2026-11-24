@extends('layouts.instructor')

@section('title', 'Faculty Dashboard')
@php
    $activeNav = 'dashboard';
    $pageTitle = 'Faculty Dashboard';
    $pageIcon = '<i class="fa-solid fa-chalkboard-user"></i>';
    $stats ??= [];
    // Quick-action targets: deep-link into the instructor's first course/class
    // when one exists, otherwise fall back to the index pages. Display-only
    // helpers — no data, route or permission changes.
    $firstCourse = $stats['my_courses_list']?->first();
    $firstClass = $stats['my_classes_list']?->first();
    $academicTerm = $firstClass?->academicPeriod?->name
        ?? $firstCourse?->academicPeriod?->name
        ?? \App\Models\AcademicPeriod::where('is_current', true)->value('name');
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-chalkboard-user"></i>
            Faculty Dashboard
        </h2>
    </div>
@endsection


@section('content')
{{-- ═══ DESIGN ENHANCEMENT: scoped tokens (instructor dashboard only) ═══ --}}
<style>
    /* Scoped theme tokens — dark (default) + light-mode overrides. */
    .main:has(.dash-scope) .content{
        --id-bg:#0d1220;--id-surface:#151c2e;--id-surface-2:#1a2236;--id-surface-3:#202a42;
        --id-line:rgba(140,163,210,.14);--id-line-strong:rgba(140,163,210,.22);
        --id-text:#eef3ff;--id-muted:#9aa9c7;--id-faint:#72809e;
        --id-brand:#5b8cff;--id-brand-2:#3a6af0;--id-cyan:#5cc9f5;
        --id-green:#34d399;--id-amber:#fbbf24;--id-rose:#fb7185;--id-violet:#a78bfa;
        --id-shadow:0 10px 30px rgba(2,6,18,.35);
        --id-shadow-lg:0 18px 50px rgba(2,6,18,.45);
    }
    body.light-mode .main:has(.dash-scope) .content{
        --id-bg:#eef2f9;--id-surface:#ffffff;--id-surface-2:#f7f9fd;--id-surface-3:#eef2f9;
        --id-line:rgba(24,44,84,.10);--id-line-strong:rgba(24,44,84,.16);
        --id-text:#16233c;--id-muted:#5a6b8a;--id-faint:#8a97b1;
        --id-brand:#2563eb;--id-cyan:#0e7490;
        --id-shadow:0 8px 24px rgba(24,44,84,.08);
        --id-shadow-lg:0 16px 42px rgba(24,44,84,.14);
    }
    .main:has(.dash-scope) .content{
        background:radial-gradient(1000px 420px at 85% -8%,rgba(91,140,255,.10),transparent 55%),
                   radial-gradient(700px 380px at -10% 12%,rgba(92,201,245,.07),transparent 50%),
                   var(--id-bg);
    }

    .dash-join-btn{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:8px;background:var(--id-brand);color:#fff;font-size:.72rem;font-weight:800;border:0;cursor:pointer;text-decoration:none}
    .dash-end{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:8px;background:rgba(251,113,133,.14);color:var(--id-rose);font-size:.72rem;font-weight:800;border:1px solid rgba(251,113,133,.34);cursor:pointer}
</style>
<div class="dash-scope">
    <div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="Welcome back, {{ auth()->user()->name ?? 'Instructor' }}"
        subtitle="Keep your classes moving forward — here's what needs your attention."
        icon="fa-chalkboard-user"
        kicker="Teaching workspace"
    >
        <x-slot name="meta">
            <span class="live-dot"></span>
            <span>{{ now()->format('l, F j, Y') }}</span>
            @if($academicTerm)
                <span>·</span>
                <span><i class="fa-solid fa-calendar-days"></i> {{ $academicTerm }}</span>
            @endif
            <span>·</span>
            <span>{{ ($stats['published_courses'] ?? 0) }} published · {{ ($stats['draft_courses'] ?? 0) }} draft</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-book-open"></i> My courses</a>
            <a href="{{ route('instructor.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-school"></i> My classes</a>
            <a href="{{ route('instructor.reports.index') }}" class="btn btn-secondary"><i class="fa-solid fa-file-lines"></i> Reports</a>
            <div class="dash-action dropdown">
                <button class="dash-action-btn"><i class="fa-solid fa-download"></i> Export</button>
                <div class="dropdown-content">
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'students', 'format' => 'csv']) }}" class="dropdown-item"><i class="fa-solid fa-users"></i> Students Data</a>
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'grades', 'format' => 'csv']) }}" class="dropdown-item"><i class="fa-solid fa-chart-line"></i> Grades Data</a>
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'performance', 'format' => 'csv']) }}" class="dropdown-item"><i class="fa-solid fa-poll"></i> Performance Data</a>
                </div>
            </div>
            {{-- Report export for the Academic Affairs (AA) system — one click,
                 AA-ready CSV of per-student LMS scores per subject. --}}
            <a href="{{ route('instructor.dashboard.export', ['type' => 'aa', 'format' => 'csv']) }}" class="btn btn-primary"><i class="fa-solid fa-file-export"></i> Export Report for AA</a>
            <button onclick="refreshDashboard()" class="btn btn-secondary"><i class="fa-solid fa-sync-alt"></i> Refresh</button>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ QUICK ACTIONS ═══ --}}
    <section class="dash-panel">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-bolt"></i> Quick actions</h4>
            <span class="panel-count" title="Active course/class context">{{ $firstClass?->code ?? $firstCourse?->code ?? '—' }}</span>
        </div>
        <div class="dash-quick-actions">
            <a href="{{ $firstCourse ? route('instructor.courses.modules.create', $firstCourse) : route('instructor.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-layer-group"></i> Create module</a>
            <a href="{{ $firstCourse ? route('instructor.courses.assignments.create', $firstCourse) : route('instructor.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-file-pen"></i> Create assignment</a>
            <a href="{{ $firstCourse ? route('instructor.courses.quizzes.create', $firstCourse) : route('instructor.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-circle-question"></i> Create quiz</a>
            <a href="{{ $firstClass ? route('instructor.classes.virtual_classes.create', $firstClass) : route('instructor.classes.index') }}" class="btn btn-primary"><i class="fa-solid fa-video"></i> Create virtual class</a>
        </div>
    </section>

    {{-- ═══ SEARCH BAR ═══ --}}
    <section class="dash-search">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" id="globalSearch" placeholder="Search students, courses, classes, assignments, quizzes..." class="search-input">
                <select id="searchType" class="search-type">
                    <option value="all">All</option>
                    <option value="students">Students</option>
                    <option value="courses">Courses</option>
                    <option value="classes">Classes</option>
                    <option value="assignments">Assignments</option>
                    <option value="quizzes">Quizzes</option>
                </select>
            </div>
            <div id="searchResults" class="search-results hidden"></div>
        </div>
    </section>

    {{-- ═══ STAT TILES ═══ --}}
    <div class="user-stat-grid">
        <x-user-stat-card
            label="My Courses"
            value="{{ $stats['my_courses'] ?? 0 }}"
            icon="fa-book"
            valueId="stat-courses-value"
            trend="{{ $stats['draft_courses'] ?? 0 }} in draft"
            footer="Courses you teach"
        />
        <x-user-stat-card
            label="Total Students"
            value="{{ $stats['total_students'] ?? 0 }}"
            icon="fa-user-graduate"
            valueId="stat-students-value"
            trend="{{ $stats['active_enrollments'] ?? 0 }} active"
        >
            <x-slot name="footer"><span id="stat-atrisk-value">{{ $stats['at_risk_count'] ?? 0 }}</span> at-risk students</x-slot>
        </x-user-stat-card>
        <x-user-stat-card
            label="Pending to Check"
            value="{{ $stats['to_check_count'] ?? 0 }}"
            icon="fa-clipboard-check"
            trend="Action needed"
            footer="{{ $stats['pending_submissions'] ?? 0 }} submissions waiting"
        />
        <x-user-stat-card
            label="Upcoming Virtual Classes"
            value="{{ count($stats['upcoming_virtual_classes'] ?? []) }}"
            icon="fa-video"
            trend="Next 7 days"
            footer="{{ $stats['total_virtual_classes'] ?? 0 }} classes total"
        />
    </div>

    {{-- ═══ CLASS PERFORMANCE + NEEDS ATTENTION ═══ --}}
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-chart-simple"></i> Class performance</h4>
                @include('partials.dashboard-panel-cap', [
                    'count' => count($stats['class_performance'] ?? []),
                    'shown' => 6,
                    'total' => count($stats['class_performance'] ?? []),
                    'href' => route('instructor.dashboard.performance'),
                    'viewAllUrl' => route('instructor.dashboard.performance'),
                    'viewAllLabel' => 'Full report',
                    'viewAllOnlyWhenCapped' => true,
                ])
            </div>
            <ul class="perf-list">
                @forelse(collect($stats['class_performance'] ?? [])->take(6) as $perf)
                    @php $pClass = $perf['class'] ?? null; @endphp
                    @if($pClass)
                        <li class="perf-item">
                            <div class="perf-top">
                                <p class="perf-name">{{ $pClass->code ?? '' }} — {{ $pClass->course?->title ?? 'Class' }}</p>
                                @if($perf['active_students'] ?? 0)
                                    <span class="perf-score">{{ number_format($perf['average_grade'] ?? 0, 1) }}%</span>
                                @else
                                    <span class="perf-score" style="opacity:.6">No students</span>
                                @endif
                            </div>
                            <div class="perf-bar">
                                <span style="width: {{ ($perf['active_students'] ?? 0) ? min(($perf['average_grade'] ?? 0), 100) : 0 }}%"></span>
                            </div>
                            <div class="perf-meta">
                                <span>{{ $perf['active_students'] ?? 0 }} students</span>
                                <span>{{ round($perf['completion_rate'] ?? 0) }}&percnt; completion</span>
                                <a href="{{ route('instructor.classes.learning-plans.index', $pClass) }}" style="font-size:0.8rem"><i class="fa-solid fa-route"></i> Learning plans</a>
                            </div>
                        </li>
                    @endif
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-school"></i> No classes yet</li>
                @endforelse
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-triangle-exclamation"></i> Needs attention</h4>
                @include('partials.dashboard-panel-cap', [
                    'count' => $stats['at_risk_count'] ?? 0,
                    'shown' => 4,
                    'total' => $stats['at_risk_count'] ?? 0,
                    'href' => route('instructor.dashboard.at-risk'),
                ])
            </div>
            <ul class="dash-list">
                @forelse(collect($stats['at_risk_students'] ?? [])->take(4) as $enrollment)
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-rose"><i class="fa-solid fa-user"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title">{{ $enrollment->student?->name }}</p>
                            <p class="dash-list-sub">{{ $enrollment->class?->course?->title ?? '' }}</p>
                        </div>
                        <div class="dash-list-meta">
                            <span class="dash-meta-chip m-rose">{{ isset($enrollment->computed_percent) ? number_format($enrollment->computed_percent, 1) . '%' : 'No grade' }}</span>
                            <span class="dash-list-date">{{ $enrollment->class?->code }}</span>
                        </div>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-face-smile"></i> No at-risk students — great job!</li>
                @endforelse
            </ul>
        </section>

            {{-- ═══ MY COURSES (course cards) ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-book-open"></i> My courses</h4>
                    @include('partials.dashboard-panel-cap', [
                        'shown' => 3,
                        'total' => count($stats['class_performance'] ?? []),
                        'href' => route('instructor.dashboard.performance'),
                        'viewAllUrl' => route('instructor.dashboard.performance'),
                    ])
                </div>
                <div class="course-card-grid">
                    @forelse(collect($stats['class_performance'] ?? [])->take(3) as $perf)
                        @php
                            $cClass = $perf['class'] ?? null;
                            $cCourse = $cClass?->course;
                            $cColor = ['#4d8ff0', '#62c9f5', '#a78bfa', '#34d399', '#fbbf24', '#fb7185', '#22d3ee', '#f472b6'][($cClass?->id ?? 0) % 8];
                        @endphp
                        @if($cClass)
                            <a class="course-card" href="{{ route('instructor.classes.show', $cClass) }}" style="border-top:3px solid {{ $cColor }};">
                                <div class="course-card-top">
                                    <span class="course-card-code" style="color:{{ $cColor }};">{{ $cCourse?->code ?? $cClass->code }}</span>
                                    <span class="dash-meta-chip {{ ($perf['active_students'] ?? 0) > 0 ? 'm-green' : 'm-gray' }}">{{ ($perf['active_students'] ?? 0) > 0 ? 'Active' : 'Empty' }}</span>
                                </div>
                                <p class="course-card-title">{{ $cCourse?->title ?? 'Class' }}</p>
                                <p class="course-card-instructor">
                                    <i class="fa-solid fa-chalkboard-user"></i>
                                    {{ $cClass->instructor?->name ?? 'Instructor TBA' }}
                                    @if($cClass->section?->name)<span class="course-card-class">· {{ $cClass->section->name }}</span>@endif
                                </p>
                                <div class="course-card-progress">
                                    <div class="dash-progress"><span style="width: {{ min((float) ($perf['completion_rate'] ?? 0), 100) }}%"></span></div>
                                    <span class="dash-progress-label">{{ round($perf['completion_rate'] ?? 0) }}%</span>
                                </div>
                                <div class="course-card-foot">
                                    <span class="course-card-count"><i class="fa-solid fa-users"></i> {{ $perf['active_students'] ?? 0 }} enrolled</span>
                                    <span class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-right"></i> Open</span>
                                </div>
                            </a>
                        @endif
                    @empty
                        <div class="empty-state"><i class="fa-solid fa-book-open"></i> No classes assigned yet.</div>
                    @endforelse
                </div>
            </section>

            {{-- ═══ RECENT COURSE ACTIVITY ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-user-plus"></i> Recent course activity</h4>
                    @include('partials.dashboard-panel-cap', [
                        'count' => $stats['recent_enrollments_total'] ?? ($stats['recent_enrollments'] ?? collect())->count(),
                        'shown' => 5,
                        'total' => $stats['recent_enrollments_total'] ?? ($stats['recent_enrollments'] ?? collect())->count(),
                        'href' => route('instructor.enrollments.index'),
                        'viewAllUrl' => route('instructor.enrollments.index'),
                        'viewAllOnlyWhenCapped' => true,
                    ])
                </div>
                <ul class="dash-list">
                    @forelse(collect($stats['recent_enrollments'] ?? [])->take(5) as $enrollment)
                        <li class="dash-list-item">
                            <span class="dash-list-icon i-green"><i class="fa-solid fa-user-graduate"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">{{ $enrollment->student?->name }}</p>
                                <p class="dash-list-sub">{{ $enrollment->class?->course?->title ?? $enrollment->class?->code }}</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip {{ $enrollment->status === 'active' ? 'm-green' : 'm-gray' }}">{{ ucfirst($enrollment->status) }}</span>
                                <span class="dash-list-date">{{ $enrollment->created_at?->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-user-plus"></i> No recent enrollments</li>
                    @endforelse
                </ul>
            </section>

            {{-- ═══ GRADE EXTRACTION & SUBMISSION (Section 4/6) ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-file-export"></i> Extract &amp; submit grades</h4>
                    @include('partials.dashboard-panel-cap', [
                        'shown' => 5,
                        'total' => $stats['grade_submissions_total'] ?? 0,
                        'href' => route('instructor.grade-submissions.index'),
                        'viewAllUrl' => route('instructor.grade-submissions.index'),
                        'viewAllLabel' => 'History',
                    ])
                </div>
                <div class="dash-quick-actions">
                    <a href="{{ route('instructor.grade-submissions.index') }}" class="btn btn-primary"><i class="fa-solid fa-file-export"></i> Extract grades</a>
                    <a href="{{ route('instructor.grade-submissions.index') }}" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> Preview export</a>
                    <a href="{{ route('instructor.grade-submissions.index') }}" class="btn btn-secondary"><i class="fa-solid fa-clock-rotate-left"></i> Submission history</a>
                </div>
                <div class="dash-outcome-row">
                    <span class="dash-outcome d-ok"><i class="fa-solid fa-layer-group"></i> {{ $stats['grade_submissions_total'] ?? 0 }} batches</span>
                    <span class="dash-outcome"><i class="fa-solid fa-paper-plane"></i> {{ $stats['grade_submissions_submitted'] ?? 0 }} submitted</span>
                    <span class="dash-outcome d-warn"><i class="fa-solid fa-triangle-exclamation"></i> {{ $stats['grade_submissions_incomplete'] ?? 0 }} incomplete</span>
                </div>
                <ul class="dash-list">
                    @forelse(collect($stats['grade_submissions_recent'] ?? [])->take(5) as $submission)
                        <li class="dash-list-item">
                            <span class="dash-list-icon {{ $submission->status === 'submitted' ? 'i-green' : ($submission->status === 'failed' ? 'i-rose' : 'i-amber') }}">
                                <i class="fa-solid {{ $submission->status === 'submitted' ? 'fa-circle-check' : ($submission->status === 'failed' ? 'fa-circle-exclamation' : 'fa-clock') }}"></i>
                            </span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">
                                    <a href="{{ route('instructor.grade-submissions.show', $submission) }}">
                                        {{ $submission->class?->code ?? 'Class' }} — {{ $submission->class?->course?->title ?? '' }}
                                    </a>
                                </p>
                                <p class="dash-list-sub">{{ $submission->graded_count }}/{{ $submission->student_count }} graded · {{ $submission->missing_count }} missing</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip {{ $submission->status === 'submitted' ? 'm-green' : ($submission->status === 'failed' ? 'm-rose' : 'm-amber') }}">{{ ucfirst($submission->status) }}</span>
                                <span class="dash-list-date">{{ $submission->created_at?->diffForHumans() }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-file-export"></i> No grade batches extracted yet — start from Extract grades.</li>
                    @endforelse
                </ul>
                <p style="margin:0;padding:0 14px 14px;font-size:.72rem;color:var(--dash-muted,#98a7c4);">
                    <i class="fa-solid fa-circle-info"></i> Submitting records an LMS snapshot lang — hindi nito binabago ang official final grade sa AA.
                </p>
            </section>

            {{-- ═══ REPORTS ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-file-lines"></i> Reports</h4>
                    <a href="{{ route('instructor.reports.index') }}" class="view-all">Open reports</a>
                </div>
                <div class="dash-quick-actions">
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'students', 'format' => 'csv']) }}" class="btn btn-secondary"><i class="fa-solid fa-users"></i> Assignment submissions</a>
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'grades', 'format' => 'csv']) }}" class="btn btn-secondary"><i class="fa-solid fa-chart-line"></i> Quiz performance</a>
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'performance', 'format' => 'csv']) }}" class="btn btn-secondary"><i class="fa-solid fa-poll"></i> Student progress</a>
                    <a href="{{ route('instructor.dashboard.export', ['type' => 'aa', 'format' => 'csv']) }}" class="btn btn-primary"><i class="fa-solid fa-file-export"></i> Export for AA</a>
                </div>
                <p style="margin:0;padding:0 14px 14px;font-size:.72rem;color:var(--dash-muted,#98a7c4);">
                    <i class="fa-solid fa-circle-info"></i> LMS scores lang — ang official final grade ay hawak ng AA.
                </p>
            </section>
        </div>

        <div class="dash-split-side">
            {{-- ═══ TO-DO LIST ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-list-check"></i> To-do list</h4>
                    @include('partials.dashboard-panel-cap', [
                        'countId' => 'upcoming-count',
                        'count' => $stats['upcoming_activities_total'] ?? ($stats['upcoming_activities'] ?? collect())->count(),
                        'shown' => 5,
                        'total' => $stats['upcoming_activities_total'] ?? ($stats['upcoming_activities'] ?? collect())->count(),
                        'href' => route('instructor.dashboard.activities'),
                        'viewAllUrl' => route('instructor.dashboard.activities'),
                        'viewAllLabel' => 'All activities',
                        'viewAllOnlyWhenCapped' => true,
                    ])
                </div>
                <div style="padding:14px">
                    @if(isset($stats['upcoming_activities']) && $stats['upcoming_activities']->isNotEmpty())
                        <div class="activity-list">
                            @foreach(collect($stats['upcoming_activities'])->take(5) as $activity)
                                <div class="activity-item">
                                    <i class="fa-solid {{ $activity['type'] === 'Assignment' ? 'fa-file-pen' : ($activity['type'] === 'Quiz' ? 'fa-circle-question' : ($activity['type'] === 'Exam' ? 'fa-graduation-cap' : 'fa-video')) }}" aria-hidden="true"></i>
                                    <div class="activity-copy"><strong>{{ $activity['title'] }}</strong><small>{{ $activity['type'] }} · {{ $activity['date'] }}</small></div>
                                    @if(!empty($activity['url']))<a href="{{ $activity['url'] }}">Open <i class="fa-solid fa-arrow-right"></i></a>@endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state"><i class="fa-solid fa-calendar-check"></i>No upcoming activities</div>
                    @endif
                </div>
            </section>

            {{-- ═══ ASSIGNMENTS AWAITING GRADING ═══ --}}
    {{-- ═══ TO-CHECK QUEUE ═══ --}}
    <section class="dash-panel">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-clipboard-check"></i> To check</h4>
            @include('partials.dashboard-panel-cap', [
                'countId' => 'to-check-count',
                'count' => $stats['to_check_count'] ?? 0,
                'shown' => 5,
                'total' => $stats['to_check_count'] ?? 0,
                'href' => route('instructor.dashboard.to-check'),
            ])
        </div>
        <ul class="dash-list">
            @forelse(collect($stats['to_check'] ?? [])->take(5) as $item)
                <li class="dash-list-item">
                    <span class="dash-list-icon {{ $item['kind'] === 'Quiz' ? 'i-violet' : 'i-amber' }}">
                        <i class="fa-solid {{ $item['kind'] === 'Quiz' ? 'fa-circle-question' : 'fa-file-pen' }}"></i>
                    </span>
                    <div class="dash-list-body">
                        <p class="dash-list-title">{{ $item['title'] }} <span style="opacity:.65;font-weight:400">· {{ $item['kind'] }}</span></p>
                        <p class="dash-list-sub">{{ $item['student'] }} · {{ $item['class_code'] }}</p>
                    </div>
                    <div class="dash-list-meta">
                        <span class="dash-list-date">Submitted {{ $item['submitted_at']?->diffForHumans() ?? 'recently' }}</span>
                        @if(!empty($item['url']))
                            <a href="{{ $item['url'] }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen-to-square"></i> Score</a>
                        @endif
                    </div>
                </li>
            @empty
                <li class="dash-list-empty"><i class="fa-solid fa-mug-hot"></i> Nothing to check — all scored!</li>
            @endforelse
        </ul>
    </section>

            {{-- ═══ LIVE ROOM STATUS (Canvas-style headcount + trend) ═══ --}}
            @php
                $rooms = collect($virtualRooms ?? []);
                $liveRoom = $rooms->firstWhere('is_live', true);
            @endphp
            @if($liveRoom)
                {{-- Widget styles travel with the widget, so a dashboard with no
                     live room does not ship its markup or CSS at all. --}}
                <style>
                    .dash-live-room{border:1px solid rgba(91,140,255,.28)}
                    .dash-live-room-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(220px,.7fr);gap:18px;padding:16px 18px 18px}
                    .dash-live-room-title{margin:0 0 10px;font-weight:800;color:var(--id-text)}
                    .dash-live-room-metrics{display:flex;align-items:baseline;gap:18px;flex-wrap:wrap}
                    .dash-live-room-count{font-size:1.7rem;font-weight:850;color:var(--id-text);line-height:1}
                    .dash-live-room-label{display:block;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--id-muted)}
                    .dash-live-room-trend{font-size:.78rem;font-weight:700;color:var(--id-cyan)}
                    .dash-live-room canvas{width:100%;height:64px;margin-top:12px;display:block}
                    .dash-live-room-note{margin:10px 0 0;font-size:.68rem;color:var(--id-faint)}
                    .dash-live-room-feed{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;max-height:170px;overflow:auto}
                    .dash-live-room-event{display:flex;align-items:center;gap:8px;font-size:.74rem;color:var(--id-muted);padding:5px 8px;border-radius:8px;background:var(--id-surface-2)}
                    .dash-live-room-event.is-join{color:var(--id-green)}
                    .dash-live-room-event.is-leave{color:var(--id-rose)}
                    .dash-live-room-event-name{font-weight:700;color:var(--id-text)}
                    .dash-live-count{display:inline-flex;align-items:center;gap:6px;margin-top:6px;padding:3px 9px;border-radius:999px;background:rgba(52,211,153,.12);color:var(--id-green);font-size:.7rem;font-weight:800}
                    @media(max-width:860px){.dash-live-room-grid{grid-template-columns:1fr}}
                </style>
                <section class="dash-panel dash-live-room"
                    data-live-room-url="{{ $liveRoom['live_count_url'] }}"
                    data-live-room-payload="{{ json_encode([
                        'history' => $liveRoom['timeline']['points'] ?? [],
                        'peak' => $liveRoom['timeline']['peak'] ?? 0,
                        'now' => $liveRoom['timeline']['now'] ?? 0,
                        'events' => $liveRoom['timeline']['events'] ?? [],
                    ]) }}">
                    <div class="dash-panel-head">
                        <h4><i class="fa-solid fa-video"></i> Live room status</h4>
                        <span class="panel-count">{{ $liveRoom['room']->class?->code ?? '' }}</span>
                    </div>
                    <div class="dash-live-room-grid">
                        <div>
                            <p class="dash-live-room-title">{{ $liveRoom['room']->title }}</p>
                            <div class="dash-live-room-metrics">
                                <div>
                                    <span class="dash-live-room-count" data-live-room-count>{{ $liveRoom['exact'] ? '' : '~' }}{{ $liveRoom['headcount'] }}</span>
                                    <span class="dash-live-room-label">Students in room</span>
                                </div>
                                <span class="dash-live-room-trend" data-live-room-trend>Peak {{ $liveRoom['timeline']['peak'] ?? 0 }} · now {{ $liveRoom['timeline']['now'] ?? 0 }}</span>
                            </div>
                            <canvas data-live-room-canvas height="64" aria-label="Headcount trend"></canvas>
                            <p class="dash-live-room-note">Headcounts and the trend come from LMS attendance records; LiveKit supplies the exact live roster.</p>
                        </div>
                        <ul class="dash-live-room-feed" data-live-room-feed>
                            @forelse(($liveRoom['timeline']['events'] ?? []) as $event)
                                <li class="dash-live-room-event is-{{ $event['type'] }}">
                                    <i class="fa-solid {{ $event['type'] === 'join' ? 'fa-right-to-bracket' : 'fa-right-from-bracket' }}"></i>
                                    <span class="dash-live-room-event-name">{{ $event['name'] }}</span>
                                    <span>{{ $event['type'] === 'join' ? 'joined' : 'left' }}</span>
                                </li>
                            @empty
                                <li class="dash-live-room-event">No joins recorded yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </section>
            @endif

            {{-- ═══ VIRTUAL CLASSES (today + upcoming) ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-video"></i> Virtual classes</h4>
                    @include('partials.dashboard-panel-cap', [
                        'count' => $rooms->count(),
                        'shown' => 4,
                        'total' => $rooms->count(),
                        'href' => route('instructor.virtual-classes.index'),
                        'viewAllUrl' => route('instructor.virtual-classes.index'),
                        'viewAllLabel' => 'All virtual classes',
                        'viewAllOnlyWhenCapped' => true,
                    ])
                </div>
                <ul class="dash-list">
                    @forelse($rooms->take(4) as $row)
                        @php $vc = $row['room']; @endphp
                        <li class="dash-list-item">
                            <span class="dash-list-icon i-violet"><i class="fa-solid fa-video"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">
                                    {{ $vc->title }}
                                    @if($row['is_live'])
                                        <span class="dash-meta-chip m-green">Live now</span>
                                    @endif
                                </p>
                                <p class="dash-list-sub">{{ $vc->class?->course?->title ?? '' }} · {{ $vc->class?->code ?? '' }}</p>
                                @if($row['is_live'])
                                    <span class="dash-live-count" data-live-count-url="{{ $row['live_count_url'] }}"
                                          data-live-participants="{{ json_encode($row['participants']) }}"
                                          data-viewer-id="{{ auth()->id() }}">
                                        <i class="fa-solid fa-users"></i>
                                        <span data-live-count-text>{{ $row['exact'] ? '' : '~' }}{{ $row['headcount'] }} in room</span>
                                    </span>
                                @endif
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-blue">{{ \Carbon\Carbon::parse($vc->meeting_date->format('Y-m-d').' '.$vc->start_time)->format('M j, g:i A') }}</span>
                                <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-top:6px">
                                    @if($row['is_live'])
                                        <a href="{{ route('instructor.classes.virtual_classes.room', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" class="dash-join-btn">
                                            <i class="fa-solid fa-video"></i> Enter
                                        </a>
                                        <form method="POST" action="{{ route('instructor.classes.virtual_classes.end', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" onsubmit="return confirm('End this class for everyone?');" style="display:inline">
                                            @csrf
                                            <button type="submit" class="dash-end"><i class="fa-solid fa-stop"></i> End</button>
                                        </form>
                                    @elseif($row['can_start'])
                                        <form method="POST" action="{{ route('instructor.classes.virtual_classes.start', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="dash-join-btn"><i class="fa-solid fa-play"></i> Start</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('instructor.classes.virtual_classes.show', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" class="dash-list-date">Details</a>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-video"></i> No upcoming virtual classes</li>
                    @endforelse
                </ul>
                <script>
                (function () {
                    const POLL_MS = 20000;

                    const esc = (value) => String(value == null ? '' : value)
                        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    function drawTrend(canvas, points) {
                        if (!canvas || !canvas.getContext || !points || !points.length) return;
                        const ctx = canvas.getContext('2d');
                        const width = canvas.width = canvas.clientWidth || 320;
                        const height = canvas.height;
                        const max = Math.max(1, ...points.map((p) => Number(p.count) || 0));
                        ctx.clearRect(0, 0, width, height);
                        ctx.beginPath();
                        ctx.lineWidth = 2;
                        ctx.strokeStyle = '#34d399';
                        points.forEach((point, index) => {
                            const x = points.length === 1 ? 0 : (index / (points.length - 1)) * width;
                            const y = height - ((Number(point.count) || 0) / max) * (height - 6) - 3;
                            index === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                        });
                        ctx.stroke();
                    }

                    function paintRoom(root, data) {
                        const count = root.querySelector('[data-live-room-count]');
                        if (count) count.textContent = (data.exact ? '' : '~') + (data.count ?? 0);

                        const trend = root.querySelector('[data-live-room-trend]');
                        if (trend) trend.textContent = 'Peak ' + (data.peak ?? 0) + ' · now ' + (data.now ?? 0);

                        drawTrend(root.querySelector('canvas[data-live-room-canvas]'), data.history || []);

                        const feed = root.querySelector('[data-live-room-feed]');
                        if (feed && Array.isArray(data.events)) {
                            feed.innerHTML = data.events.length
                                ? data.events.map((event) => '<li class="dash-live-room-event is-' + esc(event.type) + '">'
                                    + '<span class="dash-live-room-event-name">' + esc(event.name) + '</span>'
                                    + '<span>' + (event.type === 'join' ? 'joined' : 'left') + '</span></li>').join('')
                                : '<li class="dash-live-room-event">No joins recorded yet.</li>';
                        }
                    }

                    function paintChip(chip, data) {
                        const text = chip.querySelector('[data-live-count-text]');
                        if (text) text.textContent = (data.exact ? '' : '~') + (data.count ?? 0) + ' in room';
                    }

                    async function poll(url) {
                        const response = await fetch(url, {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });
                        if (!response.ok) throw new Error('poll failed');
                        return response.json();
                    }

                    document.querySelectorAll('[data-live-room-url]').forEach((root) => {
                        const payload = root.dataset.liveRoomPayload;
                        if (payload) {
                            try {
                                const initial = JSON.parse(payload);
                                const trend = root.querySelector('[data-live-room-trend]');
                                if (trend) trend.textContent = 'Peak ' + (initial.peak ?? 0) + ' · now ' + (initial.now ?? 0);
                                drawTrend(root.querySelector('canvas[data-live-room-canvas]'), initial.history || []);
                            } catch (_) { /* keep server-rendered values */ }
                        }

                        setInterval(async () => {
                            try { paintRoom(root, await poll(root.dataset.liveRoomUrl)); } catch (_) { /* offline */ }
                        }, POLL_MS);
                    });

                    document.querySelectorAll('[data-live-count-url]').forEach((chip) => {
                        setInterval(async () => {
                            try { paintChip(chip, await poll(chip.dataset.liveCountUrl)); } catch (_) { /* offline */ }
                        }, POLL_MS);
                    });
                })();
                </script>
            </section>

            {{-- ═══ RECENT NOTIFICATIONS ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-bullhorn"></i> Recent notifications</h4>
                    @include('partials.dashboard-panel-cap', [
                        'count' => $stats['total_announcements'] ?? 0,
                        'shown' => 3,
                        'total' => $stats['total_announcements'] ?? 0,
                        'href' => route('instructor.announcements.index'),
                    ])
                </div>
                <ul class="announce-feed">
                    @forelse(collect($stats['recent_announcements'] ?? [])->take(3) as $announcement)
                        <li class="announce-item">
                            <div class="announce-date">
                                <span class="ad-day">{{ $announcement->created_at?->format('j') }}</span>
                                <span class="ad-mon">{{ $announcement->created_at?->format('M') }}</span>
                            </div>
                            <div class="announce-body">
                                <p class="announce-title">{{ $announcement->title }}</p>
                                <p>{{ Str::limit($announcement->body ?? $announcement->content ?? '', 90) }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-bullhorn"></i> No announcements yet</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>

    {{-- ═══ ANALYTICS OVERVIEW ═══ --}}
    <section class="dash-analytics">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-chart-pie"></i> Performance Analytics</h4>
            <div class="analytics-controls">
                <select id="analyticsClass" class="analytics-select" title="Limit results to one class">
                    <option value="">All my classes</option>
                    @foreach ($stats['class_performance'] ?? [] as $perf)
                        <option value="{{ $perf['class']->id }}">
                            {{ $perf['class']->code }} — {{ $perf['class']->course?->title ?? $perf['class']->code }}
                        </option>
                    @endforeach
                </select>
                <select id="analyticsPeriod" class="analytics-select">
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="semester">This Semester</option>
                    <option value="year">This Year</option>
                </select>
                <select id="analyticsType" class="analytics-select">
                    <option value="enrollment">Enrollment</option>
                    <option value="performance" selected>Performance</option>
                    <option value="attendance">Attendance</option>
                    <option value="engagement">Engagement</option>
                </select>
                <button onclick="loadAnalytics(false)" class="analytics-btn"><i class="fa-solid fa-sync-alt"></i> Load</button>
            </div>
        </div>
        <div class="analytics-content">
            <div id="analytics-toast" class="analytics-toast"></div>
            <p class="analytics-hint" style="margin:0 0 12px;font-size:.72rem;color:var(--dash-muted,#98a7c4);">
                Pick a metric above and press <strong>Load</strong>. Only the selected card renders data.
            </p>
            @if(count($stats['completion_by_class'] ?? []))
                {{-- Wide enough for one readable row per class; the old slot was
                     inside the narrow class-performance column, which forced
                     Chart.js to rotate every label. --}}
                <div class="analytics-card" style="margin-bottom:var(--chart-gap)">
                    <div class="chart-card-head">
                        <div>
                            <h5><i class="fa-solid fa-chart-column"></i> Completion by class</h5>
                            <p class="chart-card-sub">Students completed vs. active per class · AA enrollment status</p>
                        </div>
                    </div>
                    <div id="completionChart" class="chart-container chart-tall" data-chart-label="Students completed versus active per class"></div>
                </div>
            @endif
            <div class="analytics-grid">
                <div class="analytics-card" data-analytics-type="performance">
                    <div class="chart-card-head">
                        <div>
                            <h5><i class="fa-solid fa-chart-line"></i> Grade Distribution</h5>
                            <p class="chart-card-sub">Released LMS scores only · A–F bands</p>
                        </div>
                    </div>
                    <div id="gradeDistributionChart" class="chart-container" data-chart-label="Grade distribution by letter band"></div>
                </div>
                <div class="analytics-card" data-analytics-type="enrollment">
                    <div class="chart-card-head">
                        <div>
                            <h5><i class="fa-solid fa-users"></i> Enrollment Trends</h5>
                            <p class="chart-card-sub">New enrollments in the selected period · running total</p>
                        </div>
                    </div>
                    <div id="enrollmentTrendsChart" class="chart-container" data-chart-label="Enrollment trend"></div>
                </div>
                <div class="analytics-card" data-analytics-type="attendance">
                    <div class="chart-card-head">
                        <div>
                            <h5><i class="fa-solid fa-clipboard-check"></i> Attendance Overview</h5>
                            <p class="chart-card-sub">Present + late over all recorded sessions</p>
                        </div>
                    </div>
                    <div id="attendanceOverviewChart" class="chart-container" data-chart-label="Attendance rate per class"></div>
                </div>
                <div class="analytics-card" data-analytics-type="engagement">
                    <div class="chart-card-head">
                        <div>
                            <h5><i class="fa-solid fa-comments"></i> Engagement Metrics</h5>
                            <p class="chart-card-sub">Announcements, virtual classes and participation</p>
                        </div>
                    </div>
                    <div id="engagementMetricsChart" class="chart-container" data-chart-label="Engagement metrics"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ ENHANCED DASHBOARD FUNCTIONALITY ═══ --}}
    @vite('resources/js/charts.js')
    <script>
        // Palette-safe chart text colours. These are the exact values the
        // --dash-* tokens already hold for each theme (dark default / light
        // mode), so charts stay readable after the theme flip without
        // introducing any new colour.
        const isLightTheme = document.body.classList.contains('light-mode');
        const CHART_TICK = isLightTheme ? '#5f708c' : '#98a7c4';
        const CHART_TEXT = isLightTheme ? '#16233c' : '#c7d4ec';
        const CHART_GRID = isLightTheme ? 'rgba(31,52,88,.12)' : 'rgba(153,174,214,.12)';
        const CHART_SLICE_BORDER = isLightTheme ? '#ffffff' : '#151c2c';
        function chartPalette() { return LMSCharts.palette(); }

        // Dropdown functionality
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const button = dropdown.querySelector('.dash-action-btn');
            const content = dropdown.querySelector('.dropdown-content');

            button.addEventListener('click', (e) => {
                e.stopPropagation();
                content.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', () => {
                content.classList.remove('show');
            });
        });

        // Completion graph — ilang students na nakatapos (vs. active) per class.
        // Server-rendered data; no extra request needed to draw it.
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('completionChart');
            if (!container || typeof Chart === 'undefined') return;

            const completionData = @json($stats['completion_by_class'] ?? []);
            if (!completionData.length) return;

            const palette = chartPalette();
            const style = getComputedStyle(document.querySelector('.main:has(.dash-scope) .content') || document.body);
            const axisColor = style.getPropertyValue('--id-muted').trim() || '#9aa9c7';
            const gridColor = style.getPropertyValue('--id-line').trim() || 'rgba(140,163,210,.14)';

            container.innerHTML = '<canvas id="completionChartCanvas"></canvas>';
            new Chart(document.getElementById('completionChartCanvas'), {
                type: 'bar',
                data: {
                    labels: completionData.map(d => d.label),
                    datasets: [
                        {
                            label: 'Completed',
                            data: completionData.map(d => d.completed),
                            backgroundColor: LMSCharts.alpha(palette.green, .85),
                            borderRadius: 6,
                            maxBarThickness: 18,
                        },
                        {
                            label: 'Active',
                            data: completionData.map(d => d.active),
                            backgroundColor: LMSCharts.alpha(palette.brand, .55),
                            borderRadius: 6,
                            maxBarThickness: 18,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { color: axisColor, precision: 0 },
                            grid: { color: gridColor },
                            border: { display: false },
                        },
                        y: {
                            stacked: true,
                            ticks: { color: axisColor, autoSkip: false },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                // The header already says what the chart is; the
                                // tooltip adds the full course name and the
                                // class size the stacked bars add up to.
                                title: (items) => completionData[items[0].dataIndex].course,
                                label: (c) => ` ${c.dataset.label}: ${c.parsed.x}`,
                                footer: (items) => `${completionData[items[0].dataIndex].total} active or completed`,
                            },
                        },
                    },
                },
            });
        });

        // Real-time dashboard refresh
        async function refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            try {
                // Clear cache and reload
                await fetch('{{ route('instructor.dashboard.clear-cache') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                    },
                });

                // Reload the page
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                refreshBtn.innerHTML = originalContent;
                refreshBtn.disabled = false;
                alert('Error refreshing dashboard. Please try again.');
            }
        }

        // Auto-refresh stats every 30 seconds — live update ng tiles nang walang page reload
        setInterval(async () => {
            try {
                const response = await fetch('{{ route('instructor.dashboard.real-time-stats') }}');
                if (!response.ok) throw new Error('Bad status: ' + response.status);
                const data = await response.json();

                if (data.success) {
                    const stats = data.data;
                    // Update stat tiles in-place
                    const pendingEl = document.getElementById('stat-pending-value');
                    if (pendingEl && stats.pending_submissions !== undefined) {
                        pendingEl.textContent = stats.pending_submissions;
                        const chip = document.querySelector('#stat-pending .trend-chip');
                        if (chip) {
                            const needsAction = stats.pending_submissions > 0;
                            chip.textContent = needsAction ? 'Action needed' : 'All clear';
                            chip.className = 'trend-chip ' + (needsAction ? 'new' : 'up');
                        }
                    }
                    const atRiskEl = document.getElementById('stat-atrisk-value');
                    if (atRiskEl && stats.at_risk_count !== undefined) {
                        atRiskEl.textContent = stats.at_risk_count;
                    }
                    const toCheckEl = document.getElementById('to-check-count');
                    if (toCheckEl && stats.to_check_count !== undefined) {
                        toCheckEl.textContent = stats.to_check_count;
                    }
                    const upcomingCountEl = document.getElementById('upcoming-count');
                    if (upcomingCountEl && stats.upcoming_activities_total !== undefined) {
                        upcomingCountEl.textContent = stats.upcoming_activities_total;
                    }
                }
            } catch (error) {
                console.error('Error fetching real-time stats:', error);
            }
        }, 30000);

        // Search functionality
        const searchInput = document.getElementById('globalSearch');
        const searchType = document.getElementById('searchType');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            // Show loading state
            searchResults.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.classList.remove('hidden');

            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        async function performSearch(query) {
            const type = searchType.value;

            try {
                const response = await fetch(`{{ route('instructor.dashboard.search') }}?query=${encodeURIComponent(query)}&type=${type}`);
                const data = await response.json();

                if (data.success) {
                    if (data.data.length > 0) {
                        displaySearchResults(data.data, type);
                    } else {
                        searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                        searchResults.classList.remove('hidden');
                    }
                } else {
                    throw new Error(data.error || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-no-results">Error: ' + error.message + '</div>';
                searchResults.classList.remove('hidden');
            }
        }

        function searchResultTypeBadge(result, type) {
            // When searching 'all', results are merged — add a type badge
            const typeMap = {
                'students': { label: 'Student', icon: 'fa-user-graduate', cls: 'type-student' },
                'courses': { label: 'Course', icon: 'fa-book', cls: 'type-course' },
                'classes': { label: 'Class', icon: 'fa-school', cls: 'type-class' },
                'assignments': { label: 'Assignment', icon: 'fa-tasks', cls: 'type-assignment' },
                'quizzes': { label: 'Quiz', icon: 'fa-question-circle', cls: 'type-quiz' }
            };
            if (type !== 'all') return '';
            // Infer type from fields
            let inferred = 'courses';
            if (result.email) inferred = 'students';
            else if (result.class_code && result.due_date) inferred = 'assignments';
            else if (result.class_code && result.availability_from) inferred = 'quizzes';
            else if (result.course_title && result.code) inferred = 'classes';
            const t = typeMap[inferred];
            return `<span class="type-badge ${t.cls}"><i class="fa-solid ${t.icon}"></i> ${t.label}</span>`;
        }

        function displaySearchResults(results, type) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                searchResults.classList.remove('hidden');
                return;
            }

            let html = '<div class="search-results-list">';

            results.forEach(result => {
                // Determine navigation target per result type
                let href = '#';
                if (type === 'students' || result.email) {
                    href = result.id ? `{{ url('instructor/enrollments') }}` : '#';
                } else if (type === 'courses' || (result.title && !result.due_date && !result.availability_from && !result.class_code)) {
                    href = result.id ? `{{ url('instructor/courses') }}/${result.id}` : '#';
                } else if (type === 'classes' || (result.code && result.course_title)) {
                    href = result.id ? `{{ url('instructor/classes') }}/${result.id}` : '#';
                } else if (type === 'assignments' || type === 'quizzes') {
                    href = result.id ? `{{ url('instructor/classes') }}` : '#';
                }

                html += `
                    <a href="${href}" class="search-result-item">
                        <div class="search-result-title">${result.name || result.title || result.code}
                            ${searchResultTypeBadge(result, type)}
                        </div>
                        <div class="search-result-details">
                            ${result.email ? `<span>${result.email}</span>` : ''}
                            ${result.course_title ? `<span>${result.course_title}</span>` : ''}
                            ${result.class_code ? `<span>${result.class_code}</span>` : ''}
                            ${result.status ? `<span class="status-badge ${result.status}">${result.status}</span>` : ''}
                            ${result.due_date ? `<span>Due ${result.due_date}</span>` : ''}
                            ${result.availability_from ? `<span>Opens ${result.availability_from}</span>` : ''}
                        </div>
                    </a>
                `;
            });

            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');
        }

        // Close search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                searchResults.classList.add('hidden');
            }
        });

        // Analytics functionality
        let chartInstances = {};

        function destroyChart(id) {
            if (chartInstances[id]) {
                chartInstances[id].destroy();
                delete chartInstances[id];
            }
        }

        // Each analytics card maps to the dropdown option that renders it.
        const ANALYTICS_CARD = {
            performance: 'gradeDistributionChart',
            enrollment: 'enrollmentTrendsChart',
            attendance: 'attendanceOverviewChart',
            engagement: 'engagementMetricsChart',
        };

        const ANALYTICS_LABEL = {
            performance: 'Grade Distribution',
            enrollment: 'Enrollment Trends',
            attendance: 'Attendance Overview',
            engagement: 'Engagement Metrics',
        };

        function idlePlaceholder(cardId, message, icon) {
            const container = document.getElementById(cardId);
            if (!container) return;

            destroyChart(cardId);

            // The grade summary lives beside the fixed-height chart container,
            // not inside it. Clear it when the card switches to an idle state.
            container.parentElement
                ?.querySelectorAll('.average-grade')
                .forEach((summary) => summary.remove());

            container.innerHTML = `
                <div class="chart-placeholder">
                    <i class="fa-solid ${icon || 'fa-chart-simple'}"></i>
                    <p>${message}</p>
                </div>
            `;
        }

        /**
         * Only the selected metric renders. The rest show an honest idle state
         * instead of a permanent "Loading analytics…" which read as no data.
         */
        function resetAnalyticsCards(activeType) {
            Object.entries(ANALYTICS_CARD).forEach(([type, cardId]) => {
                if (type === activeType) return;

                idlePlaceholder(cardId, 'Not selected — pick this metric above and press Load.');
            });
        }

        async function loadAnalytics(silent = false) {
            const period = document.getElementById('analyticsPeriod').value;
            const type = document.getElementById('analyticsType').value;
            const classId = document.getElementById('analyticsClass').value;
            const button = document.querySelector('.analytics-btn');
            const cardId = ANALYTICS_CARD[type];

            // Show loading state
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            button.disabled = true;

            if (cardId) {
                idlePlaceholder(cardId, 'Loading analytics…', 'fa-spinner fa-spin');
            }

            try {
                // Scope to a single class when one is chosen; otherwise every
                // class the instructor teaches is averaged together.
                const params = new URLSearchParams({ period, type });
                if (classId) params.set('class_id', classId);

                const response = await fetch(`{{ route('instructor.dashboard.analytics') }}?${params.toString()}`);
                const data = await response.json();

                if (data.success) {
                    displayAnalytics(data.data, type);
                    resetAnalyticsCards(type);

                    const toast = document.getElementById('analytics-toast');
                    if (toast && !silent) {
                        toast.textContent = `${ANALYTICS_LABEL[type] || type} loaded for ${period === 'week' ? 'this week' : period === 'month' ? 'this month' : period === 'semester' ? 'this semester' : 'this year'}`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                } else {
                    throw new Error(data.error || 'Failed to load analytics');
                }
            } catch (error) {
                console.error('Analytics error:', error);

                if (cardId) {
                    idlePlaceholder(cardId, 'Could not load this metric. Press Load to retry.', 'fa-triangle-exclamation');
                }

                if (!silent) {
                    if (typeof LMS !== 'undefined' && LMS.toast) {
                        LMS.toast('Error loading analytics: ' + error.message, 'error');
                    } else {
                        alert('Error loading analytics: ' + error.message);
                    }
                }
            } finally {
                // Reset button state
                button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load';
                button.disabled = false;
            }
        }

        function displayAnalytics(analytics, type) {
            switch (type) {
                case 'enrollment':
                    displayEnrollmentAnalytics(analytics);
                    break;
                case 'performance':
                    displayPerformanceAnalytics(analytics);
                    break;
                case 'attendance':
                    displayAttendanceAnalytics(analytics);
                    break;
                case 'engagement':
                    displayEngagementAnalytics(analytics);
                    break;
            }
        }

        function displayEnrollmentAnalytics(analytics) {
            const container = document.getElementById('enrollmentTrendsChart');
            destroyChart('enrollmentTrendsChart');

            const trend = analytics.enrollment_trend || [];
            const palette = chartPalette();

            if (!trend.length) {
                if (window.LMSCharts) {
                    LMSCharts.empty(container, {
                        message: 'No enrollments in this period',
                        hint: 'New AA-synced enrollments will appear here.',
                        icon: 'fa-user-plus',
                    });
                } else {
                    container.innerHTML = '<div class="chart-placeholder"><i class="fa-solid fa-user-plus"></i><p>No enrollments in this period</p></div>';
                }
                return;
            }

            container.innerHTML = '<canvas id="enrollmentChartCanvas"></canvas>';
            const ctx = document.getElementById('enrollmentChartCanvas');

            chartInstances['enrollmentTrendsChart'] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [{
                        label: 'Running total',
                        data: trend.map(t => t.cumulative),
                        fill: true,
                        borderColor: palette.cyan,
                        backgroundColor: LMSCharts.alpha(palette.cyan, 0.15),
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: palette.cyan
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const row = trend[context.dataIndex];
                                    return row ? ` ${row.count} new · ${context.parsed.y} total` : '';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: CHART_TICK },
                            grid: { color: CHART_GRID }
                        },
                        x: { grid: { display: false }, ticks: { color: CHART_TICK } }
                    }
                }
            });

            // Summary overlay
            const summary = document.createElement('div');
            summary.className = 'analytics-summary';
            summary.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Active enrollments</span>
                    <span class="summary-value">${analytics.total_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completed</span>
                    <span class="summary-value">${analytics.completed_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Avg. live progress</span>
                    <span class="summary-value">${analytics.completion_rate.toFixed(1)}%</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">At-risk students</span>
                    <span class="summary-value warning">${analytics.at_risk_students}</span>
                </div>
            `;
            container.appendChild(summary);
        }

        function displayPerformanceAnalytics(analytics) {
            const container = document.getElementById('gradeDistributionChart');
            destroyChart('gradeDistributionChart');
            const distribution = analytics.grade_distribution;
            const palette = chartPalette();

            // Doughnut chart para sa grade distribution
            container.innerHTML = '<canvas id="gradeChartCanvas"></canvas>';
            const ctx = document.getElementById('gradeChartCanvas');

            const buckets = ['A', 'B', 'C', 'D', 'F'];
            const ranges = ['A (90-100)', 'B (80-89)', 'C (70-79)', 'D (60-69)', 'F (0-59)'];
            const bucketTotal = buckets.reduce((sum, k) => sum + (Number(distribution[k]) || 0), 0);

            chartInstances['gradeDistributionChart'] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    // Show the actual headcount in each band, not just the range.
                    labels: buckets.map((k, i) => `${ranges[i]} — ${Number(distribution[k]) || 0}`),
                    datasets: [{
                        data: buckets.map(k => Number(distribution[k]) || 0),
                        backgroundColor: [palette.green, palette.cyan, palette.amber, palette.orange, palette.rose],
                        borderColor: CHART_SLICE_BORDER,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: CHART_TEXT, boxWidth: 12, padding: 12 }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const pct = bucketTotal > 0 ? (context.parsed / bucketTotal * 100).toFixed(1) : 0;
                                    return ` ${context.parsed} student${context.parsed === 1 ? '' : 's'} (${pct}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Average grade line. Keep it outside the fixed-height canvas
            // container so the chart and summary are not clipped by the card.
            container.parentElement
                ?.querySelectorAll('.average-grade')
                .forEach((summary) => summary.remove());

            const avgWrap = document.createElement('div');
            avgWrap.className = 'average-grade';

            const gradedCount = Number(analytics.graded_enrollments) || 0;

            avgWrap.innerHTML = gradedCount > 0
                ? `
                    <span class="avg-label"><i class="fa-solid fa-chart-simple"></i> Class Average: ${Number(analytics.average_grade || 0).toFixed(1)}%</span>
                    <span class="avg-sub">Across ${gradedCount} graded student${gradedCount === 1 ? '' : 's'}</span>
                `
                : `
                    <span class="avg-label"><i class="fa-solid fa-circle-info"></i> No graded results yet</span>
                    <span class="avg-sub">Release and grade items to build the distribution</span>
                `;

            container.parentElement?.appendChild(avgWrap);
        }

        function displayAttendanceAnalytics(analytics) {
            const container = document.getElementById('attendanceOverviewChart');
            destroyChart('attendanceOverviewChart');
            const palette = chartPalette();

            const byClass = analytics.attendance_by_class || [];
            container.innerHTML = '<canvas id="attendanceChartCanvas"></canvas>';
            const ctx = document.getElementById('attendanceChartCanvas');

            chartInstances['attendanceOverviewChart'] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: byClass.length ? byClass.map(c => c.class_code) : ['No data'],
                    datasets: [{
                        label: 'Attendance rate (present + late)',
                        data: byClass.length ? byClass.map(c => c.attendance_rate.toFixed(1)) : [0],
                        backgroundColor: LMSCharts.alpha(palette.green, 0.6),
                        borderColor: palette.green,
                        borderWidth: 1,
                        borderRadius: 6,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const cls = byClass[context.dataIndex];
                                    return cls ? ` ${cls.attendance_rate.toFixed(1)}% · ${cls.present} present, ${cls.late} late, ${cls.absent} absent, ${cls.excused} excused` : '';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            grid: { color: CHART_GRID },
                            ticks: { callback: (v) => v + '%', color: CHART_TICK }
                        },
                        y: { grid: { display: false }, ticks: { color: CHART_TEXT } }
                    }
                }
            });

            // Average attendance summary
            const summary = document.createElement('div');
            summary.className = 'attendance-summary';
            summary.innerHTML = `
                <div class="summary-item">
                    <span class="summary-label">Average attendance (present + late)</span>
                    <span class="summary-value">${analytics.average_attendance_rate.toFixed(1)}%</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Classes Tracked</span>
                    <span class="summary-value">${analytics.total_classes}</span>
                </div>
            `;
            container.appendChild(summary);
        }

        function displayEngagementAnalytics(analytics) {
            const container = document.getElementById('engagementMetricsChart');
            container.innerHTML = `
                <div class="engagement-metrics">
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-bullhorn"></i></span>
                        <span class="metric-label">Announcements</span>
                        <span class="metric-value">${analytics.total_announcements}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-video"></i></span>
                        <span class="metric-label">Virtual Classes</span>
                        <span class="metric-value">${analytics.virtual_classes_held}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-icon"><i class="fa-solid fa-users"></i></span>
                        <span class="metric-label">Participation Rate</span>
                            <span class="metric-value">${analytics.student_participation_rate.toFixed(1)}%</span>
                    </div>
                </div>
            `;
        }

        // Auto-load the selected metric on page load. Reuses loadAnalytics() so the
// idle state is applied to the other cards too — the previous inline copy of
        // this logic skipped resetAnalyticsCards(), which left three cards stuck
        // on "Loading analytics…" forever.
        document.addEventListener('DOMContentLoaded', function() {
            const selectedType = document.getElementById('analyticsType').value;

            // Paint the idle state immediately so nothing looks like it is
            // loading before the request comes back.
            resetAnalyticsCards(selectedType);

            if (typeof loadAnalytics === 'function') {
                loadAnalytics(true);
            }
        });

        // Switching metric should refresh without an extra click.
        document.getElementById('analyticsType')?.addEventListener('change', function() {
            resetAnalyticsCards(this.value);
        });
    </script>

    {{-- ═══ DESIGN ENHANCEMENT: scoped styles (instructor dashboard only) ═══ --}}
    <style>
        /* The hero is rendered by x-user-page-header (`.user-hero`), already
           fully styled by the shared user-ui-system stylesheet; the old
           .dash-hero / .live-dot rules targeted earlier markup and were dead
           on this page. */
        .dash-scope .dash-action{
            display:inline-flex;align-items:center;gap:8px;padding:10px 15px;border-radius:11px;
            border:1px solid rgba(255,255,255,.2);color:#fff;background:rgba(255,255,255,.08);
            font-size:.78rem;font-weight:700;text-decoration:none;white-space:nowrap;backdrop-filter:blur(8px);
            transition:background .18s,transform .18s,box-shadow .18s,border-color .18s;
        }
        .dash-scope .dash-action:hover{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.34);transform:translateY(-2px);box-shadow:0 12px 26px rgba(0,0,0,.3)}

        /* ── Search ───────────────────────────────────────── */
        .dash-scope .dash-search{margin:0}
        .dash-scope .search-container{position:relative;max-width:680px;margin:0 auto}
        .dash-scope .search-input-wrapper{
            display:flex;align-items:center;padding:13px 16px;border-radius:14px;
            background:var(--id-surface);border:1px solid var(--id-line-strong);
            box-shadow:var(--id-shadow);transition:border-color .2s,box-shadow .2s;
        }
        .dash-scope .search-input-wrapper:focus-within{border-color:rgba(91,140,255,.55);box-shadow:0 0 0 4px rgba(91,140,255,.14)}
        .dash-scope .search-icon{color:var(--id-faint);margin-right:12px}
        .dash-scope .search-input{flex:1;border:none;outline:none;font-size:15px;padding:6px 0;background:transparent;color:var(--id-text)}
        .dash-scope .search-input::placeholder{color:var(--id-faint)}
        .dash-scope .search-type{
            border:1px solid var(--id-line-strong);background:var(--id-surface-3);color:var(--id-text);
            padding:7px 11px;border-radius:9px;margin-left:12px;cursor:pointer;font-size:13px;
        }
        .dash-scope .search-results{
            position:absolute;top:100%;left:0;right:0;margin-top:8px;z-index:100;max-height:400px;overflow-y:auto;
            background:var(--id-surface-2);border:1px solid var(--id-line-strong);border-radius:14px;box-shadow:var(--id-shadow-lg);
        }
        .dash-scope .search-results.hidden{display:none}
        .dash-scope .search-results-list{padding:6px 0}
        .dash-scope .search-result-item{display:block;padding:12px 16px;border-bottom:1px solid var(--id-line);text-decoration:none;color:inherit;transition:background .18s}
        .dash-scope .search-result-item:hover{background:rgba(91,140,255,.09)}
        .dash-scope .search-result-title{font-weight:650;color:var(--id-text);margin-bottom:4px}
        .dash-scope .search-result-details{font-size:13px;color:var(--id-muted);display:flex;gap:12px;flex-wrap:wrap}
        .dash-scope .search-no-results,.dash-scope .search-loading{padding:20px;text-align:center;color:var(--id-muted)}
        .dash-scope .search-loading i{margin-right:8px}
        .dash-scope .type-badge{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:750;letter-spacing:.03em;margin-left:8px;vertical-align:middle}
        .dash-scope .type-badge.type-student{background:rgba(52,211,153,.13);color:var(--dash-status-active)}
        .dash-scope .type-badge.type-course{background:rgba(91,140,255,.15);color:var(--dash-status-info)}
        .dash-scope .type-badge.type-class{background:rgba(92,201,245,.13);color:var(--dash-cyan)}
        .dash-scope .type-badge.type-assignment{background:rgba(251,191,36,.13);color:var(--dash-status-pending)}
        .dash-scope .type-badge.type-quiz{background:rgba(167,139,250,.15);color:var(--dash-accent)}
        .dash-scope .status-badge{padding:2px 8px;border-radius:12px;font-size:12px;font-weight:500}
        .dash-scope .status-badge.published{background:rgba(52,211,153,.13);color:var(--dash-status-active)}
        .dash-scope .status-badge.draft{background:rgba(251,191,36,.13);color:var(--dash-status-pending)}
        .dash-scope .status-badge.active{background:rgba(91,140,255,.15);color:var(--dash-status-info)}

        /* Stat tiles are rendered by x-user-stat-card (`.user-stat-card`),
           already fully styled by user-ui-system.css; the old
           .dash-stat / .trend-chip rules targeted earlier markup and were
           dead on this page. */

        /* ── Panels & lists ───────────────────────────────── */
        .dash-scope .dash-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:18px;margin-bottom:18px}
        .dash-scope .dash-panel{overflow:hidden;background:var(--id-surface);border:1px solid var(--id-line);border-radius:16px;box-shadow:var(--id-shadow)}
        .dash-scope .dash-panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:15px 20px;border-bottom:1px solid var(--id-line)}
        .dash-scope .dash-panel-head h4{margin:0;color:var(--id-text);font-size:.78rem;font-weight:800;letter-spacing:.09em;text-transform:uppercase;display:flex;align-items:center;gap:9px}
        .dash-scope .dash-panel-head h4 i{color:var(--id-brand)}
        .dash-scope .dash-panel-head .panel-count{min-width:26px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:rgba(91,140,255,.14);color:var(--dash-accent);font-size:.66rem;font-weight:800;padding:0 8px}

        .dash-scope .dash-list-item{display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .dash-list-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .dash-list-item+.dash-list-item{border-top:1px solid var(--id-line)}
        .dash-scope .dash-list-icon{display:grid;place-items:center;width:37px;height:37px;flex:none;border-radius:11px;font-size:.85rem;background:rgba(91,140,255,.13);color:var(--id-brand)}
        .dash-scope .dash-list-icon.i-violet{background:rgba(167,139,250,.14);color:var(--dash-accent)}
        .dash-scope .dash-list-icon.i-green{background:rgba(52,211,153,.13);color:var(--dash-status-active)}
        .dash-scope .dash-list-icon.i-rose{background:rgba(251,113,133,.13);color:var(--dash-status-danger)}
        .dash-scope .dash-list-icon.i-amber{background:rgba(251,191,36,.13);color:var(--dash-status-pending)}
        .dash-scope .dash-meta-chip{padding:3px 9px;border-radius:999px;font-size:.64rem;font-weight:800;letter-spacing:.03em;white-space:nowrap}
        .dash-scope .dash-meta-chip.m-green{color:var(--dash-status-active);background:rgba(52,211,153,.13)}
        .dash-scope .dash-meta-chip.m-amber{color:var(--dash-status-pending);background:rgba(251,191,36,.13)}
        .dash-scope .dash-meta-chip.m-rose{color:var(--dash-status-danger);background:rgba(251,113,133,.13)}
        .dash-scope .dash-meta-chip.m-blue{color:var(--dash-status-info);background:rgba(91,140,255,.14)}
        .dash-scope .dash-meta-chip.m-gray{color:var(--dash-status-inactive);background:rgba(148,163,184,.13)}
        .dash-scope .dash-list-empty{padding:26px 18px;text-align:center;color:var(--id-muted);font-size:.82rem}
        .dash-scope .dash-list-empty i{margin-right:8px;opacity:.7}

        /* ── Class performance ────────────────────────────── */
        .dash-scope .perf-item{padding:12px 4px}
        .dash-scope .perf-item+.perf-item{border-top:1px solid var(--id-line)}
        .dash-scope .perf-name{color:var(--id-text);font-size:.83rem;font-weight:700}
        .dash-scope .perf-score{color:var(--dash-accent);font-size:.74rem;font-weight:800}
        .dash-scope .perf-bar{height:7px;border-radius:999px;background:var(--id-surface-3);overflow:hidden;margin-top:8px}
        .dash-scope .perf-bar>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#1a3fb5,#5b8cff,#5cc9f5);transition:width .6s ease}
        .dash-scope .perf-meta{color:var(--id-muted);font-size:.66rem}

        /* ── Announcements ────────────────────────────────── */
        .dash-scope .announce-item{display:flex;gap:13px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .announce-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .announce-item+.announce-item{border-top:1px solid var(--id-line)}
        .dash-scope .announce-date{flex:none;display:flex;flex-direction:column;align-items:center;justify-content:center;width:48px;height:48px;border-radius:13px;background:linear-gradient(150deg,rgba(91,140,255,.2),rgba(92,201,245,.1));border:1px solid rgba(91,140,255,.25);color:var(--dash-accent)}
        .dash-scope .announce-date .ad-day{color:var(--id-text);font-size:1.05rem;font-weight:800;line-height:1}
        .dash-scope .announce-date .ad-mon{font-size:.58rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-top:3px}
        .dash-scope .announce-title{color:var(--id-text)}

        /* Analytics cards, charts and KPI tiles come from resources/css/charts.css. */
        .dash-scope .attendance-bar{background:var(--id-surface-3)}
        .dash-scope .grade-progress{background:var(--id-surface-3)}

        /* ── Dropdown ─────────────────────────────────────── */
        .dash-scope .dropdown{position:relative;display:inline-block}
        .dash-scope .dash-action-btn{background:none;border:none;cursor:pointer;font-size:inherit;color:inherit}
        .dash-scope .dropdown-content{display:none;position:absolute;right:0;min-width:210px;margin-top:8px;z-index:50;overflow:hidden;background:var(--id-surface-2);border:1px solid var(--id-line-strong);border-radius:12px;box-shadow:var(--id-shadow-lg)}
        .dash-scope .dropdown-content.show{display:block}
        .dash-scope .dropdown-item{display:block;padding:12px 16px;text-decoration:none;color:var(--id-text);font-size:.84rem;transition:background .18s}
        .dash-scope .dropdown-item:hover{background:rgba(91,140,255,.1)}
        .dash-scope .dropdown-item i{margin-right:8px;color:var(--id-muted)}

        /* ── Light mode / responsive ──────────────────────── */
        /* The light-mode / responsive tweaks for the shared .user-hero live in
           user-ui-system.css — no local overrides needed. */
        @media (max-width:640px){
            .dash-scope .analytics-grid{grid-template-columns:1fr}
            .dash-scope .analytics-summary,.dash-scope .attendance-summary{grid-template-columns:1fr 1fr}
            .dash-scope .dash-analytics{padding:16px}
        }
    </style>
    </div>
</div>
@endsection

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
        --id-shadow:0 8px 24px rgba(24,44,84,.08);
        --id-shadow-lg:0 16px 42px rgba(24,44,84,.14);
    }
    .main:has(.dash-scope) .content{
        background:radial-gradient(1000px 420px at 85% -8%,rgba(91,140,255,.10),transparent 55%),
                   radial-gradient(700px 380px at -10% 12%,rgba(92,201,245,.07),transparent 50%),
                   var(--id-bg);
    }
</style>
<div class="dash-scope" data-viewer-id="{{ auth()->id() }}">
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
                <span class="panel-count">{{ count($stats['class_performance'] ?? []) }}</span>
            </div>
            <ul class="perf-list">
                @forelse(($stats['class_performance'] ?? []) as $perf)
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
            @if(count($stats['completion_by_class'] ?? []))
                {{-- Completion graph: ilang students na nakatapos per class (vs. active). --}}
                <div style="padding:0 14px 14px">
                    <div id="completionChart" class="chart-container" style="height:230px"></div>
                </div>
            @endif
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-triangle-exclamation"></i> Needs attention</h4>
                <span class="panel-count">{{ $stats['at_risk_count'] ?? 0 }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($stats['at_risk_students'] ?? []) as $enrollment)
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
                    <a href="{{ route('instructor.courses.index') }}" class="view-all">View all</a>
                </div>
                <div class="course-card-grid">
                    @forelse(($stats['class_performance'] ?? []) as $perf)
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
                    <span class="panel-count">{{ ($stats['recent_enrollments'] ?? collect())->count() }}</span>
                </div>
                <ul class="dash-list">
                    @forelse(($stats['recent_enrollments'] ?? []) as $enrollment)
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
                    <a href="{{ route('instructor.grade-submissions.index') }}" class="view-all">History</a>
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
                    @forelse(($stats['grade_submissions_recent'] ?? []) as $submission)
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
            @php
                // A live session is the most important thing on this page, so its
                // status widget leads the sidebar. Everything here comes from the
                // same queries the live rows below already run.
                $liveRoomSessions = collect($stats['upcoming_virtual_classes'] ?? [])
                    ->filter(function ($virtualClass) {
                        return $virtualClass->isLiveNow();
                    })
                    ->values();
            @endphp

            {{-- ═══ LIVE ROOM STATUS ═══ --}}
            @if($liveRoomSessions->isNotEmpty())
                <section class="dash-panel">
                    <div class="dash-panel-head">
                        <h4><i class="fa-solid fa-tower-broadcast"></i> Live room status</h4>
                        <span class="panel-count">{{ $liveRoomSessions->count() }}</span>
                    </div>

                    @foreach($liveRoomSessions as $vc)
                        @php $room = $vc->live_room ?? []; $roomEvents = $room['events'] ?? []; @endphp
                        <div class="dash-live-room"
                             data-live-room-url="{{ route('instructor.classes.virtual_classes.live-count', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}"
                             data-live-room-payload="{{ json_encode($room) }}">
                            <div class="dash-live-room-head">
                                <div>
                                    <p class="dash-live-room-title">{{ $vc->title }}</p>
                                    <p class="dash-live-room-sub">
                                        {{ $vc->class?->code ?? 'Class' }}
                                        @if($vc->startsAt()) · started {{ $vc->startsAt()->diffForHumans(short: true) }}@endif
                                    </p>
                                </div>
                                <span class="dash-live-room-count" title="{{ $vc->live_count_is_exact ? 'Participants connected to the LiveKit room' : 'Estimated from attendance — LiveKit did not respond' }}">
                                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                                    <b data-live-room-count>{{ $vc->live_count_is_exact ? '' : '~' }}{{ (int) $vc->live_participant_count }}</b>
                                    <span>in room</span>
                                </span>
                            </div>

                            <div class="dash-live-room-spark">
                                <p class="dash-live-room-spark-label">Students in room</p>
                                <canvas data-live-room-canvas height="46" aria-hidden="true"></canvas>
                                <p class="dash-live-room-trend" data-live-room-trend>Peak {{ (int) ($room['peak'] ?? 0) }} · now {{ (int) ($room['now'] ?? 0) }}</p>
                            </div>

                            <ul class="dash-live-room-events" data-live-room-events>
                                @forelse($roomEvents as $event)
                                    <li class="dash-live-room-event is-{{ $event['type'] }}">
                                        <i class="fa-solid {{ $event['type'] === 'join' ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket' }}" aria-hidden="true"></i>
                                        <span class="dash-live-room-event-name">{{ $event['name'] }}</span>
                                        <span class="dash-live-room-event-meta">{{ $event['type'] === 'join' ? 'joined' : 'left' }} {{ \Illuminate\Support\Carbon::parse($event['at'])->diffForHumans(short: true) }}</span>
                                    </li>
                                @empty
                                    <li class="dash-live-room-empty"><i class="fa-solid fa-hourglass-start"></i> Waiting for the first student to join</li>
                                @endforelse
                            </ul>

                            <div class="dash-live-room-foot">
                                <a class="btn btn-secondary" href="{{ route('instructor.classes.virtual_classes.room', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}">
                                    <i class="fa-solid fa-video"></i> Open classroom
                                </a>
                                <a class="dash-live-room-detail" href="{{ route('instructor.classes.virtual_classes.show', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}">Details</a>
                            </div>

                            <p class="dash-live-room-note">
                                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                Headcount is live from the room; the trend and this feed come from LMS attendance records.
                            </p>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- ═══ TO-DO LIST ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-list-check"></i> To-do list</h4>
                    <span class="panel-count" id="upcoming-count">{{ ($stats['upcoming_activities'] ?? collect())->count() }}</span>
                </div>
                <div style="padding:14px">
                    @if(isset($stats['upcoming_activities']) && $stats['upcoming_activities']->isNotEmpty())
                        <div class="activity-list">
                            @foreach($stats['upcoming_activities'] as $activity)
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
            <span class="panel-count" id="to-check-count">{{ $stats['to_check_count'] ?? 0 }}</span>
        </div>
        <ul class="dash-list">
            @forelse(($stats['to_check'] ?? []) as $item)
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

            {{-- ═══ UPCOMING VIRTUAL CLASSES ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-video"></i> Upcoming virtual classes</h4>
                    <span class="panel-count">{{ count($stats['upcoming_virtual_classes'] ?? []) }}</span>
                </div>
                <ul class="dash-list">
                    @forelse(($stats['upcoming_virtual_classes'] ?? []) as $vc)
                        @php $isLiveVirtual = $vc->isLiveNow(); @endphp
                        <li class="dash-list-item">
                            <a class="dash-list-link" href="{{ $vc->class_id ? route('instructor.classes.virtual_classes.show', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) : '#' }}">
                                <span class="dash-list-icon i-violet"><i class="fa-solid fa-video"></i></span>
                                <div class="dash-list-body">
                                    <p class="dash-list-title">{{ $vc->title }}</p>
                                    <p class="dash-list-sub">{{ $vc->class?->course?->title ?? '' }} · {{ $vc->class?->code ?? '' }}</p>
                                </div>
                                <div class="dash-list-meta">
                                    @if($isLiveVirtual)
                                        <span class="dash-meta-chip m-green">Live now</span>
                                        <span class="dash-meta-chip m-gray dash-live-count"
                                              data-live-count-url="{{ route('instructor.classes.virtual_classes.live-count', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}"
                                              data-live="1"
                                              data-exact="{{ $vc->live_count_is_exact ? '1' : '0' }}"
                                              data-live-participants="{{ json_encode($vc->live_participants ?? []) }}"
                                              aria-live="polite"
                                              title="{{ $vc->live_count_is_exact ? 'Who is in the room — hover to see the names' : 'Estimated from attendance — hover to see the names' }}">
                                            <i class="fa-solid fa-users" aria-hidden="true"></i>
                                            <span data-live-count-text>{{ $vc->live_count_is_exact ? '' : '~' }}{{ (int) $vc->live_participant_count }} in room</span>
                                        </span>
                                    @else
                                        <span class="dash-meta-chip m-blue">{{ \Carbon\Carbon::parse($vc->meeting_date->format('Y-m-d').' '.$vc->start_time)->format('M j, g:i A') }}</span>
                                    @endif
                                    <span class="dash-list-date">{{ \Carbon\Carbon::parse($vc->meeting_date->format('Y-m-d').' '.$vc->start_time)->diffForHumans() }}</span>
                                </div>
                            </a>
                            @if($isLiveVirtual)
                                <a class="dash-join-btn dash-live" href="{{ route('instructor.classes.virtual_classes.room', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" title="Open the live virtual classroom">
                                    <i class="fa-solid fa-video"></i> <span>Join</span>
                                </a>
                                @if($vc->canEnd())
                                    <form class="dash-list-action" method="POST" action="{{ route('instructor.classes.virtual_classes.end', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" onsubmit="return confirm('End this session for everyone? Students will be disconnected and can no longer join.');">
                                        @csrf
                                        <button type="submit" class="dash-join-btn dash-end" title="End this session for everyone">
                                            <i class="fa-solid fa-stop"></i> <span>End</span>
                                        </button>
                                    </form>
                                @endif
                            @elseif($vc->canStartNow())
                                <form class="dash-list-action" method="POST" action="{{ route('instructor.classes.virtual_classes.start', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) }}" onsubmit="return confirm('Start this virtual class now?');">
                                    @csrf
                                    <button type="submit" class="dash-join-btn" title="Start this virtual class now">
                                        <i class="fa-solid fa-play"></i> <span>Start</span>
                                    </button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="dash-list-empty"><i class="fa-solid fa-video"></i> No upcoming virtual classes</li>
                    @endforelse
                </ul>
            </section>

            {{-- ═══ RECENT NOTIFICATIONS ═══ --}}
            <section class="dash-panel">
                <div class="dash-panel-head">
                    <h4><i class="fa-solid fa-bullhorn"></i> Recent notifications</h4>
                    <span class="panel-count">{{ $stats['total_announcements'] ?? 0 }}</span>
                </div>
                <ul class="announce-feed">
                    @forelse(($stats['recent_announcements'] ?? []) as $announcement)
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
            <div class="analytics-grid">
                <div class="analytics-card" data-analytics-type="performance">
                    <h5><i class="fa-solid fa-chart-line"></i> Grade Distribution</h5>
                    <div id="gradeDistributionChart" class="chart-container"></div>
                </div>
                <div class="analytics-card" data-analytics-type="enrollment">
                    <h5><i class="fa-solid fa-users"></i> Enrollment Trends</h5>
                    <div id="enrollmentTrendsChart" class="chart-container"></div>
                </div>
                <div class="analytics-card" data-analytics-type="attendance">
                    <h5><i class="fa-solid fa-clipboard-check"></i> Attendance Overview</h5>
                    <div id="attendanceOverviewChart" class="chart-container"></div>
                </div>
                <div class="analytics-card" data-analytics-type="engagement">
                    <h5><i class="fa-solid fa-comments"></i> Engagement Metrics</h5>
                    <div id="engagementMetricsChart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ ENHANCED DASHBOARD FUNCTIONALITY ═══ --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        (function renderCompletionChart() {
            const container = document.getElementById('completionChart');
            if (!container || typeof Chart === 'undefined') return;

            const completionData = @json($stats['completion_by_class'] ?? []);
            if (!completionData.length) return;

            const style = getComputedStyle(document.querySelector('.main:has(.dash-scope) .content') || document.body);
            const axisColor = style.getPropertyValue('--id-faint').trim() || '#72809e';
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
                            backgroundColor: 'rgba(52, 211, 153, .85)',
                            borderRadius: 6,
                        },
                        {
                            label: 'Active',
                            data: completionData.map(d => d.active),
                            backgroundColor: 'rgba(91, 140, 255, .55)',
                            borderRadius: 6,
                        },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { color: axisColor, precision: 0 },
                            grid: { color: gridColor },
                        },
                        y: {
                            stacked: true,
                            ticks: { color: axisColor },
                            grid: { display: false },
                        },
                    },
                    plugins: {
                        legend: { labels: { color: axisColor } },
                        title: {
                            display: true,
                            text: 'Students completed vs. active per class',
                            color: axisColor,
                        },
                    },
                },
            });
        })();

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
                    if (upcomingCountEl && stats.upcoming_assignments_count !== undefined && stats.upcoming_quizzes_count !== undefined) {
                        upcomingCountEl.textContent = stats.upcoming_assignments_count + stats.upcoming_quizzes_count;
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

            const recent = analytics.recent_enrollments || [];

            // Cumulative enrollment counts — meaningful trend data
            const counts = [];
            recent.forEach((_, i) => counts.push(i + 1));

            container.innerHTML = '<canvas id="enrollmentChartCanvas"></canvas>';
            const ctx = document.getElementById('enrollmentChartCanvas');

            chartInstances['enrollmentTrendsChart'] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: recent.length ? recent.map(e => e.date || '') : ['No data'],
                    datasets: [{
                        label: 'Enrollments',
                        data: counts.length ? counts : [0],
                        fill: true,
                        borderColor: '#62c9f5',
                        backgroundColor: 'rgba(98, 201, 245, 0.15)',
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#62c9f5'
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
                                    const e = recent[context.dataIndex];
                                    return e ? `${e.student} — ${e.course}` : '';
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
                    <span class="summary-label">Total Enrollments</span>
                    <span class="summary-value">${analytics.total_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completed</span>
                    <span class="summary-value">${analytics.completed_enrollments}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Completion Rate</span>
                    <span class="summary-value">${analytics.completion_rate.toFixed(1)}%</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">At-Risk Students</span>
                    <span class="summary-value warning">${analytics.at_risk_students}</span>
                </div>
            `;
            container.appendChild(summary);
        }

        function displayPerformanceAnalytics(analytics) {
            const container = document.getElementById('gradeDistributionChart');
            destroyChart('gradeDistributionChart');
            const distribution = analytics.grade_distribution;

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
                        backgroundColor: ['#34d399', '#62c9f5', '#fbbf24', '#fb923c', '#fb7185'],
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

            // Average grade line
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

            container.appendChild(avgWrap);
        }

        function displayAttendanceAnalytics(analytics) {
            const container = document.getElementById('attendanceOverviewChart');
            destroyChart('attendanceOverviewChart');

            const byClass = analytics.attendance_by_class || [];
            container.innerHTML = '<canvas id="attendanceChartCanvas"></canvas>';
            const ctx = document.getElementById('attendanceChartCanvas');

            chartInstances['attendanceOverviewChart'] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: byClass.length ? byClass.map(c => c.class_code) : ['No data'],
                    datasets: [{
                        label: 'Attendance Rate %',
                        data: byClass.length ? byClass.map(c => c.attendance_rate.toFixed(1)) : [0],
                        backgroundColor: 'rgba(52, 211, 153, 0.6)',
                        borderColor: '#34d399',
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
                                    return cls ? ` ${cls.attendance_rate.toFixed(1)}% (${cls.total_records} records)` : '';
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
                    <span class="summary-label">Average Attendance Rate</span>
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
        .dash-scope .type-badge.type-student{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .type-badge.type-course{background:rgba(91,140,255,.15);color:#9db9ff}
        .dash-scope .type-badge.type-class{background:rgba(92,201,245,.13);color:#7dd6f8}
        .dash-scope .type-badge.type-assignment{background:rgba(251,191,36,.13);color:#fcd34d}
        .dash-scope .type-badge.type-quiz{background:rgba(167,139,250,.15);color:#c4b5fd}
        .dash-scope .status-badge{padding:2px 8px;border-radius:12px;font-size:12px;font-weight:500}
        .dash-scope .status-badge.published{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .status-badge.draft{background:rgba(251,191,36,.13);color:#fcd34d}
        .dash-scope .status-badge.active{background:rgba(91,140,255,.15);color:#9db9ff}

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
        .dash-scope .dash-panel-head .panel-count{min-width:26px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:rgba(91,140,255,.14);color:var(--id-brand);font-size:.66rem;font-weight:800;padding:0 8px}
        body.light-mode .dash-scope .dash-panel-head .panel-count{color:#1d4ed8}

        .dash-scope .dash-list-item{display:flex;align-items:center;gap:12px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .dash-list-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .dash-list-item+.dash-list-item{border-top:1px solid var(--id-line)}
        .dash-scope .dash-list-icon{display:grid;place-items:center;width:37px;height:37px;flex:none;border-radius:11px;font-size:.85rem;background:rgba(91,140,255,.13);color:var(--id-brand)}
        .dash-scope .dash-list-icon.i-violet{background:rgba(167,139,250,.14);color:#c4b5fd}
        .dash-scope .dash-list-icon.i-green{background:rgba(52,211,153,.13);color:#6ee7b7}
        .dash-scope .dash-list-icon.i-rose{background:rgba(251,113,133,.13);color:#fda4af}
        .dash-scope .dash-list-icon.i-amber{background:rgba(251,191,36,.13);color:#fcd34d}
        body.light-mode .dash-scope .dash-list-icon{color:#1d4ed8}
        body.light-mode .dash-scope .dash-list-icon.i-violet{color:#7c3aed}
        body.light-mode .dash-scope .dash-list-icon.i-green{color:#059669}
        body.light-mode .dash-scope .dash-list-icon.i-rose{color:#e11d48}
        body.light-mode .dash-scope .dash-list-icon.i-amber{color:#b45309}
        .dash-scope .dash-meta-chip{padding:3px 9px;border-radius:999px;font-size:.64rem;font-weight:800;letter-spacing:.03em;white-space:nowrap}
        .dash-scope .dash-meta-chip.m-green{color:#6ee7b7;background:rgba(52,211,153,.13)}
        .dash-scope .dash-meta-chip.m-amber{color:#fcd34d;background:rgba(251,191,36,.13)}
        .dash-scope .dash-meta-chip.m-rose{color:#fda4af;background:rgba(251,113,133,.13)}
        .dash-scope .dash-meta-chip.m-blue{color:#9db9ff;background:rgba(91,140,255,.14)}
        .dash-scope .dash-meta-chip.m-gray{color:var(--id-muted);background:rgba(148,163,184,.13)}
        body.light-mode .dash-scope .dash-meta-chip.m-green{color:#047857}
        body.light-mode .dash-scope .dash-meta-chip.m-amber{color:#b45309}
        body.light-mode .dash-scope .dash-meta-chip.m-rose{color:#be123c}
        body.light-mode .dash-scope .dash-meta-chip.m-blue{color:#1d4ed8}
        .dash-scope .dash-list-empty{padding:26px 18px;text-align:center;color:var(--id-muted);font-size:.82rem}
        .dash-scope .dash-list-empty i{margin-right:8px;opacity:.7}

        /* ── Class performance ────────────────────────────── */
        .dash-scope .perf-item{padding:12px 4px}
        .dash-scope .perf-item+.perf-item{border-top:1px solid var(--id-line)}
        .dash-scope .perf-name{color:var(--id-text);font-size:.83rem;font-weight:700}
        .dash-scope .perf-score{color:var(--id-brand);font-size:.74rem;font-weight:800}
        body.light-mode .dash-scope .perf-score{color:#1d4ed8}
        .dash-scope .perf-bar{height:7px;border-radius:999px;background:var(--id-surface-3);overflow:hidden;margin-top:8px}
        .dash-scope .perf-bar>span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,#1a3fb5,#5b8cff,#5cc9f5);transition:width .6s ease}
        .dash-scope .perf-meta{color:var(--id-muted);font-size:.66rem}

        /* ── Announcements ────────────────────────────────── */
        .dash-scope .announce-item{display:flex;gap:13px;padding:12px 12px;border-radius:12px;transition:background .18s}
        .dash-scope .announce-item:hover{background:rgba(91,140,255,.07)}
        .dash-scope .announce-item+.announce-item{border-top:1px solid var(--id-line)}
        .dash-scope .announce-date{flex:none;display:flex;flex-direction:column;align-items:center;justify-content:center;width:48px;height:48px;border-radius:13px;background:linear-gradient(150deg,rgba(91,140,255,.2),rgba(92,201,245,.1));border:1px solid rgba(91,140,255,.25);color:var(--id-brand)}
        .dash-scope .announce-date .ad-day{color:var(--id-text);font-size:1.05rem;font-weight:800;line-height:1}
        .dash-scope .announce-date .ad-mon{font-size:.58rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;margin-top:3px}
        body.light-mode .dash-scope .announce-date{color:#1d4ed8}
        .dash-scope .announce-title{color:var(--id-text)}

        /* ── Analytics ────────────────────────────────────── */
        .dash-scope .dash-analytics{margin:0;padding:22px;background:var(--id-surface);border:1px solid var(--id-line);border-radius:18px;box-shadow:var(--id-shadow)}
        .dash-scope .dash-analytics .dash-panel-head{padding:0 0 16px;border-bottom:1px solid var(--id-line);margin-bottom:20px}
        .dash-scope .analytics-toast{display:none;margin-bottom:16px;padding:10px 14px;border-radius:10px;background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.3);color:#6ee7b7;font-size:.8rem;font-weight:650}
        .dash-scope .analytics-toast.show{display:block;animation:idFadeInOut 3s ease}
        @keyframes idFadeInOut{0%{opacity:0;transform:translateY(-4px)}10%,85%{opacity:1;transform:translateY(0)}100%{opacity:0}}
        .dash-scope .analytics-controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
        .dash-scope .analytics-select{padding:8px 12px;border:1px solid var(--id-line-strong);border-radius:9px;background:var(--id-surface-3);color:var(--id-text);cursor:pointer;font-size:13.5px}
        .dash-scope .analytics-btn{padding:9px 17px;background:linear-gradient(135deg,#3a6af0,#1a3fb5);color:#fff;border:1px solid rgba(120,160,255,.4);border-radius:9px;cursor:pointer;font-size:13.5px;font-weight:700;transition:filter .18s,transform .16s}
        .dash-scope .analytics-btn:hover:not(:disabled){filter:brightness(1.15);transform:translateY(-1px)}
        .dash-scope .analytics-btn:disabled{opacity:.7;cursor:default}
        .dash-scope .analytics-content{margin-top:20px}
        .dash-scope .analytics-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
        .dash-scope .analytics-card{background:var(--id-surface-2);border:1px solid var(--id-line);border-radius:14px;padding:20px}
        .dash-scope .analytics-card h5{margin:0 0 16px;font-size:.82rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;color:var(--id-text);display:flex;align-items:center;gap:8px}
        .dash-scope .analytics-card h5 i{color:var(--id-brand)}
        .dash-scope .chart-container{position:relative;height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center}
        .dash-scope .chart-container canvas{max-height:100%}
        .dash-scope .chart-placeholder{text-align:center;color:var(--id-muted)}
        .dash-scope .chart-placeholder i{font-size:44px;margin-bottom:12px;color:var(--id-line-strong)}
        .dash-scope .chart-placeholder p{margin:0;font-size:14px}
        .dash-scope .analytics-summary,.dash-scope .attendance-summary{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;width:100%;margin-top:18px;padding-top:16px;border-top:1px solid var(--id-line)}
        .dash-scope .summary-item{display:flex;flex-direction:column;gap:4px}
        .dash-scope .summary-label{font-size:11px;color:var(--id-muted);font-weight:700;text-transform:uppercase;letter-spacing:.05em}
        .dash-scope .summary-value{font-size:21px;font-weight:800;color:var(--id-text)}
        .dash-scope .summary-value.warning{color:var(--id-rose)}
        .dash-scope .average-grade{display:flex;flex-direction:column;gap:2px;margin-top:16px;padding-top:16px;border-top:1px solid var(--id-line);width:100%;text-align:center}
        .dash-scope .avg-label{font-size:14px;font-weight:700;color:var(--id-text)}
        .dash-scope .avg-sub{font-size:12px;color:var(--id-muted)}
        .dash-scope .attendance-bar{background:var(--id-surface-3)}
        .dash-scope .grade-progress{background:var(--id-surface-3)}
        .dash-scope .metric-item{display:flex;align-items:center;gap:12px;padding:13px;background:var(--id-surface-2);border:1px solid var(--id-line);border-radius:11px}
        .dash-scope .metric-icon{width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:rgba(91,140,255,.14);border-radius:12px;color:var(--id-brand);font-size:16px}
        body.light-mode .dash-scope .metric-icon{color:#1d4ed8}
        .dash-scope .metric-label{flex:1;font-size:14px;color:var(--id-muted)}
        .dash-scope .metric-value{font-size:18px;font-weight:750;color:var(--id-text)}
        .dash-scope .engagement-metrics{display:flex;flex-direction:column;gap:12px}
        .dash-scope .grade-label,.dash-scope .class-label{color:var(--id-muted)}
        .dash-scope .grade-count,.dash-scope .attendance-rate{color:var(--id-text)}

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

        /* ── Live headcount hover roster (only on a dashboard with a live row) ── */
        @php
            // Nothing live means nothing to hover: skip the chip styling and
            // roster layout entirely on a quiet dashboard.
            $hasLiveVirtualRow = collect($stats['upcoming_virtual_classes'] ?? [])
                ->contains(function ($vc) {
                    return $vc->isLiveNow();
                });
        @endphp
        @if($hasLiveVirtualRow)
        .dash-scope .dash-live-count{cursor:help}
        /* Fixed, and attached to .dash-scope rather than the chip: every panel
           on this dashboard clips its own overflow, so a roster nested in the
           row was clipped away as soon as it reached a panel edge. The script
           offsets it from the chip; .dash-scope keeps the theme tokens in
           scope. */
        .dash-live-roster{
            display:none;
            position:fixed;
            left:0;
            top:0;
            min-width:200px;
            max-width:260px;
            background:var(--id-surface-2);
            border:1px solid var(--id-line-strong);
            border-radius:12px;
            box-shadow:var(--id-shadow-lg);
            z-index:1080;
            font-size:.78rem;
            color:var(--id-text);
        }
        .dash-live-roster.show{display:block}
        .dash-live-roster-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:10px 12px;
            border-bottom:1px solid var(--id-line);
        }
        .dash-live-roster-title{
            font-weight:800;
            letter-spacing:.05em;
            text-transform:uppercase;
            font-size:.66rem;
            color:var(--id-muted);
        }
        .dash-live-roster-count{
            font-variant-numeric:tabular-nums;
            font-weight:800;
            color:var(--id-brand);
        }
        .dash-live-roster-body{
            max-height:240px;
            overflow-y:auto;
            padding:6px 0;
        }
        .dash-live-roster-row{
            display:flex;
            align-items:center;
            gap:8px;
            padding:7px 12px;
            font-size:.82rem;
        }
        .dash-live-roster-row:hover{background:rgba(91,140,255,.08)}
        .dash-live-roster-avatar{
            display:grid;
            place-items:center;
            width:26px;
            height:26px;
            border-radius:50%;
            background:var(--id-brand);
            color:#fff;
            font-size:.72rem;
            font-weight:800;
            flex:none;
        }
        .dash-live-roster-name{
            flex:1;
            min-width:0;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            font-weight:600;
        }
        .dash-live-roster-name span{font-weight:600}
        .dash-live-roster-role{
            flex:none;
            font-size:.64rem;
            font-weight:800;
            letter-spacing:.04em;
            padding:2px 8px;
            border-radius:999px;
            text-transform:uppercase;
        }
        .dash-live-roster-role.instructor{
            background:rgba(52,211,153,.13);
            color:#6ee7b7;
        }
        .dash-live-roster-role.student{
            background:rgba(91,140,255,.13);
            color:#9db9ff;
        }
        .dash-live-roster-role.participant{
            background:rgba(148,163,184,.16);
            color:var(--id-muted);
        }
        .dash-live-roster-joined{
            flex:none;
            font-size:.7rem;
            font-variant-numeric:tabular-nums;
            color:var(--id-muted);
        }
        .dash-live-roster-foot{
            padding:6px 12px;
            font-size:.66rem;
            color:var(--id-muted);
            border-top:1px solid var(--id-line);
        }
        .dash-live-roster-empty{
            padding:14px 12px;
            text-align:center;
            color:var(--id-muted);
            font-size:.8rem;
        }

        /* ── Live room status widget (same gate: only with a live row) ── */
        .dash-scope .dash-live-room{padding:14px}
        .dash-scope .dash-live-room + .dash-live-room{border-top:1px solid var(--id-line)}
        .dash-live-room-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
        .dash-live-room-title{margin:0;font-weight:700;font-size:.9rem;color:var(--id-text)}
        .dash-live-room-sub{margin:2px 0 0;font-size:.72rem;color:var(--id-muted)}
        .dash-live-room-count{
            display:inline-flex;align-items:center;gap:6px;flex:none;white-space:nowrap;
            padding:5px 10px;border-radius:999px;font-size:.72rem;font-weight:700;
            background:var(--id-surface-3);border:1px solid var(--id-line);color:var(--id-text);
        }
        .dash-live-room-count i{color:var(--id-green)}
        .dash-live-room-count b{font-size:.86rem;font-variant-numeric:tabular-nums}
        .dash-live-room-spark{margin:12px 0 0;padding:8px 10px 6px;border:1px solid var(--id-line);border-radius:12px;background:var(--id-surface-3)}
        .dash-live-room-spark-label{margin:0 0 3px;font-size:.64rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--id-muted)}
        .dash-live-room-spark canvas{display:block;width:100%;height:46px}
        .dash-live-room-trend{margin:4px 0 0;font-size:.7rem;color:var(--id-muted);font-variant-numeric:tabular-nums}
        .dash-live-room-events{list-style:none;margin:12px 0 0;padding:0;display:grid;gap:7px;max-height:160px;overflow-y:auto}
        .dash-live-room-event{display:flex;align-items:center;gap:8px;font-size:.76rem;color:var(--id-text)}
        .dash-live-room-event i{flex:none;width:14px;text-align:center;color:var(--id-muted)}
        .dash-live-room-event.is-join i{color:var(--id-green)}
        .dash-live-room-event.is-leave i{color:var(--id-rose)}
        .dash-live-room-event-name{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600}
        .dash-live-room-event-meta{flex:none;font-size:.68rem;color:var(--id-muted)}
        .dash-live-room-empty{display:flex;align-items:center;gap:8px;font-size:.76rem;color:var(--id-muted)}
        .dash-live-room-foot{display:flex;align-items:center;gap:12px;margin-top:12px}
        .dash-live-room-detail{font-size:.74rem;font-weight:700;color:var(--id-brand);text-decoration:none}
        .dash-live-room-detail:hover{text-decoration:underline}
        .dash-live-room-note{display:flex;gap:7px;margin:10px 0 0;font-size:.68rem;line-height:1.35;color:var(--id-muted)}
        .dash-live-room.is-ended .dash-live-room-count{opacity:.55}
        @endif
    </style>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // Keep the live headcount fresh without reloading the dashboard. Only
    // rows that are actually live carry data-live-count-url, so a quiet
    // dashboard makes zero requests.
    var POLL_MS = 20000;
    var chips = Array.prototype.slice.call(document.querySelectorAll('[data-live-count-url]'));
    var rooms = Array.prototype.slice.call(document.querySelectorAll('[data-live-room-url]'));

    // One request per live room, however many places watch it: the headcount
    // chip and the live room status widget share an endpoint.
    var watched = [];

    chips.concat(rooms).forEach(function (element) {
        var url = element.getAttribute('data-live-count-url') || element.getAttribute('data-live-room-url');

        if (url && watched.indexOf(url) === -1) watched.push(url);
    });

    if (!watched.length) return;

    var timer = null;
    var inFlight = {};
    var ended = {};

    // "(You)" only makes sense for the instructor reading this page.
    var scope = document.querySelector('.dash-scope[data-viewer-id]');
    var viewerId = scope ? String(scope.getAttribute('data-viewer-id') || '') : '';

    function apply(chip, data) {
        var label = chip.querySelector('[data-live-count-text]');
        if (!label) return;

        if (data.live === false) {
            chip.dataset.live = '0';
            label.textContent = 'Session ended';
            chip.title = 'This session is no longer live';
            hideRoster(chip);

            if (chip.roster) {
                chip.roster.remove();
                chip.roster = null;
            }

            // The server-rendered Join/End controls would now point at a
            // closed room; drop them instead of leaving a dead button.
            var row = chip.closest('.dash-list-item');
            if (row) {
                row.querySelectorAll('.dash-join-btn, .dash-list-action').forEach(function (element) {
                    element.remove();
                });
            }
            return;
        }

        var count = Number(data.count) || 0;
        chip.dataset.exact = data.exact ? '1' : '0';
        label.textContent = (data.exact ? '' : '~') + count + ' in room';
        chip.title = data.exact
            ? 'Who is in the room — ' + count + ' from LiveKit'
            : 'Estimated from attendance — ' + count + ' names';

        paint(chip, data.participants || [], count);
    }

    function paint(chip, participants, count) {
        var roster = rosterFor(chip);
        var body = roster.querySelector('.dash-live-roster-body');
        var foot = roster.querySelector('.dash-live-roster-foot');
        var headCount = roster.querySelector('.dash-live-roster-count');

        if (body) body.innerHTML = renderRosterRows(participants);
        if (headCount) headCount.textContent = '(' + (typeof count === 'number' ? count : participants.length) + ')';
        if (foot) foot.textContent = chip.dataset.exact === '1' ? 'Live from LiveKit' : 'Estimated from attendance';
    }

    var HIDE_DELAY_MS = 140;
    var hideTimer = null;

    /** Built on demand and reused. */
    function rosterFor(chip) {
        if (chip.roster) return chip.roster;

        var roster = document.createElement('div');
        roster.className = 'dash-live-roster';
        roster.setAttribute('role', 'tooltip');
        // A visual detail only: keep the roster out of the polite live region
        // so the names are not re-announced on every 20s refresh.
        roster.setAttribute('aria-live', 'off');
        roster.innerHTML =
            '<div class="dash-live-roster-head">' +
            '<span class="dash-live-roster-title">In room</span>' +
            '<span class="dash-live-roster-count"></span>' +
            '</div>' +
            '<div class="dash-live-roster-body"></div>' +
            '<div class="dash-live-roster-foot"></div>';

        // Outside the row (and outside its clipping panel), but still inside the
        // element that carries this dashboard's theme tokens.
        (document.querySelector('.dash-scope') || document.body).appendChild(roster);

        roster.addEventListener('mouseenter', cancelHide);
        roster.addEventListener('mouseleave', function () { scheduleHide(chip); });

        chip.roster = roster;

        return roster;
    }

    /** Offset from the chip, flipped below when there is no room above. */
    function positionRoster(chip, roster) {
        var rect = chip.getBoundingClientRect();
        var gap = 8;
        var width = roster.offsetWidth;
        var height = roster.offsetHeight;
        var above = rect.top - height - gap >= gap;
        var left = Math.min(
            Math.max(rect.left + rect.width / 2 - width / 2, gap),
            window.innerWidth - width - gap
        );

        roster.style.left = Math.round(left) + 'px';
        roster.style.top = Math.round(above ? rect.top - height - gap : rect.bottom + gap) + 'px';
    }

    function cancelHide() {
        if (hideTimer !== null) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }
    }

    /**
     * A short grace period so the pointer can travel the 8px gap between the
     * chip and the roster without it vanishing.
     */
    function scheduleHide(chip) {
        cancelHide();
        hideTimer = window.setTimeout(function () { hideRoster(chip); }, HIDE_DELAY_MS);
    }

    function showRoster(chip) {
        cancelHide();

        var roster = rosterFor(chip);
        roster.classList.add('show');
        // Measure only once it is displayed, or offsetHeight reads 0.
        positionRoster(chip, roster);
    }

    function hideRoster(chip) {
        if (chip.roster) chip.roster.classList.remove('show');
    }

    function repositionRosters() {
        chips.forEach(function (chip) {
            if (chip.roster && chip.roster.classList.contains('show')) positionRoster(chip, chip.roster);
        });
    }

    /**
     * Participant names are user-supplied (a student picks their own profile
     * name), so every value is escaped before it reaches innerHTML.
     */
    function escapeText(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    /** Connect time → "12m" / "1h 5m", blank when LiveKit did not report one. */
    function minutesInRoom(joinedAt) {
        if (!joinedAt) return '';

        var started = Date.parse(joinedAt);
        if (isNaN(started)) return '';

        var minutes = Math.max(0, Math.round((Date.now() - started) / 60000));
        if (minutes < 1) return 'just joined';
        if (minutes < 60) return minutes + 'm';

        return Math.floor(minutes / 60) + 'h ' + (minutes % 60) + 'm';
    }

    function roleBadge(participant) {
        var role = participant.role === 'instructor' || participant.is_instructor
            ? 'instructor'
            : (participant.role === 'student' ? 'student' : 'participant');
        var label = role === 'student' ? 'Student' : (role === 'participant' ? 'Observer' : 'Instructor');

        return '<span class="dash-live-roster-role ' + role + '">' + label + '</span>';
    }

    function renderRosterRows(participants) {
        if (!participants || !participants.length) {
            return '<div class="dash-live-roster-empty">No one connected</div>';
        }

        var html = '';
        participants.forEach(function (p) {
            var data = p || {};
            var name = String(data.name || data.identity || 'Participant');
            var isMe = viewerId !== '' && String(data.identity || '') === 'user-' + viewerId;
            var joined = minutesInRoom(data.joined_at);

            html +=
                '<div class="dash-live-roster-row">' +
                '<span class="dash-live-roster-avatar">' + escapeText(name.charAt(0).toUpperCase() || '?') + '</span>' +
                '<span class="dash-live-roster-name">' + escapeText(name) + (isMe ? ' <span>(You)</span>' : '') + '</span>' +
                (joined ? '<span class="dash-live-roster-joined">' + escapeText(joined) + '</span>' : '') +
                roleBadge(data) +
                '</div>';
        });

        return html;
    }

    /* ── Live room status widget ── */

    function applyRoom(room, data) {
        var done = data.live === false;
        var count = room.querySelector('[data-live-room-count]');
        var trend = room.querySelector('[data-live-room-trend]');
        var history = data.history || [];

        room.classList.toggle('is-ended', done);

        if (count) {
            count.textContent = done ? '0' : (data.exact ? '' : '~') + (Number(data.count) || 0);
        }

        if (trend) {
            trend.textContent = done ? 'Session ended' : trendLabel(data, history);
        }

        drawSpark(room, history);
        renderRoomEvents(room, data.events || []);
    }

    /** "Peak 4 · now 2", the text twin of the sparkline. */
    function trendLabel(data, history) {
        var counts = history.map(function (point) { return Number(point.count) || 0; });
        var peak = Number(data.peak);
        var now = Number(data.now);

        if (!isFinite(peak)) peak = counts.length ? Math.max.apply(null, counts) : 0;
        if (!isFinite(now)) now = counts.length ? counts[counts.length - 1] : 0;

        return 'Peak ' + peak + ' · now ' + now;
    }

    function drawSpark(room, history) {
        var canvas = room.querySelector('canvas[data-live-room-canvas]');

        // The canvas is decoration: without the chart library (or without
        // JavaScript) the count and the trend text still carry the meaning.
        if (!canvas || typeof window.Chart === 'undefined') return;

        var labels = history.map(function (point) { return clockLabel(point.at); });
        var values = history.map(function (point) { return Number(point.count) || 0; });

        if (room.chart) {
            room.chart.data.labels = labels;
            room.chart.data.datasets[0].data = values;
            room.chart.update('none');
            return;
        }

        var accent = brandColor(room);

        room.chart = new window.Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Students in room',
                    data: values,
                    fill: true,
                    borderColor: accent,
                    backgroundColor: withAlpha(accent, 0.18),
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHitRadius: 12,
                    tension: 0.35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                // Repainted every 20s: animating each refresh just makes the
                // line wobble.
                animation: false,
                layout: { padding: { top: 4, bottom: 2 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            title: function (items) { return items.length ? labels[items[0].dataIndex] : ''; },
                            label: function (item) { return item.formattedValue + ' in room'; },
                        },
                    },
                },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true, suggestedMax: 1 },
                },
            },
        });
    }

    function renderRoomEvents(room, events) {
        var list = room.querySelector('[data-live-room-events]');
        if (!list) return;

        if (!events.length) {
            list.innerHTML =
                '<li class="dash-live-room-empty">' +
                '<i class="fa-solid fa-hourglass-start"></i> Waiting for the first student to join</li>';
            return;
        }

        var html = '';
        events.forEach(function (event) {
            var data = event || {};
            var type = data.type === 'leave' ? 'leave' : 'join';

            html +=
                '<li class="dash-live-room-event is-' + type + '">' +
                '<i class="fa-solid ' + (type === 'join' ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket') + '" aria-hidden="true"></i>' +
                '<span class="dash-live-room-event-name">' + escapeText(data.name || data.identity || 'Participant') + '</span>' +
                '<span class="dash-live-room-event-meta">' + (type === 'join' ? 'joined' : 'left') + ' ' + escapeText(agoLabel(data.at)) + '</span>' +
                '</li>';
        });

        list.innerHTML = html;
    }

    function clockLabel(value) {
        var at = new Date(value);

        return isNaN(at.getTime()) ? '' : at.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    /** Shorter than the roster's "in room" label: the feed is dense. */
    function agoLabel(value) {
        var at = Date.parse(value);
        if (isNaN(at)) return '';

        var seconds = Math.max(0, Math.round((Date.now() - at) / 1000));
        if (seconds < 60) return seconds + 's ago';

        var minutes = Math.round(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';

        var hours = Math.round(minutes / 60);
        if (hours < 24) return hours + 'h ago';

        return Math.round(hours / 24) + 'd ago';
    }

    /** The dashboard's own accent, so the sparkline follows the theme. */
    function brandColor(room) {
        var value = window.getComputedStyle(room).getPropertyValue('--id-brand');

        return /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(value.trim()) ? value.trim() : '#5b8cff';
    }

    function withAlpha(hex, alpha) {
        var value = hex.replace('#', '');

        if (value.length === 3) {
            value = value[0] + value[0] + value[1] + value[1] + value[2] + value[2];
        }

        var number = parseInt(value, 16);

        return 'rgba(' + ((number >> 16) & 255) + ',' + ((number >> 8) & 255) + ',' + (number & 255) + ',' + alpha + ')';
    }

    function refresh(url) {
        if (!url || inFlight[url] || ended[url]) return Promise.resolve();

        inFlight[url] = true;

        return fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) return null;
                return response.json();
            })
            .then(function (data) {
                if (!data) return;

                if (data.live === false) ended[url] = true;

                publish(url, data);
            })
            .catch(function () {
                // Transient network failure: keep the last known count.
            })
            .then(function () {
                delete inFlight[url];
            });
    }

    /** Fan one response out to every chip and widget watching that room. */
    function publish(url, data) {
        chips.forEach(function (chip) {
            if (chip.dataset.liveCountUrl === url) apply(chip, data);
        });

        rooms.forEach(function (room) {
            if (room.getAttribute('data-live-room-url') === url) applyRoom(room, data);
        });
    }

    function tick() {
        if (document.visibilityState !== 'visible') return;

        var live = watched.filter(function (url) { return ended[url] !== true; });
        if (!live.length) { stop(); return; }

        live.forEach(refresh);
    }

    function start() {
        if (timer !== null) return;
        timer = window.setInterval(tick, POLL_MS);
    }

    function stop() {
        if (timer === null) return;
        window.clearInterval(timer);
        timer = null;
    }

    // Show the roster on the first hover instead of waiting for the first poll:
    // the server already fetched the participant list for the headcount and
    // embedded it on the chip. The chip sits inside the row link, so a click
    // toggles the roster rather than navigating away.
    chips.forEach(function (chip) {
        var initial = [];

        try {
            initial = JSON.parse(chip.dataset.liveParticipants || '[]') || [];
        } catch (error) {
            initial = [];
        }

        paint(chip, initial, initial.length);

        chip.addEventListener('mouseenter', function () { showRoster(chip); });
        chip.addEventListener('mouseleave', function () { scheduleHide(chip); });
        chip.addEventListener('click', function (event) {
            // Inside the row link: a tap toggles the roster instead of
            // navigating to the class page.
            event.preventDefault();
            event.stopPropagation();

            if (chip.roster && chip.roster.classList.contains('show')) {
                hideRoster(chip);
            } else {
                showRoster(chip);
            }
        });

        // The chip is not a tab stop; keyboard users focus the row link, which
        // is close enough to reveal the same roster.
        var row = chip.closest('.dash-list-item');
        if (row) {
            row.addEventListener('focusin', function () { showRoster(chip); });
            row.addEventListener('focusout', function () { scheduleHide(chip); });
        }
    });

    document.addEventListener('click', function (event) {
        chips.forEach(function (chip) {
            var roster = chip.roster;
            var insideRoster = roster && roster.contains(event.target);

            if (!chip.contains(event.target) && !insideRoster) hideRoster(chip);
        });
    });

    // The count and the feed are server-rendered; only the sparkline needs the
    // chart library, so it is drawn here from the payload the server embedded
    // rather than waiting 20 seconds for the first poll.
    rooms.forEach(function (room) {
        var initial = {};

        try {
            initial = JSON.parse(room.getAttribute('data-live-room-payload') || '{}') || {};
        } catch (error) {
            initial = {};
        }

        drawSpark(room, initial.points || []);
    });

    // A fixed roster does not travel with the chip on its own.
    window.addEventListener('scroll', repositionRosters, { passive: true });
    window.addEventListener('resize', repositionRosters);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { tick(); start(); }
    });

    start();
})();
</script>
@endpush

@extends('layouts.student')

@section('title', 'Class Portal')

@php
    $activeNav = 'classes';
    $course = $class->course;
    $courseTitle = $course?->title ?? $course?->name ?? 'Course';
    $enrolledCount = (int) ($class->enrolled_count ?? 0);
    $capacity = $class->max_students ?? $class->capacity;
    $availableSeats = $capacity ? max(0, $capacity - $enrolledCount) : null;
    $status = $isEnrolled ? ($enrollment?->status ?? 'enrolled') : ($class->is_active ? 'available' : 'unavailable');
    $statusClass = match ($status) {
        'active', 'available' => 'status-good',
        'completed' => 'status-info',
        'pending' => 'status-warning',
        'dropped', 'cancelled', 'unavailable' => 'status-danger',
        default => 'status-neutral',
    };
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> {{ $courseTitle }} · {{ $class->code }}</h2>
        <div class="page-actions">
            <a href="{{ route('student.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to classes</a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .student-class-portal { max-width:1180px; margin:0 auto; }
        .student-class-portal .class-hero { display:flex; align-items:center; gap:16px; overflow:hidden; margin-bottom:18px; padding:23px 25px; border:1px solid var(--dash-line); border-radius:18px; background:radial-gradient(circle at 90% 8%,rgba(98,201,245,.25),transparent 38%),linear-gradient(135deg,rgba(23,58,168,.92),rgba(10,16,32,.97)); box-shadow:var(--bcp-shadow); }
        .student-class-portal .class-hero-icon { display:grid; place-items:center; width:54px; height:54px; flex:none; border-radius:15px; color:#fff; background:linear-gradient(135deg,#22d3ee,#0e7490); box-shadow:0 12px 25px rgba(34,211,238,.25); }
        .student-class-portal .class-hero h1 { margin:0; color:#fff; font-size:clamp(1.12rem,2.2vw,1.65rem); }
        .student-class-portal .class-hero p { margin:5px 0 0; color:#c8d9f8; font-size:.78rem; }
        .student-class-portal .hero-status { margin-left:auto; display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px; font-size:.69rem; font-weight:800; white-space:nowrap; }
        .student-class-portal .status-good { color:#6ee7b7; background:rgba(16,185,129,.14); }
        .student-class-portal .status-info { color:#93c5fd; background:rgba(59,130,246,.14); }
        .student-class-portal .status-warning { color:#fcd34d; background:rgba(251,191,36,.14); }
        .student-class-portal .status-danger { color:#fda4af; background:rgba(244,63,94,.14); }
        .student-class-portal .status-neutral { color:#cbd5e1; background:rgba(148,163,184,.14); }
        .student-class-portal .overview-grid { display:grid; grid-template-columns:minmax(0,1.45fr) minmax(300px,.75fr); gap:18px; align-items:start; }
        .student-class-portal .portal-card { overflow:hidden; margin-bottom:18px; background:var(--dash-surface); border:1px solid var(--dash-line); border-radius:15px; box-shadow:var(--bcp-shadow); }
        .student-class-portal .portal-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 19px; border-bottom:1px solid var(--dash-line); background:linear-gradient(90deg,rgba(77,143,240,.1),transparent); }
        .student-class-portal .portal-card-head h3 { display:flex; align-items:center; gap:8px; margin:0; color:var(--dash-text); font-size:.8rem; letter-spacing:.05em; text-transform:uppercase; }
        .student-class-portal .portal-card-head h3 i { color:var(--dash-cyan); }
        .student-class-portal .portal-card-body { padding:18px 19px; }
        .student-class-portal .meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 24px; }
        .student-class-portal .meta-row { display:flex; justify-content:space-between; gap:14px; padding:11px 0; border-bottom:1px solid var(--dash-line); font-size:.75rem; }
        .student-class-portal .meta-row dt { color:var(--dash-muted); }
        .student-class-portal .meta-row dd { margin:0; color:var(--dash-text); font-weight:700; text-align:right; }
        .student-class-portal .rich-copy { margin-top:16px; padding:13px; color:var(--dash-text); background:rgba(77,143,240,.05); border:1px solid var(--dash-line); border-radius:10px; font-size:.76rem; line-height:1.55; }
        .student-class-portal .progress-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:11px; }
        .student-class-portal .progress-stat { padding:14px; border:1px solid var(--dash-line); border-radius:12px; background:rgba(77,143,240,.04); }
        .student-class-portal .progress-stat-label { display:block; color:var(--dash-muted); font-size:.63rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
        .student-class-portal .progress-stat-value { display:block; margin-top:6px; color:var(--dash-text); font-size:1.35rem; font-weight:850; }
        .student-class-portal .progress-track { height:7px; margin-top:9px; overflow:hidden; border-radius:999px; background:rgba(153,174,214,.16); }
        .student-class-portal .progress-track span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,#2449c6,#62c9f5); }
        .student-class-portal .resource-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:11px; }
        .student-class-portal .resource-card { display:flex; align-items:center; gap:11px; min-width:0; padding:13px; color:var(--dash-text); text-decoration:none; border:1px solid var(--dash-line); border-radius:11px; background:rgba(77,143,240,.04); transition:transform .18s,border-color .18s,background .18s; }
        .student-class-portal .resource-card:hover { transform:translateY(-2px); border-color:rgba(98,201,245,.45); background:rgba(77,143,240,.1); }
        .student-class-portal .resource-card:focus-visible { outline:3px solid var(--bcp-focus); outline-offset:3px; }
        .student-class-portal .resource-icon { display:grid; place-items:center; width:36px; height:36px; flex:none; border-radius:10px; color:#93c5fd; background:rgba(59,130,246,.14); }
        .student-class-portal .resource-card:nth-child(2) .resource-icon { color:#fcd34d; background:rgba(251,191,36,.14); }
        .student-class-portal .resource-card:nth-child(3) .resource-icon { color:#c4b5fd; background:rgba(139,92,246,.14); }
        .student-class-portal .resource-card:nth-child(4) .resource-icon { color:#f9a8d4; background:rgba(236,72,153,.14); }
        .student-class-portal .resource-card:nth-child(5) .resource-icon { color:#67e8f9; background:rgba(34,211,238,.14); }
        .student-class-portal .resource-card:nth-child(6) .resource-icon { color:#6ee7b7; background:rgba(16,185,129,.14); }
        .student-class-portal .resource-card strong { display:block; overflow:hidden; font-size:.76rem; text-overflow:ellipsis; white-space:nowrap; }
        .student-class-portal .resource-card small { display:block; margin-top:3px; color:var(--dash-muted); font-size:.65rem; }
        .student-class-portal .activity-list { display:grid; gap:9px; }
        .student-class-portal .activity-item { display:flex; align-items:center; gap:11px; padding:12px; border:1px solid var(--dash-line); border-radius:10px; background:rgba(77,143,240,.04); }
        .student-class-portal .activity-item i { color:var(--dash-cyan); }
        .student-class-portal .activity-copy { flex:1; min-width:0; }
        .student-class-portal .activity-copy strong { display:block; overflow:hidden; color:var(--dash-text); font-size:.76rem; text-overflow:ellipsis; white-space:nowrap; }
        .student-class-portal .activity-copy small { display:block; margin-top:3px; color:var(--dash-muted); font-size:.65rem; }
        .student-class-portal .activity-item a { flex:none; color:var(--dash-cyan); font-size:.7rem; font-weight:800; text-decoration:none; }
        .student-class-portal .announcement-item { padding:12px 0; border-bottom:1px solid var(--dash-line); }
        .student-class-portal .announcement-item:last-child { border-bottom:0; }
        .student-class-portal .announcement-item strong { color:var(--dash-text); font-size:.77rem; }
        .student-class-portal .announcement-item small { display:block; margin-top:3px; color:var(--dash-muted); font-size:.65rem; }
        .student-class-portal .announcement-item p { margin:7px 0 0; color:var(--dash-muted); font-size:.72rem; line-height:1.45; }
        .student-class-portal .empty-state { padding:25px 14px; color:var(--dash-muted); text-align:center; font-size:.75rem; }
        .student-class-portal .empty-state i { display:block; margin-bottom:8px; color:var(--dash-cyan); font-size:1.45rem; }
        .student-class-portal .action-row { display:flex; flex-wrap:wrap; gap:9px; margin-bottom:20px; }
        .student-class-portal .action-row form { display:inline-flex; }
        .student-class-portal .action-row .btn { min-height:40px; }
        @media(max-width:900px) { .student-class-portal .overview-grid { grid-template-columns:1fr; } }
        @media(max-width:600px) { .student-class-portal .class-hero { align-items:flex-start; } .student-class-portal .hero-status { display:none; } .student-class-portal .meta-grid, .student-class-portal .progress-grid, .student-class-portal .resource-grid { grid-template-columns:1fr; } .student-class-portal .activity-item { align-items:flex-start; } .student-class-portal .action-row { flex-direction:column; } .student-class-portal .action-row form, .student-class-portal .action-row .btn { width:100%; } .student-class-portal .action-row .btn { justify-content:center; } }
    </style>

    <div class="student-class-portal">
        <section class="class-hero" aria-labelledby="class-hero-title">
            <span class="class-hero-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
            <div>
                <h1 id="class-hero-title">{{ $course?->title ?? $course?->name ?? 'Class Portal' }}</h1>
                <p>{{ $class->code }} · {{ $class->instructor?->full_name ?? 'Instructor not assigned' }} · {{ $class->academicPeriod?->name ?? 'Academic period not specified' }}</p>
            </div>
            <span class="hero-status {{ $statusClass }}"><i class="fa-solid fa-circle"></i> {{ ucfirst($status) }}</span>
        </section>

        <div class="overview-grid">
            <div>
                <section class="portal-card" aria-labelledby="overview-title">
                    <div class="portal-card-head"><h3 id="overview-title"><i class="fa-solid fa-circle-info"></i> Class overview</h3></div>
                    <div class="portal-card-body">
                        <dl class="meta-grid">
                            <div class="meta-row"><dt>Course code</dt><dd>{{ $course?->code ?? '—' }}</dd></div>
                            <div class="meta-row"><dt>Class code</dt><dd>{{ $class->code }}</dd></div>
                            <div class="meta-row"><dt>Instructor</dt><dd>{{ $class->instructor?->full_name ?? '—' }}</dd></div>
                            <div class="meta-row"><dt>Schedule</dt><dd>{{ $class->schedule ?: 'Not specified' }}</dd></div>
                            <div class="meta-row"><dt>Room</dt><dd>{{ $class->room ?: 'Not specified' }}</dd></div>
                            <div class="meta-row"><dt>Enrollment</dt><dd>{{ $enrolledCount }}{{ $capacity ? ' / '.$capacity : '' }}</dd></div>
                            <div class="meta-row"><dt>Credits</dt><dd>{{ $course?->credits ?? 'N/A' }}</dd></div>
                            <div class="meta-row"><dt>Available seats</dt><dd>{{ $availableSeats ?? '—' }}</dd></div>
                        </dl>
                        @if($course?->description)
                            <div class="rich-copy"><strong>Course description</strong><br>{{ $course->description }}</div>
                        @endif
                        @if($course?->objectives)
                            <div class="rich-copy"><strong>Learning objectives</strong><br>{{ $course->objectives }}</div>
                        @endif
                    </div>
                </section>

                @if($isEnrolled && $enrollment?->status === 'active')
                    <section class="portal-card" aria-labelledby="progress-title">
                        <div class="portal-card-head"><h3 id="progress-title"><i class="fa-solid fa-chart-line"></i> Your progress</h3><span>{{ number_format($progress['overall'] ?? 0, 1) }}% overall</span></div>
                        <div class="portal-card-body">
                            <div class="progress-grid">
                                @foreach([
                                    ['label' => 'Overall progress', 'value' => $progress['overall'] ?? 0],
                                    ['label' => 'Modules completed', 'value' => $progress['modules_completed'] ?? 0, 'count' => true],
                                    ['label' => 'Lessons completed', 'value' => $progress['lessons_completed'] ?? 0, 'count' => true],
                                    ['label' => 'Assignments completed', 'value' => $progress['assignments_completed'] ?? 0, 'count' => true],
                                    ['label' => 'Quizzes completed', 'value' => $progress['quizzes_completed'] ?? 0, 'count' => true],
                                    ['label' => 'Current grade', 'value' => $progress['current_grade'] ?? 0, 'grade' => true],
                                ] as $metric)
                                    <div class="progress-stat">
                                        <span class="progress-stat-label">{{ $metric['label'] }}</span>
                                        <span class="progress-stat-value">{{ $metric['count'] ?? false ? $metric['value'] : number_format($metric['value'], 1) }}{{ ($metric['grade'] ?? false) || !($metric['count'] ?? false) ? '%' : '' }}</span>
                                        @if(!($metric['count'] ?? false))
                                            <div class="progress-track" role="progressbar" aria-label="{{ $metric['label'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ min(100, max(0, $metric['value'])) }}"><span style="width:{{ min(100, max(0, $metric['value'])) }}%"></span></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                <section class="portal-card" aria-labelledby="activity-title">
                    <div class="portal-card-head"><h3 id="activity-title"><i class="fa-solid fa-calendar-days"></i> Upcoming activities</h3></div>
                    <div class="portal-card-body">
                        @if(isset($upcomingActivities) && $upcomingActivities->isNotEmpty())
                            <div class="activity-list">
                                @foreach($upcomingActivities as $activity)
                                    <div class="activity-item">
                                        <i class="fa-solid {{ $activity['type'] === 'Assignment' ? 'fa-file-pen' : ($activity['type'] === 'Quiz' ? 'fa-circle-question' : 'fa-video') }}" aria-hidden="true"></i>
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
            </div>

            <aside>
                <section class="portal-card" aria-labelledby="resources-title">
                    <div class="portal-card-head"><h3 id="resources-title"><i class="fa-solid fa-rocket"></i> Quick access</h3></div>
                    <div class="portal-card-body">
                        <div class="resource-grid">
                            <a class="resource-card" href="{{ route('student.courses.show', $course) }}" aria-label="Open course overview"><span class="resource-icon"><i class="fa-solid fa-book-open"></i></span><span><strong>Course overview</strong><small>Modules and course details</small></span></a>
                            <a class="resource-card" href="{{ route('student.courses.assignments.index', $course) }}" aria-label="Open course assignments"><span class="resource-icon"><i class="fa-solid fa-file-pen"></i></span><span><strong>Assignments</strong><small>View and submit work</small></span></a>
                            <a class="resource-card" href="{{ route('student.courses.quizzes.index', $course) }}" aria-label="Open course quizzes"><span class="resource-icon"><i class="fa-solid fa-circle-question"></i></span><span><strong>Quizzes</strong><small>Check available quizzes</small></span></a>
                            <a class="resource-card" href="{{ route('student.classes.virtual_classes.index', $class) }}" aria-label="Open virtual classes"><span class="resource-icon"><i class="fa-solid fa-video"></i></span><span><strong>Virtual classes</strong><small>View live sessions</small></span></a>
                            <a class="resource-card" href="{{ route('student.courses.discussions.index', $course) }}" aria-label="Open course discussions"><span class="resource-icon"><i class="fa-solid fa-comments"></i></span><span><strong>Discussions</strong><small>Join the conversation</small></span></a>
                            <a class="resource-card" href="{{ route('student.classes.gradebook.index', $class) }}" aria-label="Open class gradebook"><span class="resource-icon"><i class="fa-solid fa-chart-bar"></i></span><span><strong>Gradebook</strong><small>Review released grades</small></span></a>
                        </div>
                    </div>
                </section>

                <section class="portal-card" aria-labelledby="announcement-title">
                    <div class="portal-card-head"><h3 id="announcement-title"><i class="fa-solid fa-bullhorn"></i> Announcements</h3></div>
                    <div class="portal-card-body">
                        @if(isset($announcements) && $announcements->isNotEmpty())
                            @foreach($announcements as $announcement)
                                <article class="announcement-item"><strong>{{ $announcement->title }}</strong><small>{{ $announcement->created_at?->format('M d, Y g:i A') }}</small><p>{{ Str::limit($announcement->content ?? $announcement->body ?? '', 150) }}</p></article>
                            @endforeach
                        @else
                            <div class="empty-state"><i class="fa-solid fa-bullhorn"></i>No recent announcements</div>
                        @endif
                    </div>
                </section>

                @if($isEnrolled)
                    <section class="portal-card" aria-labelledby="enrollment-title">
                        <div class="portal-card-head"><h3 id="enrollment-title"><i class="fa-solid fa-user-check"></i> Enrollment</h3></div>
                        <div class="portal-card-body">
                            <dl class="meta-grid">
                                <div class="meta-row"><dt>Status</dt><dd><span class="hero-status {{ $statusClass }}">{{ ucfirst($enrollment->status) }}</span></dd></div>
                                <div class="meta-row"><dt>Enrolled</dt><dd>{{ $enrollment->enrolled_at?->format('M d, Y') ?? '—' }}</dd></div>
                                @if($enrollment->final_grade !== null)<div class="meta-row"><dt>Final grade</dt><dd>{{ $enrollment->final_grade }}%</dd></div>@endif
                                @if($enrollment->completed_at)<div class="meta-row"><dt>Completed</dt><dd>{{ $enrollment->completed_at->format('M d, Y') }}</dd></div>@endif
                            </dl>
                        </div>
                    </section>
                @endif
            </aside>
        </div>

        <div class="action-row">
            @if($isEnrolled && $enrollment?->status === 'active')
                <form action="{{ route('student.classes.drop', $class) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to drop this class?');"><i class="fa-solid fa-user-minus"></i> Drop class</button>
                </form>
            @elseif(!$isEnrolled && $class->is_active && !$isFull)
                <form action="{{ route('student.classes.enroll', $class) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-user-plus"></i> Enroll in class</button>
                </form>
            @elseif($isFull)
                <button type="button" class="btn btn-secondary" disabled><i class="fa-solid fa-ban"></i> Class full</button>
            @else
                <button type="button" class="btn btn-secondary" disabled><i class="fa-solid fa-ban"></i> Class unavailable</button>
            @endif
            <a href="{{ route('student.classes.index') }}" class="btn btn-secondary">Back to classes</a>
        </div>
    </div>
@endsection

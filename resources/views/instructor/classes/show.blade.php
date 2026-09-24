@extends('layouts.instructor')

@section('title', $class->code . ' — Class Details')
@php
    $activeNav = 'classes';
    $pageTitle = $class->code . ' — Class Details';
    $pageIcon = '<i class="fa-solid fa-school"></i>';
    $enrollments = $class->enrollments ?? collect();
    $assignments = $class->assignments ?? collect();
    $quizzes = $class->quizzes ?? collect();
    $events = $class->calendarEvents ?? collect();
    $capacity = $class->max_students ?? $class->capacity ?? null;
    $enrolledCount = $enrollments->count();
    $capacityPct = $capacity > 0 ? min(round(($enrolledCount / $capacity) * 100), 100) : 0;
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-school"></i>
            {{ $class->code }} — Class Details
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.classes.gradebook.index', $class) }}" class="btn-add" style="padding: 10px 16px; font-size: .78rem;">
                <i class="fa-solid fa-graduation-cap"></i>
                Open Gradebook
            </a>
            <a href="{{ route('instructor.classes.index') }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Classes
            </a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .cls-hero {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 18px;
            padding: 24px 28px; margin-bottom: 22px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 18px;
            background:
                radial-gradient(circle at 88% 10%, rgba(139,92,246,.32), transparent 42%),
                radial-gradient(circle at 60% 130%, rgba(98,201,245,.12), transparent 45%),
                linear-gradient(135deg, rgba(88,28,135,.85), rgba(10,16,32,.97));
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
        }
        .cls-hero:before {
            content: ""; position: absolute; inset: auto -60px -110px auto;
            width: 280px; height: 200px; border-radius: 50%;
            background: rgba(196,181,253,.1); filter: blur(7px); pointer-events: none;
        }
        .cls-icon {
            width: 56px; height: 56px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: #fff; font-size: 24px; border-radius: 15px;
            box-shadow: 0 12px 26px rgba(139,92,246,.4);
        }
        .cls-main { position: relative; z-index: 1; min-width: 0; }
        .cls-kicker {
            display: block; color: #c4b5fd;
            font-size: .64rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; margin-bottom: 5px;
        }
        .cls-main h3 { margin: 0; color: #fff; font-size: 1.3rem; font-weight: 800; letter-spacing: -.02em; }
        .cls-main p { margin: 4px 0 0; color: #d4e2fa; font-size: .82rem; }
        .cls-status {
            margin-left: auto; flex: none;
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 999px;
            font-size: .76rem; font-weight: 800; letter-spacing: .05em;
            background: rgba(52,211,153,.15); color: #6ee7b7; border: 1px solid rgba(52,211,153,.32);
        }
        .cls-status.inactive { background: rgba(148,163,184,.15); color: #cbd5e1; border-color: rgba(148,163,184,.32); }

        .cls-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .cls-stat {
            display: flex; align-items: center; gap: 13px;
            padding: 15px 17px;
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 14px;
            box-shadow: var(--bcp-shadow, 0 10px 26px rgba(3,8,20,.22));
            transition: transform .18s, border-color .18s;
        }
        .cls-stat:hover { transform: translateY(-2px); border-color: rgba(139,92,246,.4); }
        .cls-stat-icon {
            width: 40px; height: 40px; flex: none; border-radius: 11px;
            display: grid; place-items: center; font-size: 16px;
        }
        .csi-violet { background: rgba(139,92,246,.16); color: #c4b5fd; }
        .csi-blue   { background: rgba(59,130,246,.15); color: #93c5fd; }
        .csi-amber  { background: rgba(251,191,36,.15); color: #fcd34d; }
        .csi-green  { background: rgba(52,211,153,.15); color: #6ee7b7; }
        .csi-cyan   { background: rgba(34,211,238,.14); color: #67e8f9; }
        .cls-stat-body { min-width: 0; }
        .cls-stat-label { display: block; color: var(--bcp-muted, #98a7c4); font-size: .62rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .cls-stat-value { display: block; color: var(--bcp-ink, #eef4ff); font-size: 1.05rem; font-weight: 800; margin-top: 2px; }
        .cls-stat-sub { display: block; color: var(--bcp-muted, #98a7c4); font-size: .66rem; margin-top: 1px; }

        .cls-card {
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 16px; overflow: hidden;
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
            margin-bottom: 22px;
        }
        .cls-card-head {
            display: flex; align-items: center; gap: 10px;
            padding: 15px 22px;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: linear-gradient(90deg, rgba(139,92,246,.1), transparent);
        }
        .cls-card-head h4 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .8rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .cls-card-head h4 i { color: #a78bfa; font-size: .8rem; }
        .cls-card-head .view-link {
            margin-left: auto; color: #a78bfa; font-size: .72rem; font-weight: 700; text-decoration: none;
        }
        .cls-card-head .view-link:hover { text-decoration: underline; }

        .cls-rows { padding: 8px 22px; }
        .cls-row {
            display: flex; justify-content: space-between; align-items: center; gap: 18px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(153,174,214,.08);
        }
        .cls-row:last-child { border-bottom: 0; }
        .cls-row-label { color: var(--bcp-muted, #98a7c4); font-size: .78rem; font-weight: 650; flex: none; }
        .cls-row-value { color: var(--bcp-ink, #eef4ff); font-size: .84rem; font-weight: 650; text-align: right; min-width: 0; }

        /* Capacity bar */
        .cap-wrap { display: flex; align-items: center; gap: 12px; }
        .cap-track {
            flex: 1; height: 8px; border-radius: 999px;
            background: rgba(153,174,214,.12); overflow: hidden;
        }
        .cap-fill { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8b5cf6, #a78bfa); transition: width .3s; }
        .cap-num { flex: none; font-size: .8rem; font-weight: 750; color: var(--bcp-ink, #eef4ff); min-width: 52px; text-align: right; }

        /* Enrolled students table */
        .cls-table-wrap { padding: 6px 0; overflow-x: auto; }
        .cls-table { width: 100%; border-collapse: collapse; }
        .cls-table thead th {
            text-align: left; padding: 10px 22px;
            color: var(--bcp-muted, #98a7c4); font-size: .66rem; font-weight: 800;
            letter-spacing: .07em; text-transform: uppercase;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            white-space: nowrap;
        }
        .cls-table tbody td {
            padding: 12px 22px;
            color: var(--bcp-ink, #eef4ff); font-size: .82rem;
            border-bottom: 1px solid rgba(153,174,214,.07);
        }
        .cls-table tbody tr { transition: background .14s; }
        .cls-table tbody tr:hover { background: rgba(139,92,246,.06); }
        .cls-table tbody tr:last-child td { border-bottom: 0; }
        .cls-student-name { display: flex; align-items: center; gap: 10px; }
        .cls-avatar {
            width: 30px; height: 30px; flex: none; border-radius: 50%;
            display: grid; place-items: center;
            background: rgba(139,92,246,.16); color: #c4b5fd;
            font-size: .68rem; font-weight: 800; text-transform: uppercase;
        }
        .cls-empty {
            text-align: center; padding: 38px 20px;
            color: var(--bcp-muted, #98a7c4); font-size: .8rem;
        }
        .cls-empty i { display: block; font-size: 2rem; margin-bottom: 10px; color: rgba(153,174,214,.35); }

        /* Event mini list */
        .ev-list { padding: 8px 22px; }
        .ev-item {
            display: flex; align-items: center; gap: 13px;
            padding: 10px 0; border-bottom: 1px solid rgba(153,174,214,.07);
        }
        .ev-item:last-child { border-bottom: 0; }
        .ev-date {
            flex: none; width: 44px; text-align: center;
            padding: 6px 0; border-radius: 9px;
            background: rgba(139,92,246,.13); color: #c4b5fd;
        }
        .ev-day { display: block; font-size: .95rem; font-weight: 800; line-height: 1; }
        .ev-mon { display: block; font-size: .6rem; font-weight: 750; text-transform: uppercase; margin-top: 2px; }
        .ev-body { min-width: 0; }
        .ev-title { display: block; color: var(--bcp-ink, #eef4ff); font-size: .84rem; font-weight: 700; }
        .ev-meta { display: block; color: var(--bcp-muted, #98a7c4); font-size: .7rem; margin-top: 2px; }

        @media (max-width: 760px) {
            .cls-hero { flex-wrap: wrap; padding: 18px 20px; }
            .cls-status { margin-left: 0; }
            .cls-stats { grid-template-columns: 1fr 1fr; }
        }
    </style>

    {{-- ═══ HERO ═══ --}}
    <section class="cls-hero">
        <div class="cls-icon"><i class="fa-solid fa-school"></i></div>
        <div class="cls-main">
            <span class="cls-kicker"><i class="fa-solid fa-grid"></i> Class details</span>
            <h3>{{ $class->code }} — {{ $class->course->name ?? $class->course->title ?? '' }}</h3>
            <p>{{ $class->course->code ?? '' }} · {{ $class->schedule ?? 'No schedule' }} · {{ $class->room ?? 'No room' }}</p>
        </div>
        <span class="cls-status {{ ($class->status ?? 'active') !== 'active' ? 'inactive' : '' }}">
            <i class="fa-solid {{ ($class->status ?? 'active') === 'active' ? 'fa-circle-check' : 'fa-circle-xmark' }}"></i>
            {{ ucfirst($class->status ?? 'active') }}
        </span>
    </section>

    {{-- ═══ STAT TILES ═══ --}}
    <div class="cls-stats">
        <div class="cls-stat">
            <span class="cls-stat-icon csi-violet"><i class="fa-solid fa-user-graduate"></i></span>
            <div class="cls-stat-body">
                <span class="cls-stat-label">Students</span>
                <span class="cls-stat-value">{{ $enrolledCount }}</span>
                <span class="cls-stat-sub">enrolled</span>
            </div>
        </div>
        <div class="cls-stat">
            <span class="cls-stat-icon csi-blue"><i class="fa-solid fa-file-pen"></i></span>
            <div class="cls-stat-body">
                <span class="cls-stat-label">Assignments</span>
                <span class="cls-stat-value">{{ $assignments->count() }}</span>
                <span class="cls-stat-sub">total</span>
            </div>
        </div>
        <div class="cls-stat">
            <span class="cls-stat-icon csi-cyan"><i class="fa-solid fa-circle-question"></i></span>
            <div class="cls-stat-body">
                <span class="cls-stat-label">Quizzes</span>
                <span class="cls-stat-value">{{ $quizzes->count() }}</span>
                <span class="cls-stat-sub">total</span>
            </div>
        </div>
        <div class="cls-stat">
            <span class="cls-stat-icon csi-amber"><i class="fa-solid fa-calendar"></i></span>
            <div class="cls-stat-body">
                <span class="cls-stat-label">Events</span>
                <span class="cls-stat-value">{{ $events->count() }}</span>
                <span class="cls-stat-sub">scheduled</span>
            </div>
        </div>
        <div class="cls-stat">
            <span class="cls-stat-icon csi-green"><i class="fa-solid fa-users"></i></span>
            <div class="cls-stat-body">
                <span class="cls-stat-label">Capacity</span>
                <span class="cls-stat-value">{{ $capacity ? $capacityPct . '%' : '—' }}</span>
                <span class="cls-stat-sub">{{ $enrolledCount }} / {{ $capacity ?? '∞' }} used</span>
            </div>
        </div>
    </div>

    <div class="cls-split" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:22px;margin-bottom:22px;">
        {{-- ═══ CLASS INFO ═══ --}}
        <div class="cls-card" style="margin-bottom:0;">
            <div class="cls-card-head">
                <h4><i class="fa-solid fa-circle-info"></i> Class Information</h4>
            </div>
            <div class="cls-rows">
                <div class="cls-row"><span class="cls-row-label">Code</span><span class="cls-row-value">{{ $class->code }}</span></div>
                <div class="cls-row"><span class="cls-row-label">Course</span><span class="cls-row-value">{{ $class->course->name ?? $class->course->title ?? '—' }}</span></div>
                <div class="cls-row"><span class="cls-row-label">Academic Period</span><span class="cls-row-value">{{ $class->academicPeriod->name ?? '—' }}</span></div>
                <div class="cls-row"><span class="cls-row-label">Schedule</span><span class="cls-row-value">{{ $class->schedule ?? 'Not specified' }}</span></div>
                <div class="cls-row"><span class="cls-row-label">Room</span><span class="cls-row-value">{{ $class->room ?? 'Not specified' }}</span></div>
                <div class="cls-row">
                    <span class="cls-row-label">Capacity</span>
                    <span class="cap-wrap" style="flex:1;max-width:220px;">
                        <span class="cap-track"><span class="cap-fill" style="width: {{ $capacityPct }}%"></span></span>
                        <span class="cap-num">{{ $enrolledCount }}/{{ $capacity ?? '∞' }}</span>
                    </span>
                </div>
                @if($class->description)
                    <div class="cls-row" style="flex-direction:column;align-items:flex-start;gap:4px;">
                        <span class="cls-row-label">Description</span>
                        <span class="cls-row-value" style="text-align:left;">{{ $class->description }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══ UPCOMING EVENTS ═══ --}}
        <div class="cls-card" style="margin-bottom:0;">
            <div class="cls-card-head">
                <h4><i class="fa-solid fa-calendar-days"></i> Upcoming Events</h4>
                <a href="{{ route('instructor.classes.calendar.index', $class) }}" class="view-link">View calendar →</a>
            </div>
            @if($events->isNotEmpty())
                <div class="ev-list">
                    @foreach($events->take(5) as $event)
                        <div class="ev-item">
                            <span class="ev-date">
                                <span class="ev-day">{{ $event->start_at?->format('j') ?? $event->date?->format('j') ?? '—' }}</span>
                                <span class="ev-mon">{{ $event->start_at?->format('M') ?? $event->date?->format('M') ?? '' }}</span>
                            </span>
                            <div class="ev-body">
                                <span class="ev-title">{{ $event->title }}</span>
                                <span class="ev-meta">{{ $event->start_at?->format('g:i A') ?? '' }}{{ $event->type ? ' · ' . ucfirst($event->type) : '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="cls-empty">
                    <i class="fa-solid fa-calendar-days"></i>
                    No upcoming events scheduled.
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ ENROLLED STUDENTS ═══ --}}
    <div class="cls-card">
        <div class="cls-card-head">
            <h4><i class="fa-solid fa-user-graduate"></i> Enrolled Students ({{ $enrolledCount }})</h4>
            <a href="{{ route('instructor.enrollments.create') }}" class="view-link"><i class="fa-solid fa-plus"></i> Enroll student</a>
        </div>
        @if($enrollments->isNotEmpty())
            <div class="cls-table-wrap">
                <table class="cls-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Enrolled Date</th>
                            <th>Grade</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrollments as $enrollment)
                            <tr>
                                <td>
                                    <span class="cls-student-name">
                                        <span class="cls-avatar">{{ substr($enrollment->student->full_name ?? 'U', 0, 1) }}</span>
                                        {{ $enrollment->student->full_name ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $enrollment->student->email ?? '—' }}</td>
                                <td>
                                    @php
                                        $bg = $enrollment->status === 'active' ? 'background:rgba(52,211,153,.14);color:#6ee7b7'
                                            : ($enrollment->status === 'completed' ? 'background:rgba(98,201,245,.14);color:#7dd3fc'
                                            : ($enrollment->status === 'pending' ? 'background:rgba(251,191,36,.14);color:#fcd34d'
                                            : 'background:rgba(244,63,94,.14);color:#fda4af'));
                                    @endphp
                                    <span style="padding:3px 11px;border-radius:20px;font-size:.7rem;font-weight:750;{{ $bg }}">{{ ucfirst($enrollment->status) }}</span>
                                </td>
                                <td>{{ $enrollment->enrolled_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    @if($enrollment->final_grade !== null)
                                        <span style="font-weight:750;color:{{ $enrollment->final_grade >= 75 ? '#6ee7b7' : '#fda4af' }}">{{ $enrollment->final_grade }}%</span>
                                    @else
                                        <span style="color:var(--bcp-muted,#98a7c4)">—</span>
                                    @endif
                                </td>
                                <td class="actions-cell" style="white-space:nowrap;">
                                    <a href="{{ route('instructor.enrollments.show', $enrollment) }}" class="btn-icon btn-view" title="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('instructor.enrollments.edit', $enrollment) }}" class="btn-icon btn-edit" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="cls-empty">
                <i class="fa-solid fa-user-graduate"></i>
                No students enrolled in this class yet.
                <div style="margin-top:12px;">
                    <a href="{{ route('instructor.enrollments.create') }}" style="color:#a78bfa;text-decoration:underline;font-weight:650;">Enroll a student</a>
                </div>
            </div>
        @endif
    </div>
@endsection

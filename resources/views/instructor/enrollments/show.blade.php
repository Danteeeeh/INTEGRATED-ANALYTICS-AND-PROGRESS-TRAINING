@extends('layouts.instructor')

@section('title', 'Enrollment Details')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Enrollment Details';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
    $statusMeta = [
        'active' => ['label' => 'Active', 'cls' => 'st-active', 'icon' => 'fa-circle-check'],
        'pending' => ['label' => 'Pending', 'cls' => 'st-pending', 'icon' => 'fa-hourglass-half'],
        'completed' => ['label' => 'Completed', 'cls' => 'st-completed', 'icon' => 'fa-graduation-cap'],
        'dropped' => ['label' => 'Dropped', 'cls' => 'st-dropped', 'icon' => 'fa-circle-xmark'],
    ];
    $st = $statusMeta[$enrollment->status] ?? ['label' => ucfirst($enrollment->status ?? 'Unknown'), 'cls' => 'st-dropped', 'icon' => 'fa-circle-question'];
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enrollment Details
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.enrollments.index') }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Enrollments
            </a>
            <a href="{{ route('instructor.enrollments.edit', $enrollment) }}" class="btn-add">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Enrollment
            </a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        /* Enrollment show — BCP design */
        .enr-hero {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 18px;
            padding: 24px 28px; margin-bottom: 22px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 18px;
            background:
                radial-gradient(circle at 88% 10%, rgba(77,143,240,.35), transparent 42%),
                radial-gradient(circle at 60% 130%, rgba(98,201,245,.12), transparent 45%),
                linear-gradient(135deg, rgba(23,58,168,.9), rgba(10,16,32,.97));
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
        }
        .enr-hero:before {
            content: ""; position: absolute; inset: auto -60px -110px auto;
            width: 280px; height: 200px; border-radius: 50%;
            background: rgba(98,201,245,.11); filter: blur(7px); pointer-events: none;
        }
        .enr-avatar {
            width: 60px; height: 60px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #2449c6, #4d8ff0);
            color: #fff; font-size: 24px; font-weight: 800;
            border-radius: 16px; letter-spacing: .02em;
            box-shadow: 0 12px 26px rgba(36,73,198,.4);
            text-transform: uppercase;
        }
        .enr-main { position: relative; z-index: 1; min-width: 0; }
        .enr-kicker {
            display: block; color: var(--bcp-cyan-400, #62c9f5);
            font-size: .64rem; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; margin-bottom: 5px;
        }
        .enr-main h3 { margin: 0; color: #fff; font-size: 1.25rem; font-weight: 800; letter-spacing: -.02em; }
        .enr-main p { margin: 4px 0 0; color: #d4e2fa; font-size: .82rem; }
        .enr-status {
            margin-left: auto; flex: none;
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 16px; border-radius: 999px;
            font-size: .76rem; font-weight: 800; letter-spacing: .05em; white-space: nowrap;
        }
        .st-active    { background: rgba(52,211,153,.15); color: #6ee7b7; border: 1px solid rgba(52,211,153,.32); }
        .st-pending   { background: rgba(251,191,36,.15); color: #fcd34d; border: 1px solid rgba(251,191,36,.32); }
        .st-completed { background: rgba(98,201,245,.15); color: #7dd3fc; border: 1px solid rgba(98,201,245,.32); }
        .st-dropped   { background: rgba(244,63,94,.15); color: #fda4af; border: 1px solid rgba(244,63,94,.32); }

        .enr-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 22px; }
        .enr-stat {
            display: flex; align-items: center; gap: 13px;
            padding: 16px 18px;
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 14px;
            box-shadow: var(--bcp-shadow, 0 10px 26px rgba(3,8,20,.22));
            transition: transform .18s, border-color .18s;
        }
        .enr-stat:hover { transform: translateY(-2px); border-color: rgba(98,201,245,.35); }
        .enr-stat-icon {
            width: 42px; height: 42px; flex: none; border-radius: 11px;
            display: grid; place-items: center; font-size: 17px;
        }
        .esi-blue   { background: rgba(59,130,246,.15); color: #93c5fd; }
        .esi-green  { background: rgba(52,211,153,.15); color: #6ee7b7; }
        .esi-violet { background: rgba(139,92,246,.16); color: #c4b5fd; }
        .esi-amber  { background: rgba(251,191,36,.15); color: #fcd34d; }
        .esi-rose   { background: rgba(244,63,94,.15); color: #fda4af; }
        .enr-stat-body { min-width: 0; }
        .enr-stat-label { display: block; color: var(--bcp-muted, #98a7c4); font-size: .64rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .enr-stat-value { display: block; color: var(--bcp-ink, #eef4ff); font-size: 1.02rem; font-weight: 750; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .enr-card {
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 16px; overflow: hidden;
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
            margin-bottom: 22px;
        }
        .enr-card-head {
            display: flex; align-items: center; gap: 10px;
            padding: 15px 22px;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: linear-gradient(90deg, rgba(77,143,240,.1), transparent);
        }
        .enr-card-head h4 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .8rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .enr-card-head h4 i { color: var(--bcp-cyan-400, #62c9f5); font-size: .8rem; }

        .enr-rows { padding: 8px 22px; }
        .enr-row {
            display: flex; justify-content: space-between; align-items: center; gap: 18px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(153,174,214,.08);
        }
        .enr-row:last-child { border-bottom: 0; }
        .enr-row-label { color: var(--bcp-muted, #98a7c4); font-size: .78rem; font-weight: 650; flex: none; }
        .enr-row-value { color: var(--bcp-ink, #eef4ff); font-size: .84rem; font-weight: 650; text-align: right; min-width: 0; word-break: break-word; }

        .grade-chip {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 999px;
            font-size: .78rem; font-weight: 800;
        }
        .grade-high { background: rgba(52,211,153,.14); color: #6ee7b7; }
        .grade-mid  { background: rgba(251,191,36,.14); color: #fcd34d; }
        .grade-low  { background: rgba(244,63,94,.14); color: #fda4af; }
        .grade-none { background: rgba(148,163,184,.14); color: #cbd5e1; }

        .enr-notes { padding: 16px 22px 20px; }
        .enr-notes p { margin: 0; color: #c7d4ec; font-size: .85rem; line-height: 1.6; white-space: pre-wrap; }

        /* Split performance layout */
        .enr-split {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 22px; margin-bottom: 22px;
        }
        .enr-head-badge {
            margin-left: auto; flex: none;
            padding: 4px 12px; border-radius: 999px;
            font-size: .7rem; font-weight: 800;
        }
        .bh-green { background: rgba(52,211,153,.14); color: #6ee7b7; }
        .bh-amber { background: rgba(251,191,36,.14); color: #fcd34d; }
        .bh-rose  { background: rgba(244,63,94,.14); color: #fda4af; }

        .enr-empty {
            text-align: center; padding: 34px 20px;
            color: var(--bcp-muted, #98a7c4); font-size: .8rem;
        }
        .enr-empty i {
            display: block; font-size: 1.9rem; margin-bottom: 10px;
            color: rgba(153,174,214,.35);
        }

        /* Attendance bars */
        .att-bars { display: flex; flex-direction: column; gap: 10px; padding: 16px 22px; }
        .att-bar-row { display: flex; align-items: center; gap: 12px; }
        .att-label { flex: none; width: 74px; font-size: .74rem; font-weight: 700; color: var(--bcp-muted, #98a7c4); }
        .att-track {
            flex: 1; height: 8px; border-radius: 999px;
            background: rgba(153,174,214,.12); overflow: hidden;
        }
        .att-fill { display: block; height: 100%; border-radius: 999px; transition: width .3s ease; }
        .att-present { background: linear-gradient(90deg, #10b981, #34d399); }
        .att-late    { background: linear-gradient(90deg, #d97706, #fbbf24); }
        .att-absent  { background: linear-gradient(90deg, #e11d48, #fb7185); }
        .att-count { flex: none; width: 24px; text-align: right; font-size: .8rem; font-weight: 750; color: var(--bcp-ink, #eef4ff); }

        /* Timeline */
        .enr-timeline { padding: 18px 22px; display: flex; flex-direction: column; gap: 0; }
        .tl-item { display: flex; gap: 14px; position: relative; padding-bottom: 22px; }
        .tl-item:last-child { padding-bottom: 0; }
        .tl-item:not(:last-child):before {
            content: ""; position: absolute; left: 17px; top: 34px; bottom: 2px;
            width: 2px; background: rgba(153,174,214,.16);
        }
        .tl-dot {
            width: 36px; height: 36px; flex: none; border-radius: 50%;
            display: grid; place-items: center; font-size: 13px;
            z-index: 1;
        }
        .tl-done    { background: rgba(52,211,153,.15); color: #6ee7b7; border: 2px solid rgba(52,211,153,.35); }
        .tl-pending { background: rgba(251,191,36,.15); color: #fcd34d; border: 2px solid rgba(251,191,36,.35); }
        .tl-dropped { background: rgba(244,63,94,.15); color: #fda4af; border: 2px solid rgba(244,63,94,.35); }
        .tl-body { display: flex; flex-direction: column; gap: 2px; padding-top: 3px; }
        .tl-title { color: var(--bcp-ink, #eef4ff); font-size: .84rem; font-weight: 750; }
        .tl-date { color: var(--bcp-muted, #98a7c4); font-size: .72rem; }

        /* Quick actions */
        .qa-wrap { padding: 18px 22px; display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
        .qa-form { display: flex; align-items: center; gap: 10px; }
        .qa-form.qa-inline { margin-left: auto; }
        .qa-field { display: flex; flex-direction: column; gap: 4px; }
        .qa-field label { color: var(--bcp-muted, #98a7c4); font-size: .68rem; font-weight: 750; }
        .qa-field input {
            width: 120px; min-height: 36px; padding: 7px 10px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 8px;
            background: #101625; color: var(--bcp-ink, #eef4ff); font-size: .84rem;
        }
        .qa-field input:focus { outline: 0; border-color: var(--bcp-blue-500, #4d8ff0); box-shadow: 0 0 0 3px rgba(77,143,240,.15); }
        .qa-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; border-radius: 9px; border: 1px solid;
            font-size: .78rem; font-weight: 750; cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
            text-decoration: none;
        }
        .qa-btn:hover { transform: translateY(-1px); }
        .qa-complete {
            color: #fff; background: linear-gradient(135deg, #10b981, #059669);
            border-color: #059669; box-shadow: 0 6px 16px rgba(16,185,129,.28);
        }
        .qa-complete:hover { background: linear-gradient(135deg, #34d399, #10b981); }
        .qa-drop {
            color: #fda4af; background: rgba(244,63,94,.12);
            border-color: rgba(244,63,94,.35);
        }
        .qa-drop:hover { background: rgba(244,63,94,.22); }
        .qa-activate {
            color: #fff; background: linear-gradient(135deg, #2449c6, #173aa8);
            border-color: #3159d1; box-shadow: 0 6px 16px rgba(36,73,198,.28);
        }
        .qa-activate:hover { background: linear-gradient(135deg, #4d8ff0, #2449c6); }

        @media (max-width: 640px) {
            .enr-hero { flex-wrap: wrap; padding: 18px 20px; }
            .enr-status { margin-left: 0; }
            .enr-stats { grid-template-columns: 1fr 1fr; }
            .qa-wrap { flex-direction: column; align-items: stretch; }
            .qa-form, .qa-form.qa-inline { flex-direction: column; margin-left: 0; }
            .qa-field input { width: 100%; }
            .qa-btn { justify-content: center; }
        }
    </style>

    {{-- ═══ HERO ═══ --}}
    <section class="enr-hero">
        <div class="enr-avatar">{{ substr($enrollment->student->full_name ?? 'U', 0, 1) }}</div>
        <div class="enr-main">
            <span class="enr-kicker"><i class="fa-solid fa-graduation-cap"></i> Enrollment record</span>
            <h3>{{ $enrollment->student->full_name ?? 'Student' }}</h3>
            <p>{{ $enrollment->student->email ?? '' }} · {{ $enrollment->class->code ?? '' }}</p>
        </div>
        <span class="enr-status {{ $st['cls'] }}"><i class="fa-solid {{ $st['icon'] }}"></i> {{ $st['label'] }}</span>
    </section>

    {{-- ═══ STAT TILES ═══ --}}
    <div class="enr-stats">
        <div class="enr-stat">
            <span class="enr-stat-icon esi-blue"><i class="fa-solid fa-school"></i></span>
            <div class="enr-stat-body">
                <span class="enr-stat-label">Class</span>
                <span class="enr-stat-value">{{ $enrollment->class->code ?? '—' }}</span>
            </div>
        </div>
        <div class="enr-stat">
            <span class="enr-stat-icon esi-violet"><i class="fa-solid fa-book"></i></span>
            <div class="enr-stat-body">
                <span class="enr-stat-label">Course</span>
                <span class="enr-stat-value">{{ $enrollment->class->course->name ?? '—' }}</span>
            </div>
        </div>
        <div class="enr-stat">
            <span class="enr-stat-icon esi-amber"><i class="fa-solid fa-calendar"></i></span>
            <div class="enr-stat-body">
                <span class="enr-stat-label">Period</span>
                <span class="enr-stat-value">{{ $enrollment->class->academicPeriod->name ?? '—' }}</span>
            </div>
        </div>
        @if($enrollment->final_grade !== null)
            <div class="enr-stat">
                <span class="enr-stat-icon {{ $enrollment->final_grade >= 75 ? 'esi-green' : 'esi-rose' }}"><i class="fa-solid fa-star"></i></span>
                <div class="enr-stat-body">
                    <span class="enr-stat-label">Final Grade</span>
                    <span class="enr-stat-value">{{ $enrollment->final_grade }}%</span>
                </div>
            </div>
        @endif
    </div>

    {{-- ═══ STUDENT INFO ═══ --}}
    <div class="enr-card">
        <div class="enr-card-head">
            <h4><i class="fa-solid fa-user"></i> Student Information</h4>
        </div>
        <div class="enr-rows">
            <div class="enr-row"><span class="enr-row-label">Full Name</span><span class="enr-row-value">{{ $enrollment->student->full_name ?? '—' }}</span></div>
            <div class="enr-row"><span class="enr-row-label">Email</span><span class="enr-row-value">{{ $enrollment->student->email ?? '—' }}</span></div>
            @if($enrollment->student->identifier ?? null)
                <div class="enr-row"><span class="enr-row-label">Student ID</span><span class="enr-row-value">{{ $enrollment->student->identifier }}</span></div>
            @endif
        </div>
    </div>

    {{-- ═══ ENROLLMENT DETAILS ═══ --}}
    <div class="enr-card">
        <div class="enr-card-head">
            <h4><i class="fa-solid fa-info-circle"></i> Enrollment Details</h4>
        </div>
        <div class="enr-rows">
            <div class="enr-row">
                <span class="enr-row-label">Status</span>
                <span class="enr-row-value">
                    <span class="grade-chip {{ $st['cls'] }}"><i class="fa-solid {{ $st['icon'] }}"></i> {{ $st['label'] }}</span>
                </span>
            </div>
            <div class="enr-row">
                <span class="enr-row-label">Final Grade</span>
                <span class="enr-row-value">
                    @if($enrollment->final_grade !== null)
                        <span class="grade-chip {{ $enrollment->final_grade >= 75 ? 'grade-high' : ($enrollment->final_grade >= 60 ? 'grade-mid' : 'grade-low') }}">{{ $enrollment->final_grade }}%</span>
                    @else
                        <span class="grade-chip grade-none">Not graded</span>
                    @endif
                </span>
            </div>
            <div class="enr-row"><span class="enr-row-label">Enrolled Date</span><span class="enr-row-value">{{ $enrollment->enrolled_at?->format('M d, Y g:i A') ?? '—' }}</span></div>
            @if($enrollment->completed_at)
                <div class="enr-row"><span class="enr-row-label">Completed Date</span><span class="enr-row-value">{{ $enrollment->completed_at->format('M d, Y g:i A') }}</span></div>
            @endif
        </div>
    </div>

    {{-- ═══ CLASS INFO + TIMELINE ═══ --}}
    <div class="enr-split">
        {{-- Class information --}}
        <div class="enr-card" style="margin-bottom:0;">
            <div class="enr-card-head">
                <h4><i class="fa-solid fa-school"></i> Class Information</h4>
            </div>
            <div class="enr-rows">
                <div class="enr-row"><span class="enr-row-label">Class</span><span class="enr-row-value">{{ $enrollment->class->code ?? '—' }}</span></div>
                <div class="enr-row"><span class="enr-row-label">Course</span><span class="enr-row-value">{{ $enrollment->class->course->name ?? $enrollment->class->course->title ?? '—' }}</span></div>
                @if($enrollment->class->instructor ?? null)
                    <div class="enr-row"><span class="enr-row-label">Instructor</span><span class="enr-row-value">{{ $enrollment->class->instructor->full_name ?? $enrollment->class->instructor->name ?? '—' }}</span></div>
                @endif
                @if($enrollment->class->schedule ?? null)
                    <div class="enr-row"><span class="enr-row-label">Schedule</span><span class="enr-row-value">{{ $enrollment->class->schedule }}</span></div>
                @endif
                @if($enrollment->class->room ?? null)
                    <div class="enr-row"><span class="enr-row-label">Room</span><span class="enr-row-value">{{ $enrollment->class->room }}</span></div>
                @endif
                @if($enrollment->class->capacity ?? null)
                    <div class="enr-row"><span class="enr-row-label">Capacity</span><span class="enr-row-value">{{ $enrollment->class->enrollments_count ?? $enrollment->class->enrollments()->count() }} / {{ $enrollment->class->capacity }}</span></div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <div class="enr-card" style="margin-bottom:0;">
            <div class="enr-card-head">
                <h4><i class="fa-solid fa-clock-rotate-left"></i> Enrollment Timeline</h4>
            </div>
            <div class="enr-timeline">
                <div class="tl-item">
                    <span class="tl-dot tl-done"><i class="fa-solid fa-user-plus"></i></span>
                    <div class="tl-body">
                        <span class="tl-title">Enrolled</span>
                        <span class="tl-date">{{ $enrollment->enrolled_at?->format('M d, Y g:i A') ?? $enrollment->created_at?->format('M d, Y g:i A') ?? '—' }}</span>
                    </div>
                </div>
                @if($enrollment->status === 'completed' || $enrollment->completed_at)
                    <div class="tl-item">
                        <span class="tl-dot tl-done"><i class="fa-solid fa-graduation-cap"></i></span>
                        <div class="tl-body">
                            <span class="tl-title">Completed</span>
                            <span class="tl-date">{{ $enrollment->completed_at?->format('M d, Y g:i A') ?? '—' }}</span>
                        </div>
                    </div>
                @elseif($enrollment->status === 'dropped')
                    <div class="tl-item">
                        <span class="tl-dot tl-dropped"><i class="fa-solid fa-circle-xmark"></i></span>
                        <div class="tl-body">
                            <span class="tl-title">Dropped</span>
                            <span class="tl-date">{{ $enrollment->updated_at?->format('M d, Y g:i A') ?? '—' }}</span>
                        </div>
                    </div>
                @elseif($enrollment->status === 'pending')
                    <div class="tl-item">
                        <span class="tl-dot tl-pending"><i class="fa-solid fa-hourglass-half"></i></span>
                        <div class="tl-body">
                            <span class="tl-title">Pending confirmation</span>
                            <span class="tl-date">Awaiting approval</span>
                        </div>
                    </div>
                @else
                    <div class="tl-item">
                        <span class="tl-dot tl-pending"><i class="fa-solid fa-circle-info"></i></span>
                        <div class="tl-body">
                            <span class="tl-title">In progress</span>
                            <span class="tl-date">Actively enrolled</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ QUICK ACTIONS ═══ --}}
    <div class="enr-card" style="margin-bottom:0;">
        <div class="enr-card-head">
            <h4><i class="fa-solid fa-bolt"></i> Quick Actions</h4>
        </div>
        <div class="qa-wrap">
            @if($enrollment->status === 'active')
                <form method="POST" action="{{ route('instructor.enrollments.complete', $enrollment) }}" class="qa-form" id="completeForm">
                    @csrf
                    <div class="qa-field">
                        <label>Final Grade (%)</label>
                        <input type="number" name="final_grade" min="0" max="100" step="0.01" placeholder="e.g. 88.5" value="{{ old('final_grade', $enrollment->final_grade) }}">
                    </div>
                    <button type="submit" class="qa-btn qa-complete" onclick="return confirm('Mark this enrollment as completed?')">
                        <i class="fa-solid fa-graduation-cap"></i>
                        Mark Completed
                    </button>
                </form>
                <form method="POST" action="{{ route('instructor.enrollments.drop', $enrollment) }}" class="qa-form qa-inline">
                    @csrf
                    <button type="submit" class="qa-btn qa-drop" onclick="return confirm('Drop this student from the class?')">
                        <i class="fa-solid fa-user-slash"></i>
                        Drop Enrollment
                    </button>
                </form>
            @elseif($enrollment->status === 'pending')
                <form method="POST" action="{{ route('instructor.enrollments.activate', $enrollment) }}" class="qa-form qa-inline">
                    @csrf
                    <button type="submit" class="qa-btn qa-activate">
                        <i class="fa-solid fa-circle-check"></i>
                        Approve &amp; Activate
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('instructor.enrollments.activate', $enrollment) }}" class="qa-form qa-inline">
                    @csrf
                    <button type="submit" class="qa-btn qa-activate">
                        <i class="fa-solid fa-rotate-left"></i>
                        Re-activate Enrollment
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- ═══ NOTES ═══ --}}
    @if($enrollment->notes)
        <div class="enr-card">
            <div class="enr-card-head">
                <h4><i class="fa-solid fa-align-left"></i> Notes</h4>
            </div>
            <div class="enr-notes">
                <p>{{ $enrollment->notes }}</p>
            </div>
        </div>
    @endif

    {{-- ═══ PERFORMANCE: GRADES + ATTENDANCE ═══ --}}
    <div class="enr-split">
        {{-- Grades --}}
        <div class="enr-card" style="margin-bottom:0;">
            <div class="enr-card-head">
                <h4><i class="fa-solid fa-star"></i> Grades ({{ $grades->count() }})</h4>
                @if($averageGrade !== null)
                    <span class="enr-head-badge {{ $averageGrade >= 75 ? 'bh-green' : ($averageGrade >= 60 ? 'bh-amber' : 'bh-rose') }}">
                        Avg {{ number_format($averageGrade, 1) }}%
                    </span>
                @endif
            </div>
            <div class="enr-rows">
                @forelse($grades as $grade)
                    <div class="enr-row">
                        <div style="min-width:0;">
                            <div class="enr-row-value" style="text-align:left;font-weight:700;">{{ $grade->item->title ?? 'Grade item' }}</div>
                            <div class="enr-row-label" style="margin-top:2px;">
                                {{ $grade->graded_at?->format('M j, Y') ?? '—' }}
                                @if($grade->item && $grade->item->max_points)
                                    · {{ $grade->points ?? '—' }}/{{ $grade->item->max_points }} pts
                                @endif
                            </div>
                        </div>
                        <span class="enr-row-value">
                            @if($grade->score_percent !== null)
                                <span class="grade-chip {{ $grade->score_percent >= 75 ? 'grade-high' : ($grade->score_percent >= 60 ? 'grade-mid' : 'grade-low') }}">
                                    {{ number_format($grade->score_percent, 1) }}%
                                </span>
                            @else
                                <span class="grade-chip grade-none">—</span>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="enr-empty">
                        <i class="fa-solid fa-star"></i>
                        No grades recorded yet para sa class na ito.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Attendance --}}
        <div class="enr-card" style="margin-bottom:0;">
            <div class="enr-card-head">
                <h4><i class="fa-solid fa-clipboard-check"></i> Attendance ({{ $attendanceSummary['total'] }})</h4>
                @if($attendanceRate !== null)
                    <span class="enr-head-badge {{ $attendanceRate >= 75 ? 'bh-green' : ($attendanceRate >= 50 ? 'bh-amber' : 'bh-rose') }}">
                        {{ $attendanceRate }}% rate
                    </span>
                @endif
            </div>
            @if($attendanceSummary['total'] > 0)
                <div class="att-bars">
                    <div class="att-bar-row">
                        <span class="att-label"><i class="fa-solid fa-circle-check" style="color:#6ee7b7"></i> Present</span>
                        <div class="att-track"><span class="att-fill att-present" style="width: {{ ($attendanceSummary['present'] / max($attendanceSummary['total'],1)) * 100 }}%"></span></div>
                        <span class="att-count">{{ $attendanceSummary['present'] }}</span>
                    </div>
                    <div class="att-bar-row">
                        <span class="att-label"><i class="fa-solid fa-clock" style="color:#fcd34d"></i> Late</span>
                        <div class="att-track"><span class="att-fill att-late" style="width: {{ ($attendanceSummary['late'] / max($attendanceSummary['total'],1)) * 100 }}%"></span></div>
                        <span class="att-count">{{ $attendanceSummary['late'] }}</span>
                    </div>
                    <div class="att-bar-row">
                        <span class="att-label"><i class="fa-solid fa-circle-xmark" style="color:#fda4af"></i> Absent</span>
                        <div class="att-track"><span class="att-fill att-absent" style="width: {{ ($attendanceSummary['absent'] / max($attendanceSummary['total'],1)) * 100 }}%"></span></div>
                        <span class="att-count">{{ $attendanceSummary['absent'] }}</span>
                    </div>
                </div>
                <div class="enr-rows" style="padding-top:0;">
                    @foreach($attendance->take(5) as $record)
                        <div class="enr-row">
                            <span class="enr-row-label">{{ $record->attendance_date?->format('M j, Y') ?? '—' }}{{ $record->session_title ? ' · ' . $record->session_title : '' }}</span>
                            <span class="enr-row-value">
                                <span class="grade-chip {{ $record->status === 'present' ? 'grade-high' : ($record->status === 'late' ? 'grade-mid' : 'grade-low') }}">
                                    {{ ucfirst($record->status ?? 'unknown') }}
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="enr-empty">
                    <i class="fa-solid fa-clipboard-check"></i>
                    No attendance records yet para sa class na ito.
                </div>
            @endif
        </div>
    </div>
@endsection

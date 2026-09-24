@extends('layouts.admin-sms')

@section('title', 'Enrollment Details')

@php
    $activeNav = 'enrollment';
    $statusClass = match ($enrollment->status) {
        'active', 'completed' => 'status-active',
        'dropped' => 'status-cancelled',
        default => 'status-inactive',
    };
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-graduation-cap"></i> Enrollment Details</h2>
        <div class="page-actions">
            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="btn btn-primary"><i class="fa-solid fa-pen-to-square"></i> Edit enrollment</a>
            <a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .enrollment-details { max-width:1000px; margin:0 auto; }
        .enrollment-details .enrollment-hero { display:flex; align-items:center; gap:15px; margin-bottom:18px; padding:21px 23px; border:1px solid var(--dash-line); border-radius:16px; background:radial-gradient(circle at 90% 10%,rgba(139,92,246,.25),transparent 40%),linear-gradient(135deg,rgba(23,58,168,.92),rgba(10,16,32,.97)); box-shadow:var(--bcp-shadow); }
        .enrollment-details .enrollment-icon { display:grid; place-items:center; width:50px; height:50px; flex:none; border-radius:14px; color:#fff; background:linear-gradient(135deg,#8b5cf6,#6d28d9); box-shadow:0 10px 24px rgba(139,92,246,.26); }
        .enrollment-details .enrollment-hero h3 { margin:0; color:#fff; font-size:1.08rem; }
        .enrollment-details .enrollment-hero p { margin:4px 0 0; color:#c8d9f8; font-size:.76rem; }
        .enrollment-details .enrollment-status { margin-left:auto; padding:6px 11px; border-radius:999px; font-size:.68rem; font-weight:800; }
        .enrollment-details .status-active { color:#6ee7b7; background:rgba(16,185,129,.13); }
        .enrollment-details .status-inactive { color:#fcd34d; background:rgba(251,191,36,.13); }
        .enrollment-details .status-cancelled { color:#fda4af; background:rgba(244,63,94,.13); }
        .enrollment-details .detail-card { overflow:hidden; margin-bottom:18px; background:var(--dash-surface); border:1px solid var(--dash-line); border-radius:15px; box-shadow:var(--bcp-shadow); }
        .enrollment-details .detail-card-head { display:flex; align-items:center; gap:9px; padding:15px 19px; border-bottom:1px solid var(--dash-line); background:linear-gradient(90deg,rgba(77,143,240,.1),transparent); }
        .enrollment-details .detail-card-head h3 { margin:0; color:var(--dash-text); font-size:.8rem; letter-spacing:.05em; text-transform:uppercase; }
        .enrollment-details .detail-card-head i { color:var(--dash-cyan); }
        .enrollment-details .detail-card-body { padding:18px 19px; }
        .enrollment-details .meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 26px; }
        .enrollment-details .meta-row { display:flex; justify-content:space-between; gap:16px; padding:12px 0; border-bottom:1px solid var(--dash-line); font-size:.76rem; }
        .enrollment-details .meta-row dt { color:var(--dash-muted); }
        .enrollment-details .meta-row dd { margin:0; color:var(--dash-text); font-weight:700; text-align:right; }
        .enrollment-details .notes { padding:14px; color:var(--dash-text); background:rgba(77,143,240,.05); border:1px solid var(--dash-line); border-radius:10px; font-size:.78rem; line-height:1.55; }
        .enrollment-details .grade-highlight { color:var(--dash-cyan); font-size:.9rem; font-weight:850; }
        @media(max-width:680px) { .enrollment-details .enrollment-hero { align-items:flex-start; } .enrollment-details .enrollment-status { display:none; } .enrollment-details .meta-grid { grid-template-columns:1fr; } }
    </style>

    <div class="enrollment-details">
        <section class="enrollment-hero">
            <span class="enrollment-icon"><i class="fa-solid fa-user-check"></i></span>
            <div>
                <h3>{{ $enrollment->student?->full_name ?? 'Student enrollment' }}</h3>
                <p>{{ $enrollment->class?->code ?? 'Class' }} · {{ $enrollment->class?->course?->title ?? $enrollment->class?->course?->name ?? 'Course' }}</p>
            </div>
            <span class="enrollment-status {{ $statusClass }}">{{ ucfirst($enrollment->status) }}</span>
        </section>

        <section class="detail-card">
            <div class="detail-card-head"><i class="fa-solid fa-user"></i><h3>Student information</h3></div>
            <div class="detail-card-body">
                <dl class="meta-grid">
                    <div class="meta-row"><dt>Student</dt><dd>{{ $enrollment->student?->full_name ?? '—' }}</dd></div>
                    <div class="meta-row"><dt>Email</dt><dd>{{ $enrollment->student?->email ?? '—' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-head"><i class="fa-solid fa-chalkboard-user"></i><h3>Class information</h3></div>
            <div class="detail-card-body">
                <dl class="meta-grid">
                    <div class="meta-row"><dt>Class</dt><dd>{{ $enrollment->class?->code ?? '—' }} — {{ $enrollment->class?->name ?? '—' }}</dd></div>
                    <div class="meta-row"><dt>Course</dt><dd>{{ $enrollment->class?->course?->title ?? $enrollment->class?->course?->name ?? '—' }}</dd></div>
                    <div class="meta-row"><dt>Academic period</dt><dd>{{ $enrollment->class?->academicPeriod?->name ?? '—' }}</dd></div>
                    <div class="meta-row"><dt>Instructor</dt><dd>{{ $enrollment->class?->instructor?->full_name ?? '—' }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-head"><i class="fa-solid fa-circle-info"></i><h3>Enrollment details</h3></div>
            <div class="detail-card-body">
                <dl class="meta-grid">
                    <div class="meta-row"><dt>Status</dt><dd><span class="enrollment-status {{ $statusClass }}">{{ ucfirst($enrollment->status) }}</span></dd></div>
                    <div class="meta-row"><dt>Final grade</dt><dd class="{{ $enrollment->final_grade !== null ? 'grade-highlight' : '' }}">{{ $enrollment->final_grade ?? 'Not graded' }}</dd></div>
                    <div class="meta-row"><dt>Enrolled date</dt><dd>{{ $enrollment->enrolled_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                    @if($enrollment->completed_at)
                        <div class="meta-row"><dt>Completed date</dt><dd>{{ $enrollment->completed_at->format('M d, Y g:i A') }}</dd></div>
                    @endif
                </dl>
                @if($enrollment->notes)
                    <div class="notes" style="margin-top:18px;"><strong>Notes</strong><br>{{ $enrollment->notes }}</div>
                @endif
            </div>
        </section>
    </div>
@endsection

@extends('layouts.admin')

@php
    $activeNav = 'grade_submissions';
@endphp

@section('title', 'Grade Submissions')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-export"></i>
            Grade Submission Monitoring
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Overview</h3>
        </div>
        <div style="padding:16px;display:flex;gap:26px;flex-wrap:wrap;font-size:.88rem;">
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submissions</div><strong>{{ $stats['total'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submitted</div><strong>{{ $stats['submitted'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Classes covered</div><strong>{{ $stats['classes'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">With missing grades</div>
                <strong style="color:{{ $stats['incomplete'] > 0 ? '#fbbf24' : '#34d399' }};">{{ $stats['incomplete'] }}</strong>
            </div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Average LMS score</div>
                <strong>{{ number_format($stats['average_percent'], 2) }}%</strong>
            </div>
        </div>
    </div>

    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>Filter</h3>
        </div>
        <form method="GET" action="{{ route('admin.grade-submissions.index') }}" style="padding:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label for="status" style="display:block;font-size:.78rem;color:#94a3b8;margin-bottom:6px;">Status</label>
                <select id="status" name="status" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(148,163,184,.35);background:rgba(15,23,42,.35);color:inherit;">
                    <option value="">All</option>
                    <option value="submitted" @selected($status === 'submitted')>Submitted</option>
                    <option value="previewed" @selected($status === 'previewed')>Previewed</option>
                    <option value="failed" @selected($status === 'failed')>Failed</option>
                </select>
            </div>
            <div>
                <label for="class_id" style="display:block;font-size:.78rem;color:#94a3b8;margin-bottom:6px;">Class</label>
                <select id="class_id" name="class_id" style="padding:8px 12px;border-radius:8px;border:1px solid rgba(148,163,184,.35);background:rgba(15,23,42,.35);color:inherit;">
                    <option value="">All classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected((int) request('class_id') === $class->id)>{{ $class->code }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="{{ route('admin.grade-submissions.index') }}" class="btn btn-secondary btn-sm">Reset</a>
        </form>
    </div>

    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(96,165,250,.08);border:1px solid rgba(96,165,250,.30);border-radius:10px;font-size:.83rem;">
        <i class="fa-solid fa-circle-info" style="color:#60a5fa"></i>
        Monitoring lamang ito. Ang official final grades ay nasa <strong>AA</strong>; walang editing ng official record sa page na ito.
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Submissions</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th style="text-align:center;">Students</th>
                    <th style="text-align:center;">Graded</th>
                    <th style="text-align:center;">Missing</th>
                    <th style="text-align:center;">Average</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $submission)
                    <tr>
                        <td>{{ $submission->class?->code ?? '—' }}</td>
                        <td>{{ $submission->class?->course?->title ?? '—' }}</td>
                        <td>{{ $submission->instructor?->name ?? '—' }}</td>
                        <td style="text-align:center;">{{ $submission->student_count }}</td>
                        <td style="text-align:center;">{{ $submission->graded_count }}</td>
                        <td style="text-align:center;">
                            @if($submission->missing_count > 0)
                                <span style="color:#fbbf24;font-weight:700;">{{ $submission->missing_count }}</span>
                            @else
                                {{ $submission->missing_count }}
                            @endif
                        </td>
                        <td style="text-align:center;">
                            {{ $submission->average_percent !== null ? number_format((float) $submission->average_percent, 2) : '—' }}
                        </td>
                        <td>{{ ucfirst($submission->status) }}</td>
                        <td>{{ $submission->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                        <td><a href="{{ route('admin.grade-submissions.show', $submission) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align:center;padding:20px;color:#64748b;">Wala pang grade submission.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:10px 16px;">{{ $submissions->links() }}</div>
    </div>
@endsection

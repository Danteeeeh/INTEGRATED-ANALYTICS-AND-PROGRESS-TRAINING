@extends('layouts.aa')

@php
    $activeNav = 'grade_submissions';
@endphp

@section('title', 'Grade Submissions')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-export"></i>
            LMS Grade Submissions (Read-Only)
        </h2>
    </div>
@endsection

@section('content')
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>View-only ito. Ang mga tala dito ay <strong>LMS assessment scores</strong> na isinumite ng faculty — <strong>hindi official final grades</strong>. Ang official records ay nananatili sa <strong>AA</strong>; walang write action sa page na ito.</span>
    </div>

    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header"><h3>Summary</h3></div>
        <div style="padding:16px;display:flex;gap:26px;flex-wrap:wrap;font-size:.88rem;">
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submissions</div><strong>{{ $stats['total'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submitted</div><strong>{{ $stats['submitted'] }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">With missing grades</div>
                <strong style="color:{{ $stats['incomplete'] > 0 ? '#fbbf24' : '#34d399' }};">{{ $stats['incomplete'] }}</strong>
            </div>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-header"><h3>Submission statuses</h3></div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th style="text-align:center;">Students</th>
                    <th style="text-align:center;">Graded</th>
                    <th style="text-align:center;">Missing</th>
                    <th>Status</th>
                    <th>Submitted</th>
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
                        <td style="text-align:center;">{{ $submission->missing_count }}</td>
                        <td>{{ ucfirst($submission->status) }}</td>
                        <td>{{ $submission->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align:center;padding:20px;color:#64748b;">Wala pang grade submission.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:10px 16px;">{{ $submissions->links() }}</div>
    </div>
@endsection

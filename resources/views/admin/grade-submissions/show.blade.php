@extends('layouts.admin')

@php
    $activeNav = 'grade_submissions';
    $rows = $submission->payload['rows'] ?? [];
@endphp

@section('title', 'Grade Submission')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-export"></i>
            Submission #{{ $submission->id }} — {{ $submission->class?->code ?? '—' }}
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('admin.grade-submissions.index') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header"><h3>Details</h3></div>
        <div style="padding:16px;display:flex;gap:26px;flex-wrap:wrap;font-size:.88rem;">
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Class</div><strong>{{ $submission->class?->code ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Course</div><strong>{{ $submission->class?->course?->title ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Instructor</div><strong>{{ $submission->instructor?->name ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submitted by</div><strong>{{ $submission->submittedBy?->name ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Period</div><strong>{{ $submission->period_label ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Status</div><strong>{{ ucfirst($submission->status) }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Submitted</div><strong>{{ $submission->submitted_at?->format('M d, Y g:i A') ?? '—' }}</strong></div>
            <div><div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;">Students / Graded / Missing</div>
                <strong>{{ $submission->student_count }} / {{ $submission->graded_count }} / {{ $submission->missing_count }}</strong>
            </div>
        </div>
        @if($submission->notes)
            <div style="padding:0 16px 16px;font-size:.85rem;color:#cbd5e1;">
                <div style="color:#94a3b8;font-size:.75rem;text-transform:uppercase;margin-bottom:4px;">Notes</div>
                {{ $submission->notes }}
            </div>
        @endif
    </div>

    <div class="crud-card">
        <div class="crud-header"><h3>Snapshot rows</h3></div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th style="text-align:center;">LMS Score %</th>
                    <th style="text-align:center;">Letter</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['identifier'] ?? ($row['student_id'] ?? '—') }}</td>
                        <td>{{ $row['name'] ?? '—' }}</td>
                        <td>{{ ucfirst($row['enrollment_status'] ?? '—') }}</td>
                        <td style="text-align:center;">
                            @if($row['is_graded'] ?? false)
                                {{ number_format((float) $row['lms_score'], 2) }}
                            @else
                                <span style="color:#fbbf24;font-weight:700;">Missing</span>
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $row['letter_grade'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">Walang snapshot rows.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

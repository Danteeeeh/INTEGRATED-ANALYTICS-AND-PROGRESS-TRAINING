@extends('layouts.instructor')

@php
    $activeNav = 'grade_submissions';
@endphp

@section('title', 'Extract & Submit Grades')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-export"></i>
            Extract &amp; Submit Grades
        </h2>
    </div>
@endsection

@section('content')
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>Ang extraction na ito ay <strong>LMS assessment scores</strong> lamang — hindi ito official final grade. Ang official final grade ay hawak ng <strong>AA</strong>. Ang pag-submit dito ay nagre-record lamang ng snapshot at history; <strong>hindi nito binabago</strong> ang official records.</span>
    </div>

    <div class="crud-card" style="margin-bottom:18px;">
        <div class="crud-header">
            <h3>My Classes — extract grades</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th style="text-align:center;">Enrollments</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->code }}</td>
                        <td>{{ $class->course?->title ?? '—' }}</td>
                        <td style="text-align:center;">{{ $class->enrollments_count }}</td>
                        <td style="text-align:center;white-space:nowrap;">
                            <a href="{{ route('instructor.grade-submissions.preview', $class) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-magnifying-glass-chart"></i> Extract &amp; Preview
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:20px;color:#64748b;">
                            Wala pang naka-assign na class. Ang classes ay galing sa <strong>AA sync</strong>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Grade Submission History</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
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
                        <td>
                            <span style="padding:2px 9px;border-radius:999px;font-size:.72rem;font-weight:700;background:rgba(52,211,153,.12);color:#34d399;">
                                {{ ucfirst($submission->status) }}
                            </span>
                        </td>
                        <td>{{ $submission->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('instructor.grade-submissions.show', $submission) }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center;padding:20px;color:#64748b;">
                            Wala pang grade submission. Piliin ang class sa itaas para mag-extract at mag-submit.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:10px 16px;">
            {{ $submissions->links() }}
        </div>
    </div>
@endsection

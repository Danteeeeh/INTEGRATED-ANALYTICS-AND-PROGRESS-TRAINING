@extends('layouts.instructor')

@section('title', $assignment->title)
@php
    $activeNav = 'courses';
    $pageTitle = $assignment->title;
    $pageIcon = '<i class="fa-solid fa-file-pen"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-pen"></i>
            {{ $assignment->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.assignments.index', $course) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
            <a href="{{ route('instructor.courses.assignments.edit', [$course, $assignment]) }}" class="btn-add">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card" style="margin-bottom:20px;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-info"></i> Assignment Details</h3>
            @if($assignment->status === 'published')
                <span class="badge-published">Published</span>
            @elseif($assignment->status === 'closed')
                <span class="badge-draft">Closed</span>
            @else
                <span class="badge-draft">Draft</span>
            @endif
        </div>
        <div style="padding: 20px 24px; display:flex; flex-direction:column; gap:14px;">
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <div><span class="dash-stat-label">Class</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ $assignment->class?->code ?? '—' }}</div></div>
                <div><span class="dash-stat-label">Points</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ $assignment->points ?? '—' }}</div></div>
                <div><span class="dash-stat-label">Submission Type</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ ucfirst($assignment->submission_type ?? 'text') }}</div></div>
                @if($assignment->due_date)
                    <div><span class="dash-stat-label">Due</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ $assignment->due_date->format('M j, Y g:i A') }}</div></div>
                @endif
            </div>
            @if($assignment->instructions)
                <div>
                    <span class="dash-stat-label">Instructions</span>
                    <div style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.6;white-space:pre-wrap;">{{ $assignment->instructions }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-inbox"></i> Submissions ({{ $assignment->submissions->count() }})</h3>
            <a href="{{ route('instructor.courses.assignments.submissions', [$course, $assignment]) }}" class="btn-add">
                <i class="fa-solid fa-inbox"></i>
                Review Submissions
            </a>
        </div>
        <div style="padding: 16px 24px;">
            @forelse($assignment->submissions as $submission)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(52,211,153,.13);color:#6ee7b7"><i class="fa-solid fa-file-lines"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $submission->student?->full_name ?? 'Student' }}</div>
                        <div class="module-mini-meta">
                            Status: {{ ucfirst($submission->status ?? 'submitted') }}
                            @if($submission->score !== null)
                                · Score: {{ $submission->score }}
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission]) }}" class="btn-icon btn-view" title="Review">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @empty
                <div class="tab-empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    No submissions yet.
                </div>
            @endforelse
        </div>
    </div>
@endsection

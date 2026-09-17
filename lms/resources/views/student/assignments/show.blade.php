@extends('layouts.student')
@section('title', $assignment->title)
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $assignment->title }}"
        subtitle="{{ $course->title }} — assignment details"
        icon="fa-tasks"
    >
        <x-slot name="meta">
            @if($assignment->due_date)
                <span class="user-status">{{ $assignment->due_date->isPast() ? 'Overdue' : 'Open' }}</span>
                <span>Due {{ $assignment->due_date->format('l, F j, Y g:i A') }}</span>
            @endif
        </x-slot>
        <x-slot name="actions">
            @if(!$mySubmission || in_array($mySubmission->status, ['draft', 'returned'], true))
                <a href="{{ route('student.courses.assignments.submit', [$course, $assignment]) }}" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> {{ $mySubmission ? 'Resubmit' : 'Submit' }}</a>
            @endif
            <a href="{{ route('student.courses.assignments.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Assignment</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Points</label>
                    <div>{{ $assignment->points }}</div>
                </div>
                <div class="form-field">
                    <label>Due Date</label>
                    <div>{{ $assignment->due_date?->format('M j, Y g:i A') ?? 'No due date' }}</div>
                </div>
                @if($assignment->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div style="white-space:pre-line">{{ $assignment->description }}</div>
                    </div>
                @endif
            </div>

            @if($assignment->attachments && $assignment->attachments->count() > 0)
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attachments</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        @foreach($assignment->attachments as $file)
                            <a href="{{ $file->url ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file"></i> {{ $file->original_name ?? $file->name ?? 'File' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-file-circle-check"></i> My Submission</h3></div>
        <div class="user-panel-body">
            @if($mySubmission)
                <div class="form-grid">
                    <div class="form-field">
                        <label>Status</label>
                        <div><x-user-status-badge status="{{ $mySubmission->status }}" /></div>
                    </div>
                    <div class="form-field">
                        <label>Submitted</label>
                        <div>{{ $mySubmission->submitted_at?->format('M j, Y g:i A') ?? $mySubmission->created_at?->format('M j, Y g:i A') }}</div>
                    </div>
                    @if($mySubmission->grade)
                        <div class="form-field">
                            <label>Score</label>
                            <div><strong>{{ number_format($mySubmission->grade->score_percent ?? 0, 1) }}%</strong> ({{ $mySubmission->grade->points ?? 0 }}/{{ $assignment->points }})</div>
                        </div>
                    @endif
                    @if($mySubmission->grade?->feedback)
                        <div class="form-field full">
                            <label>Feedback</label>
                            <div style="white-space:pre-line">{{ $mySubmission->grade->feedback }}</div>
                        </div>
                    @endif
                </div>

                <div class="user-actions" style="justify-content:flex-start;margin-top:12px">
                    <a href="{{ route('student.courses.assignments.submissions.show', [$course, $assignment, $mySubmission]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View Submission</a>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-file-circle-check"
                    title="Not submitted yet"
                    description="Submit your work before the due date."
                >
                    <x-slot name="action">
                        <a href="{{ route('student.courses.assignments.submit', [$course, $assignment]) }}" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Now</a>
                    </x-slot>
                </x-user-empty-state>
            @endif
        </div>
    </div>

    @if(!$mySubmission || in_array($mySubmission->status, ['draft', 'returned'], true))
        <div class="learning-next-action">
            <div>
                <strong>Ready to submit?</strong>
                <span>Make sure you have completed all parts of the assignment.</span>
            </div>
            <a href="{{ route('student.courses.assignments.submit', [$course, $assignment]) }}" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Go to Submission</a>
        </div>
    @endif
</div>
@endsection

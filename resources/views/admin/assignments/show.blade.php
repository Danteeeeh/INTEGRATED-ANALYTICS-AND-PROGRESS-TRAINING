@extends('layouts.admin-sms')

@section('title', 'Assignment Details')
@php
    $activeNav = 'courses';
    $pageTitle = 'Assignment Details';
    $pageIcon = '<i class="fa-solid fa-file-circle-check"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-circle-check"></i>
            {{ $assignment->title }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Assignment Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Points:</span><span>{{ $assignment->points }} pts</span></div>
        <div class="modal-row"><span>Status:</span><span>
            @if($assignment->status === \App\Models\Assignment::STATUS_PUBLISHED)
                <span class="badge-active">Published</span>
            @elseif($assignment->status === \App\Models\Assignment::STATUS_CLOSED)
                <span class="badge-inactive">Closed</span>
            @else
                <span style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);color:#d97706;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600;border:1px solid #fcd34d;">Draft</span>
            @endif
        </span></div>
        <div class="modal-row"><span>Submission Type:</span><span>{{ ucfirst(str_replace('_', ' ', $assignment->submission_type)) }}</span></div>
        <div class="modal-row"><span>Allow Late:</span><span>{{ $assignment->allow_late ? 'Yes' : 'No' }}</span></div>
        <div class="modal-row"><span>Resubmission Limit:</span><span>{{ $assignment->max_attempts ?? 0 }} {{ ($assignment->max_attempts ? 'attempts' : '(unlimited)') }}</span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-graduation-cap"></i> Academic Context</div>
        <div class="modal-row"><span>Course:</span><span>
            @if($assignment->class && $assignment->class->course)
                <a href="{{ route('admin.courses.show', $assignment->class->course) }}" class="text-blue-600 hover:underline">{{ $assignment->class->course->name }}</a>
            @else
                Not assigned
            @endif
        </span></div>
        <div class="modal-row"><span>Class:</span><span>{{ $assignment->class ? $assignment->class->name : 'Not assigned' }}</span></div>
        <div class="modal-row"><span>Rubric:</span><span>{{ $assignment->rubric ? $assignment->rubric->title : 'None' }}</span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-calendar"></i> Schedule</div>
        <div class="modal-row"><span>Due Date:</span><span>{{ $assignment->due_date ? $assignment->due_date->format('F d, Y H:i') : 'Not set' }}</span></div>
        <div class="modal-row"><span>Available From:</span><span>{{ $assignment->availability_from ? $assignment->availability_from->format('F d, Y H:i') : 'Always' }}</span></div>
        <div class="modal-row"><span>Available Until:</span><span>{{ $assignment->availability_until ? $assignment->availability_until->format('F d, Y H:i') : 'Always' }}</span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Instructions</div>
        <div class="modal-row"><span></span><span style="white-space:pre-wrap;">{{ $assignment->instructions ?? 'No instructions provided.' }}</span></div>

        @if($assignment->attachments && $assignment->attachments->count() > 0)
            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-paperclip"></i> Attachments ({{ $assignment->attachments->count() }})</div>
            <div style="padding:0 0 0 0;">
                @foreach($assignment->attachments as $att)
                    <div class="modal-row"><span></span><span><i class="fa-solid fa-file" style="color:#6b7280;margin-right:6px;"></i>{{ $att->filename }}</span></div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Submissions ({{ $assignment->submissions->count() }})</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Submitted At</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignment->submissions as $sub)
                    <tr>
                        <td>{{ $sub->student ? $sub->student->full_name : 'Unknown' }}</td>
                        <td>{{ $sub->submitted_at ? $sub->submitted_at->format('M d, Y H:i') : 'Not submitted' }}</td>
                        <td>
                            @if($sub->submitted_at)
                                <span class="badge-active">Submitted</span>
                            @else
                                <span class="badge-inactive">Pending</span>
                            @endif
                        </td>
                        <td>{{ $sub->grade !== null ? $sub->grade . ' / ' . $assignment->points : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:#aaa;">No submissions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin:0 24px 24px;">
        <a href="{{ route('admin.assignments.index') }}" class="btn-modal-cancel" style="display:inline-flex;align-items:center;justify-content:center;padding:11px 32px;border-radius:8px;font-size:0.88rem;font-weight:600;cursor:pointer;transition:background 0.2s;text-decoration:none;">
            ← Back to Assignments
        </a>
    </div>
@endsection

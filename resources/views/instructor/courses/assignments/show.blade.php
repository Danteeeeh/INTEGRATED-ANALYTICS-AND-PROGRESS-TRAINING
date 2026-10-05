@extends('layouts.instructor')

@section('title', $assignment->title)
@php
    $activeNav = 'assignments';
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
            <form action="{{ route('instructor.courses.assignments.destroy', [$course, $assignment]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this assignment?')" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-delete" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; background: #ef4444; color: white; border: none; cursor: pointer;">
                    <i class="fa-solid fa-trash"></i>
                    Delete
                </button>
            </form>
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

    <div class="crud-card" style="margin-bottom:20px;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-clock"></i> Deadline Extensions</h3>
        </div>
        <div style="padding: 20px 24px;">
            <form action="{{ route('instructor.courses.assignments.extensions.grant', [$course, $assignment]) }}" method="POST">
                @csrf
                <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; margin-bottom:6px; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Student</label>
                        <select name="student_id" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid #374151; background:#1f2937; color:#eef4ff;">
                            <option value="">Select a student...</option>
                            @foreach($course->enrollments()->where('status', 'active')->get() as $enrollment)
                                <option value="{{ $enrollment->student_id }}">{{ $enrollment->student->name }} ({{ $enrollment->student->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <label style="display:block; margin-bottom:6px; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Extended Until</label>
                        <input type="datetime-local" name="extended_until" required style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid #374151; background:#1f2937; color:#eef4ff;">
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:6px; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Reason (Optional)</label>
                    <input type="text" name="reason" placeholder="e.g., Medical emergency, family emergency, etc." style="width:100%; padding:10px 12px; border-radius:8px; border:1px solid #374151; background:#1f2937; color:#eef4ff;">
                </div>
                <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Grant Extension</button>
            </form>

            @if($assignment->extensions && $assignment->extensions->count() > 0)
                <div style="margin-top:24px;">
                    <h4 style="color:#eef4ff; margin-bottom:12px; font-size:0.95rem;">Active Extensions</h4>
                    <div style="background:#111827; border-radius:8px; overflow:hidden;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background:#1f2937;">
                                    <th style="padding:12px 16px; text-align:left; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Student</th>
                                    <th style="padding:12px 16px; text-align:left; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Extended Until</th>
                                    <th style="padding:12px 16px; text-align:left; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Reason</th>
                                    <th style="padding:12px 16px; text-align:left; color:#c7d4ec; font-size:0.85rem; font-weight:600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignment->extensions as $extension)
                                    <tr style="border-top:1px solid #374151;">
                                        <td style="padding:12px 16px; color:#eef4ff;">{{ $extension->student->name }}</td>
                                        <td style="padding:12px 16px; color:#eef4ff;">{{ $extension->extended_until->format('M j, Y g:i A') }}</td>
                                        <td style="padding:12px 16px; color:#eef4ff;">{{ $extension->reason ?? '—' }}</td>
                                        <td style="padding:12px 16px;">
                                            <form action="{{ route('instructor.courses.assignments.extensions.revoke', [$course, $assignment, $extension->student_id]) }}" method="POST" onsubmit="return confirm('Revoke this extension?')" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-delete" style="padding:6px 12px; border-radius:6px; font-size:0.8rem; background:#ef4444; color:white; border:none; cursor:pointer;"><i class="fa-solid fa-times"></i> Revoke</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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

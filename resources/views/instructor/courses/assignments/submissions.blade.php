@extends('layouts.instructor')
@section('title', 'Submissions — '.$assignment->title)
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Assignment Submissions"
        subtitle="{{ $assignment->title }} — {{ $course->title }}"
        icon="fa-tasks"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $submissions->total() }} submissions</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.assignments.show', [$course, $assignment]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-users"></i> Submissions</h3></div>
        <div class="user-panel-body">
            @if($submissions->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $submission)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $submission->student?->full_name ?? $submission->student?->name ?? '—' }}</div>
                                        <div class="user-email">{{ $submission->student?->email }}</div>
                                    </td>
                                    <td>{{ $submission->submitted_at?->format('M j, Y g:i A') ?? $submission->created_at?->format('M j, Y g:i A') }}</td>
                                    <td><x-user-status-badge status="{{ $submission->status }}" /></td>
                                    <td>
                                        @if($submission->grade)
                                            {{ number_format($submission->grade->score_percent ?? 0, 1) }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('instructor.courses.assignments.submissions.show', [$course, $assignment, $submission]) }}" class="btn btn-icon" title="Review"><i class="fa-solid fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-tasks"
                    title="No submissions yet"
                    description="Student submissions will appear here once they are turned in."
                />
            @endif

            @if($submissions->hasPages())
                <div class="pagination">{{ $submissions->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

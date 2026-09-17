@extends('layouts.instructor')

@section('title', 'Assignments — ' . $course->code)
@php
    $activeNav = 'courses';
    $pageTitle = 'Assignments';
    $pageIcon = '<i class="fa-solid fa-file-pen"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-pen"></i>
            Assignments — {{ $course->code }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.show', $course) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="{{ route('instructor.courses.assignments.create', $course) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Assignment
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-file-pen"></i> Assignments ({{ $assignments->total() }})</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Points</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->title }}</td>
                        <td>{{ $assignment->class?->code ?? '—' }}</td>
                        <td>{{ $assignment->points ?? '—' }}</td>
                        <td>{{ $assignment->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td>
                            @if($assignment->status === 'published')
                                <span class="badge-published">Published</span>
                            @elseif($assignment->status === 'closed')
                                <span class="badge-draft">Closed</span>
                            @else
                                <span class="badge-draft">Draft</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('instructor.courses.assignments.show', [$course, $assignment]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('instructor.courses.assignments.edit', [$course, $assignment]) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            @if($assignment->status === 'published')
                                <a href="{{ route('instructor.courses.assignments.submissions', [$course, $assignment]) }}" class="btn-icon" title="Submissions" style="color:#62c9f5;">
                                    <i class="fa-solid fa-inbox"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No assignments yet. <a href="{{ route('instructor.courses.assignments.create', $course) }}" style="color:#2563eb;text-decoration:underline;">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($assignments->hasPages())
            <div style="padding:14px 24px;">{{ $assignments->links() }}</div>
        @endif
    </div>
@endsection

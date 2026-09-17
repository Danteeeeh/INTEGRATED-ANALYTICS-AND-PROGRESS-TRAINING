@extends('layouts.admin')

@section('title', 'Assignments')
@php
    $activeNav = 'courses';
    $pageTitle = 'Assignments';
    $pageIcon = '<i class="fa-solid fa-file-circle-check"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-circle-check"></i>
            Assignments
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Assignments</h3>
            <a href="{{ route('admin.assignments.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Assignment
            </a>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
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
                        <td>
                            @if($assignment->class && $assignment->class->course)
                                <a href="{{ route('admin.courses.show', $assignment->class->course) }}" class="text-blue-600 hover:underline">
                                    {{ $assignment->class->course->name }}
                                </a>
                            @else
                                <span style="color:#aaa;">—</span>
                            @endif
                        </td>
                        <td>{{ $assignment->class ? $assignment->class->name : '—' }}</td>
                        <td>{{ $assignment->points }}</td>
                        <td>{{ $assignment->due_date ? $assignment->due_date->format('M d, Y H:i') : '—' }}</td>
                        <td>
                            @if($assignment->status === \App\Models\Assignment::STATUS_PUBLISHED)
                                <span class="badge-active">Published</span>
                            @elseif($assignment->status === \App\Models\Assignment::STATUS_CLOSED)
                                <span class="badge-inactive">Closed</span>
                            @else
                                <span style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);color:#d97706;padding:4px 12px;border-radius:20px;font-size:0.72rem;font-weight:600;border:1px solid #fcd34d;">Draft</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            @if($assignment->status !== \App\Models\Assignment::STATUS_PUBLISHED)
                                <form method="POST" action="{{ route('admin.assignments.publish', $assignment) }}" class="inline" style="margin-right:4px;">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Publish" style="background:#dcfce7;color:#16a34a;border:1px solid #86efac;border-radius:6px;padding:4px 8px;font-size:0.7rem;font-weight:600;">
                                        <i class="fa-solid fa-eye"></i> Publish
                                    </button>
                                </form>
                            @endif
                            @if($assignment->status === \App\Models\Assignment::STATUS_PUBLISHED)
                                <form method="POST" action="{{ route('admin.assignments.close', $assignment) }}" class="inline" style="margin-right:4px;">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Close" style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;border-radius:6px;padding:4px 8px;font-size:0.7rem;font-weight:600;">
                                        <i class="fa-solid fa-lock"></i> Close
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('admin.assignments.show', $assignment) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.assignments.edit', $assignment) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:#aaa;">
                            No assignments found. <a href="{{ route('admin.assignments.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

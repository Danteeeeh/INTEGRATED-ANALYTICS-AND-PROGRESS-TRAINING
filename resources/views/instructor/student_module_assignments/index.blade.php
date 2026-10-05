@extends('layouts.instructor')

@section('title', 'Student Module Assignments')
@php
    $activeNav = 'classes';
    $pageTitle = 'Student Module Assignments';
    $pageIcon = '<i class="fa-solid fa-users-gear"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-users-gear"></i>
            Student Module Assignments
        </h2>
    </div>
@endsection

@section('content')
<div class="admin-shell">
    <x-user-page-header
        title="Manage Module Assignments"
        subtitle="Assign specific modules to students in this class."
        icon="fa-users-gear"
        kicker="Class: {{ $class->code }}"
    >
        <x-slot name="meta">
            <span>{{ $class->course->title ?? '' }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to Class
            </a>
            <a href="{{ route('instructor.classes.student_module_assignments.create', $class) }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Assign Module
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="admin-panel">
        <div class="admin-panel-head">
            <h4><i class="fa-solid fa-list"></i> Module Assignments</h4>
            <span class="panel-count">{{ $moduleAssignments->total() }} total</span>
        </div>
        <div class="admin-panel-body">
            @if($moduleAssignments->isEmpty())
                <x-user-empty-state
                    icon="fa-users-gear"
                    title="No module assignments yet"
                    description="Assign modules to students to customize their learning path."
                />
            @else
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Module</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($moduleAssignments as $assignment)
                            <tr>
                                <td>
                                    <strong>{{ $assignment->student->name }}</strong>
                                    <small>{{ $assignment->student->email }}</small>
                                </td>
                                <td>
                                    <strong>{{ $assignment->module->title }}</strong>
                                    <small>{{ $assignment->module->course->title ?? '' }}</small>
                                </td>
                                <td>
                                    @if($assignment->status === 'assigned')
                                        <span class="user-status pending">Assigned</span>
                                    @elseif($assignment->status === 'in_progress')
                                        <span class="user-status active">In Progress</span>
                                    @elseif($assignment->status === 'completed')
                                        <span class="user-status completed">Completed</span>
                                    @endif
                                </td>
                                <td>{{ $assignment->assigned_at?->format('M d, Y') }}</td>
                                <td>
                                    <form action="{{ route('instructor.classes.student_module_assignments.destroy', [$class, $assignment]) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this module assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{ $moduleAssignments->links() }}
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.instructor')

@section('title', 'My Enrollments')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Student Enrollments';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
@endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Student Enrollments"
        subtitle="Review enrollments in your classes."
        icon="fa-graduation-cap"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.enrollments.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Enroll Student
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-user-plus"></i> My Class Enrollments</h3>
        </div>
        <form class="user-toolbar" method="GET" action="{{ route('instructor.enrollments.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search students..." aria-label="Search enrollments">
            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('instructor.enrollments.index') }}">Clear</a>
        </form>
        <div class="user-panel-body">
            @if($enrollments->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->student->full_name ?? '-' }}</td>
                                    <td>{{ $enrollment->class->code ?? '-' }} - {{ $enrollment->class->name ?? '-' }}</td>
                                    <td>{{ $enrollment->class->course->name ?? '-' }}</td>
                                    <td><x-user-status-badge status="{{ $enrollment->status }}" /></td>
                                    <td>{{ $enrollment->final_grade ?? '-' }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('instructor.enrollments.show', $enrollment) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('instructor.enrollments.edit', $enrollment) }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form action="{{ route('instructor.enrollments.destroy', $enrollment) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this enrollment?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Remove"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-user-plus"
                    title="No enrollments found"
                    description="No enrollments found in your classes."
                />
            @endif
        </div>
    </div>
</div>
@endsection
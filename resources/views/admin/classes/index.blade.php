@extends('layouts.admin')

@section('title', 'Classes')
@php
    $activeNav = 'classes';
    $pageTitle = 'Classes';
    $pageIcon = '<i class="fa-solid fa-grid"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-grid"></i>
            Classes
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Classes</h3>
            <a href="{{ route('admin.classes.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Class
            </a>
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('admin.classes.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search classes..." aria-label="Search classes">
            <select name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Enrolled</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->code }}</td>
                        <td>{{ $class->name }}</td>
                        <td>{{ $class->course->name ?? '-' }}</td>
                        <td>{{ $class->instructor->full_name ?? '-' }}</td>
                        <td>
                            {{ $class->enrolled_count ?? 0 }} / {{ $class->max_students }}
                            @if($class->enrolled_count >= $class->max_students)
                                <span class="badge-inactive">Full</span>
                            @else
                                <span class="badge-active">Available</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('admin.classes.show', $class) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.classes.edit', $class) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.classes.destroy', $class) }}" class="inline">
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
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No classes found. <a href="{{ route('admin.classes.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($classes->hasPages())
            <div class="pagination">{{ $classes->appends(request()->query())->links() }}</div>
        @endif
    </div>
@endsection
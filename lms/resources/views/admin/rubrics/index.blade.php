@extends('layouts.admin')

@section('title', 'Rubrics')
@php $activeNav = 'rubrics'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Rubrics"
        subtitle="Create and manage grading rubrics for assignments."
        icon="fa-table-list"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.rubrics.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Rubric</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.rubrics.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search rubrics..." aria-label="Search rubrics">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.rubrics.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($rubrics->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rubric</th>
                                <th>Course / Class</th>
                                <th>Status</th>
                                <th>Shared</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rubrics as $rubric)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $rubric->title }}</div>
                                        <div class="user-email">{{ Str::limit($rubric->description ?? '', 50) }}</div>
                                    </td>
                                    <td>
                                        @if($rubric->course)
                                            {{ $rubric->course->code }}
                                        @elseif($rubric->class)
                                            {{ $rubric->class->code }}
                                        @else
                                            General
                                        @endif
                                    </td>
                                    <td><x-user-status-badge status="{{ $rubric->status }}" /></td>
                                    <td>{{ $rubric->is_shared ? 'Yes' : 'No' }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.rubrics.show', $rubric) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.rubrics.edit', $rubric) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.rubrics.destroy', $rubric) }}" onsubmit="return confirm('Delete this rubric?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
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
                    icon="fa-table-list"
                    title="No rubrics found"
                    description="Create a rubric to standardize assignment grading."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.rubrics.create') }}"><i class="fa-solid fa-plus"></i> New Rubric</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($rubrics->hasPages())
                <div class="pagination">{{ $rubrics->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

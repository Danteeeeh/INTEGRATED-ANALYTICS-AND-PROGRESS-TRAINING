@extends('layouts.admin')

@section('title', 'Lessons')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $module->title }} — Lessons"
        subtitle="Manage lesson content, materials, and publishing."
        icon="fa-list-check"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $lessons->total() }} lessons</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.courses.modules.lessons.create', [$module->course_id, $module]) }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Lesson</a>
            <a href="{{ route('admin.courses.modules.show', [$module->course_id, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Module</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.courses.modules.lessons.index', [$module->course_id, $module]) }}">
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.courses.modules.lessons.index', [$module->course_id, $module]) }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($lessons->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lesson</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Materials</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lessons as $lesson)
                                <tr>
                                    <td>{{ $lesson->position }}</td>
                                    <td>
                                        <div class="user-name">{{ $lesson->title }}</div>
                                        <div class="user-email">{{ Str::limit($lesson->description ?? '', 50) }}</div>
                                    </td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $lesson->lesson_type)) }}</span></td>
                                    <td><x-user-status-badge status="{{ $lesson->status }}" /></td>
                                    <td>{{ $lesson->materials->count() }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.courses.modules.lessons.show', [$module->course_id, $module, $lesson]) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.courses.modules.lessons.edit', [$module->course_id, $module, $lesson]) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.courses.modules.lessons.destroy', [$module->course_id, $module, $lesson]) }}" onsubmit="return confirm('Delete this lesson?')">
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
                    icon="fa-list-check"
                    title="No lessons yet"
                    description="Create the first lesson for this module."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.courses.modules.lessons.create', [$module->course_id, $module]) }}"><i class="fa-solid fa-plus"></i> New Lesson</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($lessons->hasPages())
                <div class="pagination">{{ $lessons->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

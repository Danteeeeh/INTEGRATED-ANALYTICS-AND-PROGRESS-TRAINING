@extends('layouts.admin')

@section('title', 'Modules')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Modules"
        subtitle="Manage learning modules across all courses."
        icon="fa-layer-group"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $modules->total() }} modules</span>
            <span class="user-status">{{ $courses->count() }} courses</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Courses</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.modules.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search modules..." aria-label="Search modules">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $courseOpt)
                    <option value="{{ $courseOpt->id }}" @selected(request('course_id') == $courseOpt->id)>{{ $courseOpt->code }} — {{ $courseOpt->title }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.modules.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($modules->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Module</th>
                                <th>Course</th>
                                <th>Lessons</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modules as $module)
                                <tr>
                                    <td>{{ $module->position }}</td>
                                    <td>
                                        <strong>{{ $module->title }}</strong>
                                        <div class="user-email">{{ Str::limit($module->description ?? '', 60) }}</div>
                                    </td>
                                    <td>
                                        @if($module->course)
                                            <span class="user-status">{{ $module->course->code }}</span>
                                            <div class="user-email">{{ Str::limit($module->course->title, 40) }}</div>
                                        @else
                                            <span class="user-status">—</span>
                                        @endif
                                    </td>
                                    <td><span class="user-status">{{ $module->lessons_count }}</span></td>
                                    <td>
                                        @if($module->status === 'published')
                                            <span class="user-status published">Published</span>
                                        @else
                                            <span class="user-status draft">Draft</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            @if($module->course)
                                                <a href="{{ route('admin.courses.modules.show', [$module->course, $module]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
                                                <a href="{{ route('admin.courses.modules.lessons.index', [$module->course, $module]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-list-check"></i></a>
                                                <a href="{{ route('admin.courses.modules.edit', [$module->course, $module]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($modules->hasPages())
                    <div class="user-panel-foot" style="padding:14px 16px; border-top:1px solid var(--enh-border, rgba(153,174,214,.16));">
                        {{ $modules->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="user-empty">
                    <i class="fa-solid fa-layer-group"></i>
                    <h3>No modules found</h3>
                    <p>Try adjusting your filters, or create a module from a course page.</p>
                    <a href="{{ route('admin.courses.index') }}" class="btn btn-primary"><i class="fa-solid fa-book"></i> Browse Courses</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

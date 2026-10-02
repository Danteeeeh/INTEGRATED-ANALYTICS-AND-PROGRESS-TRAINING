@extends('layouts.admin')

@section('title', $module ? $module->title.' — Lessons' : 'Lessons')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $module ? $module->title.' — Lessons' : 'Lessons' }}"
        subtitle="Manage lesson content, materials, and publishing."
        icon="fa-list-check"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $lessons->total() }} lessons</span>
            @if($module)<span class="user-status">{{ $module->course?->code }}</span>@endif
        </x-slot>
        <x-slot name="actions">
            @if($module)
                <a href="{{ route('admin.courses.modules.show', [$module->course_id, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Module</a>
            @else
                <a href="{{ route('admin.modules.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Modules</a>
            @endif
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ $module ? route('admin.courses.modules.lessons.index', [$module->course_id, $module]) : route('admin.lessons.index') }}">
            @if(!$module)
                <select class="form-control" name="course_id" aria-label="Filter course">
                    <option value="">All Courses</option>
                    @foreach(($courses ?? []) as $courseOpt)
                        <option value="{{ $courseOpt->id }}" @selected(request('course_id') == $courseOpt->id)>{{ $courseOpt->code }} — {{ $courseOpt->title }}</option>
                    @endforeach
                </select>
                <select class="form-control" name="module_id" aria-label="Filter module">
                    <option value="">All Modules</option>
                    @foreach(($modules ?? []) as $moduleOpt)
                        <option value="{{ $moduleOpt->id }}" @selected(request('module_id') == $moduleOpt->id)>{{ $moduleOpt->course?->code }} — {{ $moduleOpt->title }}</option>
                    @endforeach
                </select>
            @endif
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                <option value="published" @selected(request('status') === 'published')>Published</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ request()->url() }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($lessons->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Lesson</th>
                                @if(!$module)<th>Module</th>@endif
                                <th>Type</th>
                                <th>Status</th>
                                <th>Materials</th>
                                @if($module)<th>Actions</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lessons as $lesson)
                                <tr>
                                    <td>{{ $lesson->position }}</td>
                                    <td>
                                        <strong>{{ $lesson->title }}</strong>
                                        <div class="user-email">{{ Str::limit($lesson->description ?? '', 60) }}</div>
                                    </td>
                                    @if(!$module)
                                        <td>
                                            @if($lesson->module)
                                                <span class="user-status">{{ $lesson->module->course?->code }}</span>
                                                <div class="user-email">{{ Str::limit($lesson->module->title, 40) }}</div>
                                            @endif
                                        </td>
                                    @endif
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $lesson->lesson_type ?? 'text')) }}</span></td>
                                    <td>
                                        @if($lesson->status === 'published')
                                            <span class="user-status published">Published</span>
                                        @elseif($lesson->status === 'archived')
                                            <span class="user-status archived">Archived</span>
                                        @else
                                            <span class="user-status draft">Draft</span>
                                        @endif
                                    </td>
                                    <td><span class="user-status">{{ $lesson->materials_count }}</span></td>
                                    @if($module)
                                        <td>
                                            <div class="user-actions">
                                                <a href="{{ route('admin.courses.modules.lessons.show', [$module->course_id, $module, $lesson]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i></a>
                                                <a href="{{ route('admin.courses.modules.lessons.edit', [$module->course_id, $module, $lesson]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-pen"></i></a>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($lessons->hasPages())
                    <div style="padding:14px 16px; border-top:1px solid var(--enh-border, rgba(153,174,214,.16));">
                        {{ $lessons->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="user-empty">
                    <i class="fa-solid fa-list-check"></i>
                    <h3>No lessons found</h3>
                    <p>Try adjusting your filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


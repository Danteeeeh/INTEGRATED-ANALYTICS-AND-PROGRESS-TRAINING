@extends('layouts.admin')

@section('title', $module->title)
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $module->title }}"
        subtitle="{{ $module->course?->title ?? 'Module' }} — module details and lessons."
        icon="fa-cubes"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $module->status }}" />
            @if($module->is_required)<span class="user-status active">Required</span>@endif
            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.courses.modules.edit', [$module->course_id, $module]) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.courses.show', $module->course_id) }}" class="btn btn-secondary"><i class="fa-solid fa-book"></i> Course</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Module Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $module->course?->code ?? '—' }} — {{ $module->course?->title ?? '' }}</div>
                </div>
                <div class="form-field">
                    <label>Position</label>
                    <div>#{{ $module->position }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $module->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Required</label>
                    <div>{{ $module->is_required ? 'Yes' : 'No' }}</div>
                </div>
                @if($module->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div style="white-space:pre-line">{{ $module->description }}</div>
                    </div>
                @endif
                @if($module->objectives)
                    <div class="form-field full">
                        <label>Objectives</label>
                        <div style="white-space:pre-line">{{ is_array($module->objectives) ? implode("\n", $module->objectives) : $module->objectives }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Lessons</h3>
            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
        </div>
        <div class="user-panel-body">
            @if($module->lessons->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Lesson</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Materials</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($module->lessons->sortBy('position') as $lesson)
                                <tr>
                                    <td>
                                        <div class="user-name">#{{ $lesson->position }} — {{ $lesson->title }}</div>
                                        <div class="user-email">{{ Str::limit($lesson->description ?? '', 50) }}</div>
                                    </td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $lesson->lesson_type)) }}</span></td>
                                    <td><x-user-status-badge status="{{ $lesson->status }}" /></td>
                                    <td>{{ $lesson->materials->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-list-check"
                    title="No lessons yet"
                    description="Add lessons to this module to build the course content."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <a href="{{ route('admin.courses.show', $module->course_id) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Course</a>
        <form action="{{ route('admin.courses.modules.destroy', [$module->course_id, $module]) }}" method="POST" onsubmit="return confirm('Delete this module?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', $lesson->title)
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $lesson->title }}"
        subtitle="{{ $lesson->module?->title ?? 'Lesson' }} — lesson details."
        icon="fa-list-check"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $lesson->status }}" />
            <span class="user-status">{{ ucfirst(str_replace('_', ' ', $lesson->lesson_type)) }}</span>
            @if($lesson->is_required)<span class="user-status active">Required</span>@endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.courses.modules.lessons.edit', [$module->course_id, $module, $lesson]) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.courses.modules.lessons.index', [$module->course_id, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Lesson Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Module</label>
                    <div>{{ $module->title }}</div>
                </div>
                <div class="form-field">
                    <label>Type</label>
                    <div>{{ ucfirst(str_replace('_', ' ', $lesson->lesson_type)) }}</div>
                </div>
                <div class="form-field">
                    <label>Duration</label>
                    <div>{{ $lesson->duration_minutes ? $lesson->duration_minutes.' min' : '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $lesson->status }}" /></div>
                </div>
                @if($lesson->external_url)
                    <div class="form-field full">
                        <label>External URL</label>
                        <div><a href="{{ $lesson->external_url }}" target="_blank" rel="noopener">{{ $lesson->external_url }}</a></div>
                    </div>
                @endif
                @if($lesson->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $lesson->description }}</div>
                    </div>
                @endif
                @if($lesson->objectives)
                    <div class="form-field full">
                        <label>Objectives</label>
                        <div style="white-space:pre-line">{{ $lesson->objectives }}</div>
                    </div>
                @endif
                @if($lesson->content)
                    <div class="form-field full">
                        <label>Content</label>
                        <div style="white-space:pre-line">{{ $lesson->content }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-paperclip"></i> Materials</h3>
            <span class="user-status">{{ $lesson->materials->count() }} materials</span>
        </div>
        <div class="user-panel-body">
            @if($lesson->materials->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Type</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lesson->materials as $material)
                                <tr>
                                    <td>{{ $material->title ?? $material->name ?? $material->file_name ?? 'Material' }}</td>
                                    <td><span class="user-status">{{ $material->type ?? 'file' }}</span></td>
                                    <td>{{ $material->is_required ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-paperclip"
                    title="No materials"
                    description="Materials can be attached from the instructor interface."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.courses.modules.lessons.destroy', [$module->course_id, $module, $lesson]) }}" method="POST" onsubmit="return confirm('Delete this lesson?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

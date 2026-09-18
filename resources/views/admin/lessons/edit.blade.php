@extends('layouts.admin')

@section('title', 'Edit Lesson')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Lesson"
        subtitle="Update lesson content and publishing state."
        icon="fa-list-check"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.courses.modules.lessons.show', [$module->course_id, $module, $lesson]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Lesson Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.courses.modules.lessons.update', [$module->course_id, $module, $lesson]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required placeholder="Lesson title">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Module</label>
                        <select name="module_id">
                            @foreach($modules as $m)
                                <option value="{{ $m->id }}" @selected(old('module_id', $lesson->module_id) == $m->id)>{{ $m->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('module_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Lesson Type <span class="required">*</span></label>
                        <select name="lesson_type" required>
                            @foreach(['text' => 'Text', 'video' => 'Video', 'audio' => 'Audio', 'pdf' => 'PDF', 'document' => 'Document', 'presentation' => 'Presentation', 'external' => 'External Link'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('lesson_type', $lesson->lesson_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('lesson_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $lesson->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" min="0" value="{{ old('duration_minutes', $lesson->duration_minutes) }}">
                        <span class="field-error">{{ $errors->first('duration_minutes') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Position</label>
                        <input type="number" name="position" min="0" value="{{ old('position', $lesson->position) }}">
                        <span class="field-error">{{ $errors->first('position') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_required" value="1" @checked(old('is_required', $lesson->is_required))>
                            Required lesson
                        </label>
                    </div>

                    <div class="form-field">
                        <label>Available from</label>
                        <input type="datetime-local" name="availability_from" value="{{ old('availability_from', $lesson->availability_from?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('availability_from') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Available until</label>
                        <input type="datetime-local" name="availability_until" value="{{ old('availability_until', $lesson->availability_until?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('availability_until') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>External URL (for external lessons)</label>
                        <input type="url" name="external_url" value="{{ old('external_url', $lesson->external_url) }}" placeholder="https://...">
                        <span class="field-error">{{ $errors->first('external_url') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Short description">{{ old('description', $lesson->description) }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Objectives</label>
                        <textarea name="objectives" rows="3" placeholder="Learning objectives">{{ old('objectives', $lesson->objectives) }}</textarea>
                        <span class="field-error">{{ $errors->first('objectives') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Content</label>
                        <textarea name="content" rows="8" placeholder="Lesson content">{{ old('content', $lesson->content) }}</textarea>
                        <span class="field-error">{{ $errors->first('content') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.courses.modules.lessons.show', [$module->course_id, $module, $lesson]) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

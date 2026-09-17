@extends('layouts.admin')

@section('title', 'New Lesson')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Lesson"
        subtitle="Add a lesson to {{ $module->title }}."
        icon="fa-list-check"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.courses.modules.lessons.index', [$module->course_id, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Lesson Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.courses.modules.lessons.store', [$module->course_id, $module]) }}" method="POST">
                @csrf
                <input type="hidden" name="module_id" value="{{ $module->id }}">

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="Lesson title">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Module</label>
                        <select name="module_id" disabled>
                            @foreach($modules as $m)
                                <option value="{{ $m->id }}" @selected($m->id === $module->id)>{{ $m->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Lesson Type <span class="required">*</span></label>
                        <select name="lesson_type" required>
                            @foreach(['text' => 'Text', 'video' => 'Video', 'audio' => 'Audio', 'pdf' => 'PDF', 'document' => 'Document', 'presentation' => 'Presentation', 'external' => 'External Link'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('lesson_type', 'text') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('lesson_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" min="0" value="{{ old('duration_minutes') }}">
                        <span class="field-error">{{ $errors->first('duration_minutes') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_required" value="1" @checked(old('is_required'))>
                            Required lesson
                        </label>
                    </div>

                    <div class="form-field">
                        <label>Available from</label>
                        <input type="datetime-local" name="availability_from" value="{{ old('availability_from') }}">
                        <span class="field-error">{{ $errors->first('availability_from') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Available until</label>
                        <input type="datetime-local" name="availability_until" value="{{ old('availability_until') }}">
                        <span class="field-error">{{ $errors->first('availability_until') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>External URL (for external lessons)</label>
                        <input type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://...">
                        <span class="field-error">{{ $errors->first('external_url') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Short description">{{ old('description') }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Objectives</label>
                        <textarea name="objectives" rows="3" placeholder="Learning objectives">{{ old('objectives') }}</textarea>
                        <span class="field-error">{{ $errors->first('objectives') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Content</label>
                        <textarea name="content" rows="8" placeholder="Lesson content (text, embedded media, or instructions)">{{ old('content') }}</textarea>
                        <span class="field-error">{{ $errors->first('content') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.courses.modules.lessons.index', [$module->course_id, $module]) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

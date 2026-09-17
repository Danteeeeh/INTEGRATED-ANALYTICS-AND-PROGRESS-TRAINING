@extends('layouts.instructor')
@section('title', 'Edit Announcement')
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Announcement"
        subtitle="{{ $course->title }}"
        icon="fa-bullhorn"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.announcements.show', [$course, $announcement]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-pen"></i> Announcement Details</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.announcements.update', [$course, $announcement]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required>
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Body <span class="required">*</span></label>
                        <textarea name="body" rows="6" required>{{ old('body', $announcement->body) }}</textarea>
                        <span class="field-error">{{ $errors->first('body') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Audience</label>
                        <select name="audience_type">
                            @foreach($audienceTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience_type', $announcement->audience_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('audience_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— None —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $announcement->class_id) == $class->id)>{{ $class->code }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $announcement->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned))>
                            Pin this announcement
                        </label>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.courses.announcements.show', [$course, $announcement]) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

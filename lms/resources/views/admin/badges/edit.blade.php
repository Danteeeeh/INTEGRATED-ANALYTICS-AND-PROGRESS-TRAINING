@extends('layouts.admin')

@section('title', 'Edit Badge')
@php $activeNav = 'badges'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Badge"
        subtitle="Update badge details and criteria."
        icon="fa-medal"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.badges.show', $badge) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Badge Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.badges.update', $badge) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $badge->name) }}" required placeholder="e.g. Perfect Attendance">
                        <span class="field-error">{{ $errors->first('name') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Badge Type <span class="required">*</span></label>
                        <select name="badge_type" required>
                            @foreach(['course' => 'Course', 'competency' => 'Competency', 'achievement' => 'Achievement', 'participation' => 'Participation'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('badge_type', $badge->badge_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('badge_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $badge->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Icon (Font Awesome class)</label>
                        <input type="text" name="icon" value="{{ old('icon', $badge->icon) }}" placeholder="e.g. fa-trophy">
                        <span class="field-error">{{ $errors->first('icon') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— None —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $badge->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— None —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $badge->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Criteria Description</label>
                        <textarea name="criteria_description" rows="3" placeholder="How is this badge earned?">{{ old('criteria_description', $badge->criteria_description) }}</textarea>
                        <span class="field-error">{{ $errors->first('criteria_description') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Badge description">{{ old('description', $badge->description) }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.badges.show', $badge) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Badge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit Announcement')
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Announcement"
        subtitle="Update announcement details and audience."
        icon="fa-bullhorn"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.announcements.show', $announcement) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Announcement Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required placeholder="Announcement title">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Body <span class="required">*</span></label>
                        <textarea name="body" rows="6" required placeholder="Announcement message">{{ old('body', $announcement->body) }}</textarea>
                        <span class="field-error">{{ $errors->first('body') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Audience <span class="required">*</span></label>
                        <select name="audience_type" id="audienceType" required>
                            @foreach(['institution' => 'Institution', 'course' => 'Course', 'class' => 'Class', 'role' => 'Role', 'users' => 'Specific Users'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('audience_type', $announcement->audience_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('audience_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $announcement->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field audience-field" data-audience="course">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— Select course —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $announcement->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field audience-field" data-audience="class">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— Select class —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $announcement->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field audience-field" data-audience="role">
                        <label>Target Role</label>
                        <select name="target_role_id">
                            <option value="">— Select role —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('target_role_id', $announcement->target_role_id) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('target_role_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Schedule (optional)</label>
                        <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $announcement->publish_at?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('publish_at') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned))>
                            Pin this announcement
                        </label>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.announcements.show', $announcement) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const type = document.getElementById('audienceType');
        if (!type) return;
        const sync = function () {
            document.querySelectorAll('.audience-field').forEach(function (el) {
                el.style.display = el.dataset.audience === type.value ? '' : 'none';
            });
        };
        type.addEventListener('change', sync);
        sync();
    });
</script>
@endpush
@endsection

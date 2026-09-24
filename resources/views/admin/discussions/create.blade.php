@extends('layouts.admin-sms')

@section('title', 'Create Discussion')
@php
    $activeNav = 'courses';
    $pageTitle = 'Create Discussion';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Create Discussion
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Create Discussion</h3>
        <form method="POST" action="{{ route('admin.discussions.store') }}">
            @csrf
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="Discussion title" value="{{ old('title') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Course</label>
                    <select name="course_id">
                        <option value="">Select Course (optional)</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name ?? $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label>Class</label>
                    <select name="class_id">
                        <option value="">Select Class (optional)</option>
                        @foreach($classes ?? [] as $class)
                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->code }} ({{ $class->course->code ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field">
                    <label>Type <span class="req">*</span></label>
                    <select name="type" required>
                        <option value="">Select Type</option>
                        <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="academic" {{ old('type') == 'academic' ? 'selected' : '' }}>Academic</option>
                        <option value="qna" {{ old('type') == 'qna' ? 'selected' : '' }}>Q&amp;A</option>
                        <option value="graded" {{ old('type') == 'graded' ? 'selected' : '' }}>Graded</option>
                    </select>
                </div>

                <div class="form-field">
                    <label>Options</label>
                    <div style="display: flex; gap: 20px; padding: 10px 0;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            <span style="font-size: 0.88rem;">Pin this discussion</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_locked" value="1" {{ old('is_locked') ? 'checked' : '' }} style="width: 18px; height: 18px;">
                            <span style="font-size: 0.88rem;">Lock this discussion</span>
                        </label>
                    </div>
                </div>

                <div class="form-field full">
                    <label>Content <span class="req">*</span></label>
                    <textarea name="content" rows="8" required placeholder="Write your discussion content here...">{{ old('content') }}</textarea>
                    <span class="field-error"></span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Discussion</button>
                <a href="{{ route('admin.discussions.index') }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.instructor')

@section('title', 'Edit Lesson — ' . $lesson->title)
@php
    $activeNav = 'courses';
    $pageTitle = 'Edit Lesson';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Lesson — {{ $lesson->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Lesson
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card course-form-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-book-open"></i> Lesson Information</h3>
        </div>
        <form method="POST" action="{{ route('instructor.courses.modules.lessons.update', [$course, $module, $lesson]) }}">
            @csrf
            @method('PUT')
            <div class="modal-section" style="padding: 24px;">
                <div class="modal-grid">
                    <div class="modal-row">
                        <label>Lesson Title <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required class="form-input">
                        @error('title')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Lesson Type <span style="color:#dc2626;">*</span></label>
                        <select name="lesson_type" required class="form-input">
                            @foreach($lessonTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('lesson_type', $lesson->lesson_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('lesson_type')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $lesson->duration_minutes) }}" min="0" class="form-input">
                        @error('duration_minutes')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Status <span style="color:#dc2626;">*</span></label>
                        <select name="status" required class="form-input">
                            <option value="draft" {{ old('status', $lesson->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $lesson->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status', $lesson->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>External URL</label>
                    <input type="url" name="external_url" value="{{ old('external_url', $lesson->external_url) }}" class="form-input" placeholder="https://...">
                    @error('external_url')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-input">{{ old('description', $lesson->description) }}</textarea>
                    @error('description')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Objectives</label>
                    <textarea name="objectives" rows="3" class="form-input">{{ old('objectives', $lesson->objectives) }}</textarea>
                    @error('objectives')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Content</label>
                    <textarea name="content" rows="6" class="form-input">{{ old('content', $lesson->content) }}</textarea>
                    @error('content')<span class="error-message">{{ $message }}</span>@enderror
                </div>
            </div>

            @php
                $crRules = is_array($lesson->completion_rules) ? $lesson->completion_rules : [];
                $crContent = (bool) ($crRules['require_content_view'] ?? false);
                $crMaterials = (bool) ($crRules['require_all_materials'] ?? false);
                $crMinutes = (int) ($crRules['min_minutes'] ?? 0);
            @endphp
            <div class="modal-section" style="padding: 24px; border-top: 1px solid var(--lms-border, rgba(148,174,222,.18));">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="fa-solid fa-clipboard-check" style="color:var(--lms-accent,#62c9f5);"></i>
                    <label style="font-size:.95rem;font-weight:800;margin:0;">Completion Rules</label>
                </div>
                <p style="margin:0 0 14px;color:var(--dash-muted,#9eafca);font-size:.78rem;">
                    Requirements the student must meet before they can mark this lesson complete. Leave all unchecked for no requirements.
                </p>

                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;cursor:pointer;">
                    <input type="checkbox" name="cr_require_content_view" value="1" {{ old('cr_require_content_view', $crContent) ? 'checked' : '' }} style="margin-top:2px;width:16px;height:16px;">
                    <span>
                        <strong style="font-size:.85rem;">Require opening the lesson content</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Student must open this lesson page before completing.</span>
                    </span>
                </label>

                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;cursor:pointer;border-top:1px solid var(--lms-border, rgba(148,174,222,.18));">
                    <input type="checkbox" name="cr_require_all_materials" value="1" {{ old('cr_require_all_materials', $crMaterials) ? 'checked' : '' }} style="margin-top:2px;width:16px;height:16px;">
                    <span>
                        <strong style="font-size:.85rem;">Require reviewing all materials</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Student must open every attached material at least once.</span>
                    </span>
                </label>

                <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-top:1px solid var(--lms-border, rgba(148,174,222,.18));">
                    <input type="number" name="cr_min_minutes" value="{{ old('cr_min_minutes', $crMinutes) }}" min="0" max="600" class="form-input" style="width:100px;" placeholder="0">
                    <span>
                        <strong style="font-size:.85rem;display:block;">Minimum time on lesson (minutes)</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Leave 0 for no time requirement.</span>
                    </span>
                </div>
            </div>

            <div class="modal-footer" style="justify-content:flex-end; gap:10px; padding: 18px 24px;">
                <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn-modal-cancel">Cancel</a>
                <button type="submit" class="btn-modal-save">
                    <i class="fa-solid fa-save"></i>
                    Update Lesson
                </button>
            </div>
        </form>
    </div>
@endsection

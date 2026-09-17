@extends('layouts.instructor')

@section('title', 'Edit Assignment — ' . $assignment->title)
@php
    $activeNav = 'courses';
    $pageTitle = 'Edit Assignment';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Assignment — {{ $assignment->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.assignments.show', [$course, $assignment]) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card course-form-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-file-pen"></i> Assignment Details</h3>
        </div>
        <form method="POST" action="{{ route('instructor.courses.assignments.update', [$course, $assignment]) }}">
            @csrf
            @method('PUT')
            <div class="modal-section" style="padding: 24px;">
                <div class="modal-grid">
                    <div class="modal-row">
                        <label>Title <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $assignment->title) }}" required class="form-input">
                        @error('title')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal-row">
                        <label>Class</label>
                        <select name="class_id" class="form-input">
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id', $assignment->class_id) == $class->id ? 'selected' : '' }}>{{ $class->code }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal-row">
                        <label>Submission Type <span style="color:#dc2626;">*</span></label>
                        <select name="submission_type" required class="form-input">
                            @foreach($submissionTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('submission_type', $assignment->submission_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('submission_type')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal-row">
                        <label>Points</label>
                        <input type="number" name="points" value="{{ old('points', $assignment->points) }}" min="0" class="form-input">
                        @error('points')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal-row">
                        <label>Due Date</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date', $assignment->due_date?->format('Y-m-d\TH:i')) }}" class="form-input">
                        @error('due_date')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                    <div class="modal-row">
                        <label>Status <span style="color:#dc2626;">*</span></label>
                        <select name="status" required class="form-input">
                            <option value="draft" {{ old('status', $assignment->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $assignment->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="closed" {{ old('status', $assignment->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        @error('status')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Instructions</label>
                    <textarea name="instructions" rows="5" class="form-input">{{ old('instructions', $assignment->instructions) }}</textarea>
                    @error('instructions')<span class="error-message">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="modal-footer" style="justify-content:flex-end; gap:10px; padding: 18px 24px;">
                <a href="{{ route('instructor.courses.assignments.show', [$course, $assignment]) }}" class="btn-modal-cancel">Cancel</a>
                <button type="submit" class="btn-modal-save"><i class="fa-solid fa-save"></i> Update Assignment</button>
            </div>
        </form>
    </div>
@endsection

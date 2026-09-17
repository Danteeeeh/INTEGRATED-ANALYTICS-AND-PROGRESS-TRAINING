@extends('layouts.admin-sms')

@section('title', 'Edit Assignment')
@php
    $activeNav = 'courses';
    $pageTitle = 'Edit Assignment';
    $pageIcon = '<i class="fa-solid fa-file-circle-check"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-circle-check"></i>
            Edit Assignment
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Edit Assignment</h3>
        <form method="POST" action="{{ route('admin.assignments.update', $assignment) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Midterm Project" value="{{ old('title', $assignment->title) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Instructions</label>
                    <textarea name="instructions" rows="5" placeholder="Assignment instructions and requirements...">{{ old('instructions', $assignment->instructions) }}</textarea>
                </div>

                <div class="form-field">
                    <label>Class <span class="req">*</span></label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        @foreach($classes ?? [] as $class)
                            <option value="{{ $class->id }}" {{ old('class_id', $assignment->class_id) == $class->id ? 'selected' : '' }}>
                                {{ $class->name }} ({{ $class->course->name ?? 'No Course' }})
                            </option>
                        @endforeach
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Points <span class="req">*</span></label>
                    <input type="number" name="points" required min="0" placeholder="e.g. 100" value="{{ old('points', $assignment->points) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Due Date <span class="req">*</span></label>
                    <input type="datetime-local" name="due_date" required value="{{ old('due_date', $assignment->due_date ? $assignment->due_date->format('Y-m-d\TH:i') : '') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Submission Type <span class="req">*</span></label>
                    <select name="submission_type" required>
                        <option value="">Select Type</option>
                        <option value="{{ \App\Models\Assignment::TYPE_TEXT }}" {{ old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_TEXT ? 'selected' : '' }}>Text Only</option>
                        <option value="{{ \App\Models\Assignment::TYPE_FILE }}" {{ old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_FILE ? 'selected' : '' }}>Single File</option>
                        <option value="{{ \App\Models\Assignment::TYPE_MULTIPLE_FILES }}" {{ old('submission_type', $assignment->submission_type) == \App\Models\Assignment::TYPE_MULTIPLE_FILES ? 'selected' : '' }}>Multiple Files</option>
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Availability From</label>
                    <input type="datetime-local" name="availability_from" value="{{ old('availability_from', $assignment->availability_from ? $assignment->availability_from->format('Y-m-d\TH:i') : '') }}">
                </div>

                <div class="form-field">
                    <label>Availability Until</label>
                    <input type="datetime-local" name="availability_until" value="{{ old('availability_until', $assignment->availability_until ? $assignment->availability_until->format('Y-m-d\TH:i') : '') }}">
                </div>

                <div class="form-field">
                    <label>Resubmission Limit</label>
                    <input type="number" name="resubmission_limit" min="0" placeholder="e.g. 3 (0 = unlimited)" value="{{ old('resubmission_limit', $assignment->max_attempts ?? 0) }}">
                </div>

                <div class="form-field">
                    <label>Rubric</label>
                    <select name="rubric_id">
                        <option value="">None</option>
                        @foreach($rubrics ?? [] as $rubric)
                            <option value="{{ $rubric->id }}" {{ old('rubric_id', $assignment->rubric_id) == $rubric->id ? 'selected' : '' }}>
                                {{ $rubric->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-field full">
                    <label style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="allow_late" value="1" {{ old('allow_late', $assignment->allow_late) ? 'checked' : '' }} style="width:auto;">
                        Allow Late Submissions
                    </label>
                </div>

                <div class="form-field full">
                    <label>Status</label>
                    <select name="status">
                        <option value="{{ \App\Models\Assignment::STATUS_DRAFT }}" {{ old('status', $assignment->status) == \App\Models\Assignment::STATUS_DRAFT ? 'selected' : '' }}>Draft</option>
                        <option value="{{ \App\Models\Assignment::STATUS_PUBLISHED }}" {{ old('status', $assignment->status) == \App\Models\Assignment::STATUS_PUBLISHED ? 'selected' : '' }}>Published</option>
                        <option value="{{ \App\Models\Assignment::STATUS_CLOSED }}" {{ old('status', $assignment->status) == \App\Models\Assignment::STATUS_CLOSED ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                @if($assignment->attachments && $assignment->attachments->count() > 0)
                    <div class="form-field full">
                        <label>Current Attachments</label>
                        <div style="padding:12px;background:#f7f9fc;border-radius:8px;border:1px solid #e5e7eb;">
                            @foreach($assignment->attachments as $att)
                                <div style="padding:8px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
                                    <span><i class="fa-solid fa-paperclip" style="color:#6b7280;margin-right:6px;"></i>{{ $att->filename }}</span>
                                    <span class="btn btn-sm btn-secondary" aria-disabled="true" title="Attachment removal is not available yet"><i class="fa-solid fa-lock"></i> Remove unavailable</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="form-field full">
                    <label>Add Attachments</label>
                    <input type="file" name="attachments[]" multiple style="padding:10px;border:1.5px dashed #d0d7e2;border-radius:8px;background:#fafbfc;">
                    <span style="font-size:0.75rem;color:#888;">Upload additional files (optional)</span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Assignment</button>
                <a href="{{ route('admin.assignments.index') }}" class="btn-modal-cancel" style="display:inline-flex;align-items:center;justify-content:center;padding:11px 60px;border-radius:8px;font-size:0.92rem;font-weight:600;cursor:pointer;transition:background 0.2s;text-decoration:none;margin-left:10px;">Cancel</a>
            </div>
        </form>
    </div>
@endsection

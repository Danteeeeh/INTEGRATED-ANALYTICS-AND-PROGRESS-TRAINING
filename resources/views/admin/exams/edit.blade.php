@extends('layouts.admin')

@section('title', 'Edit Exam')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Exam"
        subtitle="{{ $exam->title }}"
        icon="fa-file-signature"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin:6px 0 0 18px;padding:0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.exams.update', $exam) }}">
        @csrf
        @method('PUT')

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-circle-info"></i> Exam details</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $exam->title) }}" required>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <option value="">Choose a class…</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) old('class_id', $exam->class_id) === $class->id)>{{ $class->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">Infer from class</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((int) old('course_id', $exam->course_id) === $course->id)>{{ $course->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="exam_type" required>
                            @foreach(['midterm', 'final', 'comprehensive', 'module', 'other'] as $value)
                                <option value="{{ $value }}" @selected(old('exam_type', $exam->exam_type) === $value)>{{ ucfirst($value) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes) <span class="required">*</span></label>
                        <input type="number" name="duration_minutes" min="1" value="{{ old('duration_minutes', $exam->duration_minutes) }}" required>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="2">{{ old('description', $exam->description) }}</textarea>
                    </div>

                    <div class="form-field full">
                        <label>Instructions</label>
                        <textarea name="instructions" rows="3">{{ old('instructions', $exam->instructions) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-sliders"></i> Scoring &amp; attempts</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Attempt limit</label>
                        <input type="number" name="attempt_limit" min="1" value="{{ old('attempt_limit', $exam->attempt_limit) }}">
                    </div>
                    <div class="form-field">
                        <label>Passing score (%)</label>
                        <input type="number" name="passing_score_percent" min="0" max="100" value="{{ old('passing_score_percent', $exam->passing_score_percent) }}">
                    </div>
                    <div class="form-field">
                        <label>Grade weight (%)</label>
                        <input type="number" name="grade_weight" min="0" max="100" value="{{ old('grade_weight', $exam->grade_weight) }}">
                    </div>
                    <div class="form-field">
                        <label>Result visibility <span class="required">*</span></label>
                        <select name="result_visibility" required>
                            @foreach(['after_grading', 'immediately', 'after_all_submissions', 'after_date', 'never'] as $value)
                                <option value="{{ $value }}" @selected(old('result_visibility', $exam->result_visibility) === $value)>{{ str_replace('_', ' ', $value) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            @foreach(['draft', 'published', 'closed'] as $value)
                                <option value="{{ $value }}" @selected(old('status', $exam->status) === $value)>{{ ucfirst($value) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-shuffle"></i> Delivery</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Opens at</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-field">
                        <label>Closes at</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="form-field full">
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="show_score" value="1" @checked(old('show_score', $exam->show_score))>
                            <span>Show the score to the student</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="allow_review" value="1" @checked(old('allow_review', $exam->allow_review))>
                            <span>Let students review their answers</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="auto_submit_on_timeout" value="1" @checked(old('auto_submit_on_timeout', $exam->auto_submit_on_timeout))>
                            <span>Auto-submit when time runs out</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
            <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
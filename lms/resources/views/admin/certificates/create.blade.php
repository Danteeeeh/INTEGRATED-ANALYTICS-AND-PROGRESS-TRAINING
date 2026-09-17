@extends('layouts.admin')

@section('title', 'Issue Certificate')
@php $activeNav = 'certificates'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Issue Certificate"
        subtitle="Create a completion certificate for a student."
        icon="fa-certificate"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Certificate Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.certificates.store') }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <option value="">— Select class —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $preselectedClassId) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— From class —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Student <span class="required">*</span></label>
                        <select name="student_id" required>
                            <option value="">— Select student —</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id', $preselectedStudentId) == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('student_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Template Name</label>
                        <input type="text" name="template_name" value="{{ old('template_name') }}" placeholder="e.g. Standard Completion">
                        <span class="field-error">{{ $errors->first('template_name') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Student Name Display</label>
                        <input type="text" name="student_name_display" value="{{ old('student_name_display') }}" placeholder="Overrides student name">
                        <span class="field-error">{{ $errors->first('student_name_display') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course Name Display</label>
                        <input type="text" name="course_name_display" value="{{ old('course_name_display') }}" placeholder="Overrides course name">
                        <span class="field-error">{{ $errors->first('course_name_display') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Completion Date</label>
                        <input type="date" name="completion_date" value="{{ old('completion_date', now()->format('Y-m-d')) }}">
                        <span class="field-error">{{ $errors->first('completion_date') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Final Grade</label>
                        <input type="number" name="final_grade" min="0" max="100" step="0.01" value="{{ old('final_grade') }}">
                        <span class="field-error">{{ $errors->first('final_grade') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="issued" @selected(old('status', 'issued') === 'issued')>Issued</option>
                            <option value="revoked" @selected(old('status') === 'revoked')>Revoked</option>
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-certificate"></i> Issue Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

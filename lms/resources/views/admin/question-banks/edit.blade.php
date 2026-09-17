@extends('layouts.admin')

@section('title', 'Edit Question Bank')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Question Bank"
        subtitle="Update bank metadata and associations."
        icon="fa-database"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.question_banks.show', $questionBank) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Bank Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.question_banks.update', $questionBank) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $questionBank->title) }}" required placeholder="e.g. Midterm Biology Questions">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $questionBank->code) }}" placeholder="e.g. BIO-MID" maxlength="50">
                        <span class="field-error">{{ $errors->first('code') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category', $questionBank->category) }}" placeholder="e.g. Biology" maxlength="100">
                        <span class="field-error">{{ $errors->first('category') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— General —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $questionBank->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— General —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $questionBank->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $questionBank->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_shared" value="1" @checked(old('is_shared', $questionBank->is_shared))>
                            Share this bank across courses
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Bank description">{{ old('description', $questionBank->description) }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.question_banks.show', $questionBank) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Bank
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

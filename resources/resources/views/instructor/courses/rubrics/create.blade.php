@extends('layouts.instructor')
@section('title', 'New Rubric')
@php $activeNav = 'rubrics'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Rubric"
        subtitle="{{ $course->title }}"
        icon="fa-table-list"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.rubrics.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-pen"></i> Rubric Details</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.rubrics.store', $course) }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Essay Rubric">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— General —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->code }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_shared" value="1" @checked(old('is_shared'))>
                            Share this rubric across courses
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Rubric description">{{ old('description') }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.courses.rubrics.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Create Rubric</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

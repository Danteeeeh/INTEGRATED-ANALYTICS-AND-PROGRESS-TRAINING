@extends('layouts.registrar')
@section('title', 'Enroll Student')
@php $activeNav = 'enrollments'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Enroll Student"
        subtitle="Add a student to a class."
        icon="fa-user-plus"
    >
        <x-slot name="actions">
            <a href="{{ route('registrar.enrollments.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-user-plus"></i> Enrollment Details</h3></div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('registrar.enrollments.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Student <span class="required">*</span></label>
                        <select name="student_id" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('student_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <option value="">Select class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('registrar.enrollments.index') }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus"></i> Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

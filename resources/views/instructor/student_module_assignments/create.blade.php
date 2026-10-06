@extends('layouts.instructor')

@section('title', 'Assign Module to Student')
@php
    $activeNav = 'modules';
    $pageTitle = 'Assign Module';
    $pageIcon = '<i class="fa-solid fa-plus"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-plus"></i>
            Assign Module to Student
        </h2>
    </div>
@endsection

@section('content')
<div class="admin-shell">
    <x-user-page-header
        title="Assign Module to Student"
        subtitle="Select a student and module to create a personalized assignment."
        icon="fa-plus"
        kicker="Class: {{ $class->code }}"
    >
        <x-slot name="meta">
            <span>{{ $class->course->title ?? '' }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.student_module_assignments.index', $class) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to Assignments
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="admin-panel">
        <div class="admin-panel-head">
            <h4><i class="fa-solid fa-plus"></i> New Module Assignment</h4>
        </div>
        <div class="admin-panel-body">
            <form action="{{ route('instructor.classes.student_module_assignments.store', $class) }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="student_id">Student</label>
                    <select name="student_id" id="student_id" class="form-control" required>
                        <option value="">Select a student...</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="module_id">Module</label>
                    <select name="module_id" id="module_id" class="form-control" required>
                        <option value="">Select a module...</option>
                        @foreach($modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }} ({{ $module->course->title ?? '' }})</option>
                        @endforeach
                    </select>
                    @error('module_id')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-actions">
                    <a href="{{ route('instructor.classes.student_module_assignments.index', $class) }}" class="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-plus"></i> Assign Module
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

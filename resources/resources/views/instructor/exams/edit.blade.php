@extends('layouts.instructor')

@section('title', 'Edit Exam')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Exam"
        subtitle="Update the schedule, timing rules, and result visibility."
        icon="fa-file-signature"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $exam->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View</a>
            <a href="{{ route('instructor.exams.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin:6px 0 0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Exam Details</h3>
        </div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('instructor.courses.exams.update', [$course, $exam]) }}">
                @include('instructor.exams._form', [
                    'exam' => $exam,
                    'courses' => $courses,
                    'classes' => $classes,
                    'method' => 'PUT',
                ])

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('instructor.courses.exams.destroy', [$course, $exam]) }}"
          style="margin-top:16px;" onsubmit="return confirm('Delete this exam?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete Exam</button>
    </form>
</div>
@endsection
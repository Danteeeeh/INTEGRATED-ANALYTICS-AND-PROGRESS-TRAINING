@extends('layouts.instructor')

@section('title', 'New Exam')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Exam"
        subtitle="Set the schedule, timing rules, and result visibility."
        icon="fa-file-signature"
    >
        <x-slot name="actions">
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
            <h3><i class="fa-solid fa-pen-to-square"></i> Exam Details</h3>
        </div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('instructor.exams.store') }}">
                @include('instructor.exams._form', [
                    'exam' => new \App\Models\Exam(),
                    'courses' => $courses,
                    'classes' => $classes,
                    'method' => 'POST',
                ])

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.exams.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Exam
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
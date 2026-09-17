@extends('layouts.student')
@section('title', 'Submit Assignment')
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Submit Assignment"
        subtitle="{{ $assignment->title }} — {{ $course->title }}"
        icon="fa-paper-plane"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.assignments.show', [$course, $assignment]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-upload"></i> Your Submission</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('student.courses.assignments.submit.store', [$course, $assignment]) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Your Answer <span class="required">*</span></label>
                        <textarea name="content" rows="8" required placeholder="Write your submission here...">{{ old('content', $existingSubmission->content ?? '') }}</textarea>
                        <span class="field-error">{{ $errors->first('content') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Attach Files</label>
                        <input type="file" name="files[]" multiple>
                        <span class="field-error">{{ $errors->first('files') }}</span>
                        <small class="user-email">You may attach one or more files.</small>
                    </div>
                </div>

                @if($existingSubmission)
                    <div class="user-toolbar" style="margin-top:12px">
                        <span class="user-status pending">Existing submission</span>
                        <span class="user-email">Submitted {{ $existingSubmission->created_at?->format('M j, Y g:i A') }}</span>
                    </div>
                @endif

                <div class="form-actions user-actions">
                    <a href="{{ route('student.courses.assignments.show', [$course, $assignment]) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Assignment</button>
                </div>
            </form>
        </div>
    </div>

    @if($assignment->due_date)
        <div class="learning-next-action">
            <div>
                <strong>Due {{ $assignment->due_date->format('l, F j, Y g:i A') }}</strong>
                <span>{{ $assignment->due_date->isPast() ? 'This assignment is overdue — late submission may be penalized.' : 'Submit before the deadline to avoid penalties.' }}</span>
            </div>
        </div>
    @endif
</div>
@endsection

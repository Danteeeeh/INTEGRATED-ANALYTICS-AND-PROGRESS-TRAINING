@extends('layouts.instructor')
@section('title', 'Submission Review')
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Submission Review"
        subtitle="{{ $assignment->title }} — {{ $submission->student?->full_name ?? $submission->student?->name ?? 'Student' }}"
        icon="fa-tasks"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $submission->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.assignments.submissions', [$course, $assignment]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-user"></i> Student</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div>{{ $submission->student?->full_name ?? $submission->student?->name ?? '—' }}</div>
                    <div class="user-email">{{ $submission->student?->email }}</div>
                </div>
                <div class="form-field">
                    <label>Submitted</label>
                    <div>{{ $submission->submitted_at?->format('M j, Y g:i A') ?? $submission->created_at?->format('M j, Y g:i A') }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $submission->status }}" /></div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment"></i> Submission Content</h3></div>
        <div class="user-panel-body">
            @if($submission->content)
                <div style="white-space:pre-line">{{ $submission->content }}</div>
            @elseif($submission->text)
                <div style="white-space:pre-line">{{ $submission->text }}</div>
            @else
                <p class="user-email">No text content provided.</p>
            @endif

            @if($submission->files && $submission->files->count() > 0)
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attached Files</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        @foreach($submission->files as $file)
                            <a href="{{ $file->url ?? $file->path ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file"></i> {{ $file->original_name ?? $file->name ?? 'File' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Grade Submission</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.assignments.submissions.grade', [$course, $assignment, $submission]) }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Points (max {{ $assignment->points }}) <span class="required">*</span></label>
                        <input type="number" name="points" min="0" max="{{ $assignment->points }}" step="0.1" required
                               value="{{ old('points', $submission->grade?->points) }}">
                        <span class="field-error">{{ $errors->first('points') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="4" placeholder="Feedback for the student">{{ old('feedback', $submission->grade?->feedback) }}</textarea>
                        <span class="field-error">{{ $errors->first('feedback') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

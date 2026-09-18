@extends('layouts.student')
@section('title', 'Submission')
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Submission"
        subtitle="{{ $assignment->title }} — {{ $course->title }}"
        icon="fa-file-circle-check"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $submission->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.assignments.show', [$course, $assignment]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment"></i> Submitted Content</h3></div>
        <div class="user-panel-body">
            @if($submission->content)
                <div style="white-space:pre-line">{{ $submission->content }}</div>
            @else
                <p class="user-email">No text content.</p>
            @endif

            @if($submission->files && $submission->files->count() > 0)
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attached Files</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        @foreach($submission->files as $file)
                            <a href="{{ $file->mediaFile?->url ?? $file->url ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-file"></i> {{ $file->mediaFile?->original_name ?? $file->original_name ?? 'File' }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Grade &amp; Feedback</h3></div>
        <div class="user-panel-body">
            @if($submission->grade)
                <div class="form-grid">
                    <div class="form-field">
                        <label>Score</label>
                        <div><strong>{{ number_format($submission->grade->score_percent ?? 0, 1) }}%</strong> ({{ $submission->grade->points ?? 0 }}/{{ $assignment->points }})</div>
                    </div>
                    <div class="form-field">
                        <label>Graded</label>
                        <div>{{ $submission->grade->graded_at?->format('M j, Y g:i A') ?? '—' }}</div>
                    </div>
                    @if($submission->grade->feedback)
                        <div class="form-field full">
                            <label>Feedback</label>
                            <div style="white-space:pre-line">{{ $submission->grade->feedback }}</div>
                        </div>
                    @endif
                </div>
            @else
                <x-user-empty-state icon="fa-clock" title="Not graded yet" description="Your instructor will grade this submission soon." />
            @endif
        </div>
    </div>
</div>
@endsection

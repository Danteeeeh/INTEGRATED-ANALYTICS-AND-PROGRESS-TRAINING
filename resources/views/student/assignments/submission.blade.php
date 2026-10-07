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
            @if($submission->submission_text)
                <div style="white-space:pre-line">{{ $submission->submission_text }}</div>
            @else
                <p class="user-email">No text content.</p>
            @endif

            @if($submission->files && $submission->files->count() > 0)
                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-paperclip"></i> Attached Files</div>
                    <div class="user-actions" style="justify-content:flex-start">
                        @foreach($submission->files as $file)
                            @php
                                // Same rule as the instructor's copy: a link to a
                                // deleted row or a row whose bytes left storage just
                                // 404s, so show the state instead of a dead link.
                                $media = $file->mediaFile;
                                $available = $media && $media->url && $media->fileExists();
                            @endphp

                            @if ($available)
                                <a href="{{ $media->url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-file"></i> {{ $file->original_name ?? $media->original_name ?? 'File' }}
                                </a>
                            @else
                                <span class="btn btn-secondary btn-sm" style="opacity:.6;cursor:not-allowed;"
                                      title="{{ $media ? 'This file is no longer available on the server.' : 'The linked file record was deleted.' }}">
                                    <i class="fa-solid fa-file-circle-exclamation"></i>
                                    {{ $file->original_name ?? 'File' }}
                                    &mdash; unavailable
                                </span>
                            @endif
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

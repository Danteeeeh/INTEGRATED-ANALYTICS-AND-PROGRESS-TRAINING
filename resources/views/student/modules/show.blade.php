@extends('layouts.student')
@section('title', $module->title)
@php $activeNav = 'modules'; @endphp

@push('styles')
<style>
.module-video-container {
    display: grid;
    gap: 16px;
}
.module-video-thumbnail {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: #1e293b;
    aspect-ratio: 16/9;
}
.module-video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.module-video-play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: grid;
    place-items: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.9);
    color: #fff;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    text-decoration: none;
}
.module-video-play-btn:hover {
    background: rgba(239, 68, 68, 1);
    transform: translate(-50%, -50%) scale(1.1);
}
.module-video-info {
    padding: 12px;
    background: rgba(139, 92, 246, 0.08);
    border-radius: 8px;
    border: 1px solid rgba(139, 92, 246, 0.18);
}
</style>
@endpush

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $module->title }}"
        subtitle="{{ $course->title }} — module lessons"
        icon="fa-cubes"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
            @if($module->is_required)<span class="user-status active">Required</span>@endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.modules.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($module->description)
        <div class="user-panel">
            <div class="user-panel-body">
                <p style="margin:0;color:var(--dash-muted)">{{ $module->description }}</p>
            </div>
        </div>
    @endif

    @if($module->external_video_url)
    <div class="user-panel">
        <div class="user-panel-head">
            <span class="user-kicker"><i class="fa-solid fa-video"></i> External Video</span>
            <h3>Video Resource</h3>
        </div>
        <div class="user-panel-body">
            <div class="module-video-container">
                @if($module->getYouTubeThumbnailUrl())
                    <div class="module-video-thumbnail">
                        <img src="{{ $module->getYouTubeThumbnailUrl() }}" alt="Video thumbnail" loading="lazy">
                        @if($module->isYouTubeVideo())
                            <a href="{{ $module->external_video_url }}" target="_blank" class="module-video-play-btn">
                                <i class="fa-solid fa-play"></i>
                            </a>
                        @endif
                    </div>
                @endif
                <div class="module-video-info">
                    <p style="margin: 0 0 8px; font-size: 0.85rem; color: #64748b;">
                        <i class="fa-solid fa-link" style="margin-right: 6px;"></i>
                        <a href="{{ $module->external_video_url }}" target="_blank" style="color: #2563eb; text-decoration: none;">
                            {{ Str::limit($module->external_video_url, 60) }}
                        </a>
                    </p>
                    @if($module->isYouTubeVideo())
                        <p style="margin: 0; font-size: 0.75rem; color: #94a3b8;">
                            <i class="fa-brands fa-youtube" style="margin-right: 6px;"></i>
                            YouTube Video
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($module->lessons->count() > 0)
        <div class="learning-grid">
            @foreach($module->lessons as $lesson)
                @php
                    $p = $lesson->progress->first();
                    $completed = $p && $p->status === 'completed';
                    $lessonHasRules = is_array($lesson->completion_rules) && count(array_filter($lesson->completion_rules ?? []));
                    $lessonRuleLabel = $lessonHasRules ? 'Has completion requirements' : null;
                @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            Lesson {{ $lesson->position ?? $loop->iteration }}
                            @if($completed)<span style="color:var(--user-success)"> · Completed</span>@endif
                            @if($lessonHasRules && ! $completed)<span style="color:var(--lms-gold,#f6c84a)"> · <i class="fa-solid fa-clipboard-check"></i> Requirements</span>@endif
                        </div>
                        <h3>{{ $lesson->title }}</h3>
                        <p>{{ Str::limit($lesson->description ?? '', 80) }}</p>
                    </div>
                    <div>
                        @if($p)
                            <div class="learning-progress"><span style="width: {{ min(100, round($p->progress_percent ?? 0)) }}%"></span></div>
                        @endif
                        <div class="user-actions" style="justify-content:flex-end;margin-top:10px">
                            <a href="{{ route('student.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid {{ $completed ? 'fa-circle-check' : 'fa-play' }}"></i> {{ $completed ? 'Review' : 'Start' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-user-empty-state icon="fa-cubes" title="No lessons" description="No published lessons in this module yet." />
    @endif
</div>
@endsection

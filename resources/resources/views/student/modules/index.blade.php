@extends('layouts.student')
@section('title', 'Course Modules')
@php $activeNav = 'modules'; @endphp

@push('styles')
<style>
.module-video-thumb-wrapper {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: #1e293b;
    aspect-ratio: 16/9;
    margin-bottom: 12px;
}
.module-video-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.module-video-thumb-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(239, 68, 68, 0.9);
    color: #fff;
    font-size: 1.3rem;
    transition: all 0.3s ease;
    text-decoration: none;
}
.module-video-thumb-play:hover {
    background: rgba(239, 68, 68, 1);
    transform: translate(-50%, -50%) scale(1.1);
}
</style>
@endpush

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Course Modules"
        subtitle="{{ $course->title }} — learning modules"
        icon="fa-cubes"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($modules->count() > 0)
        <div class="learning-grid">
            @foreach($modules as $module)
                <div class="learning-card">
                    @if($module->external_video_url && $module->getYouTubeThumbnailUrl())
                        <div class="module-video-thumb-wrapper">
                            <img src="{{ $module->getYouTubeThumbnailUrl() }}" alt="Video thumbnail" loading="lazy" class="module-video-thumb">
                            @if($module->isYouTubeVideo())
                                <a href="{{ $module->external_video_url }}" target="_blank" class="module-video-thumb-play">
                                    <i class="fa-solid fa-play"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                    <div>
                        <div class="user-kicker">Module {{ $module->position ?? $loop->iteration }}</div>
                        <h3>{{ $module->title }}</h3>
                        <p>{{ Str::limit($module->description ?? '', 90) }}</p>
                    </div>
                    <div>
                        <div class="user-actions" style="justify-content:space-between">
                            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
                            <a href="{{ route('student.courses.modules.show', [$course, $module]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-book-open"></i> Open</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($modules->hasPages())
            <div class="pagination">{{ $modules->links() }}</div>
        @endif
    @else
        <x-user-empty-state icon="fa-cubes" title="No modules yet" description="No published modules for this course yet." />
    @endif
</div>
@endsection

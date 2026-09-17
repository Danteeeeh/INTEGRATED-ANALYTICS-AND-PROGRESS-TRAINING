@extends('layouts.student')
@section('title', $lesson->title)
@php $activeNav = 'lessons'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $lesson->title }}"
        subtitle="{{ $course->title }} · {{ $module->title }}"
        icon="fa-book-open"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $progress->status }}" />
            <span class="user-status">{{ round($progress->progress_percent ?? 0) }}% complete</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.modules.show', [$course, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Module</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book-open"></i> Lesson Content</h3>
            @if($lesson->duration_minutes)
                <span class="user-email">{{ $lesson->duration_minutes }} min</span>
            @endif
        </div>
        <div class="user-panel-body">
            @if($lesson->description)
                <div style="margin-bottom:12px;color:var(--dash-muted)">{{ $lesson->description }}</div>
            @endif
            @if($lesson->content)
                <div style="white-space:pre-line">{{ $lesson->content }}</div>
            @else
                <p class="user-email">This lesson has no text content.</p>
            @endif
            @if($lesson->external_url)
                <div class="user-actions" style="justify-content:flex-start;margin-top:14px">
                    <a href="{{ $lesson->external_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="fa-solid fa-up-right-from-square"></i> Open External Lesson</a>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-paperclip"></i> Materials</h3>
            <span class="user-status">{{ $lesson->materials->count() }} materials</span>
        </div>
        <div class="user-panel-body">
            @if($lesson->materials->count() > 0)
                <div class="user-actions" style="justify-content:flex-start;flex-wrap:wrap">
                    @foreach($lesson->materials as $material)
                        <a href="{{ $material->url ?? $material->path ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-file"></i> {{ $material->title ?? $material->name ?? $material->file_name ?? 'Material' }}
                            @if($material->is_required)<span class="user-status active">Required</span>@endif
                        </a>
                    @endforeach
                </div>
            @else
                <x-user-empty-state icon="fa-paperclip" title="No materials" description="No materials have been attached to this lesson." />
            @endif
        </div>
    </div>

    @if($lesson->content)
        <form action="{{ route('student.courses.modules.lessons.complete', [$course, $module, $lesson]) }}" method="POST">
            @csrf
            <div class="learning-next-action">
                <div>
                    <strong>{{ $progress->status === 'completed' ? 'Completed — you can review anytime' : 'Finished this lesson?' }}</strong>
                    <span>Mark it complete to update your progress.</span>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid {{ $progress->status === 'completed' ? 'fa-circle-check' : 'fa-check-circle' }}"></i>
                    {{ $progress->status === 'completed' ? 'Completed' : 'Mark Complete' }}
                </button>
            </div>
        </form>
    @endif
</div>
@endsection

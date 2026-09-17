@extends('layouts.instructor')

@section('title', $lesson->title)
@php
    $activeNav = 'courses';
    $pageTitle = $lesson->title;
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book-open"></i>
            {{ $lesson->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.modules.lessons.index', [$course, $module]) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Lessons
            </a>
            <a href="{{ route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson]) }}" class="btn-add">
                <i class="fa-solid fa-pen-to-square"></i>
                Edit Lesson
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card" style="margin-bottom:20px;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-info"></i> Lesson Details</h3>
            @if($lesson->status === 'published')
                <span class="badge-published">Published</span>
            @else
                <span class="badge-draft">Draft</span>
            @endif
        </div>
        <div style="padding: 20px 24px; display:flex; flex-direction:column; gap:14px;">
            <div style="display:flex; gap:24px; flex-wrap:wrap;">
                <div><span class="dash-stat-label">Type</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ ucfirst($lesson->lesson_type ?? 'text') }}</div></div>
                <div><span class="dash-stat-label">Duration</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ $lesson->duration_minutes ? $lesson->duration_minutes . ' min' : '—' }}</div></div>
                <div><span class="dash-stat-label">Position</span><div style="color:#eef4ff;font-weight:650;margin-top:3px;">{{ $lesson->position ?? '—' }}</div></div>
                @if($lesson->external_url)
                    <div><span class="dash-stat-label">External URL</span><div style="margin-top:3px;"><a href="{{ $lesson->external_url }}" target="_blank" style="color:#62c9f5;">{{ $lesson->external_url }}</a></div></div>
                @endif
            </div>

            @if($lesson->description)
                <div>
                    <span class="dash-stat-label">Description</span>
                    <p style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.5;">{{ $lesson->description }}</p>
                </div>
            @endif

            @if($lesson->objectives)
                <div>
                    <span class="dash-stat-label">Objectives</span>
                    <p style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.5;">{{ $lesson->objectives }}</p>
                </div>
            @endif

            @if($lesson->content)
                <div>
                    <span class="dash-stat-label">Content</span>
                    <div style="color:#c7d4ec;margin:4px 0 0;font-size:.88rem;line-height:1.6;white-space:pre-wrap;">{{ $lesson->content }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-folder-open"></i> Materials ({{ $lesson->materials->count() }})</h3>
            <a href="{{ route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson]) }}" class="btn-add">
                <i class="fa-solid fa-folder-open"></i>
                Manage Materials
            </a>
        </div>
        <div style="padding: 16px 24px;">
            @forelse($lesson->materials as $material)
                <div class="module-mini-card">
                    <div class="module-mini-icon"><i class="fa-solid fa-paperclip"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $material->pivot->title ?? $material->filename }}</div>
                        <div class="module-mini-meta">
                            {{ $material->file_type ?? 'File' }}
                            @if($material->pivot->is_required ?? false)
                                <span style="margin:0 8px;">·</span><span class="badge-published">Required</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="tab-empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    No materials attached yet.
                    <a href="{{ route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson]) }}">Add materials</a>
                </div>
            @endforelse
        </div>
    </div>
@endsection

@extends('layouts.instructor')

@section('title', 'Discussions — ' . $course->code)
@php
    $activeNav = 'courses';
    $pageTitle = 'Discussions';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Discussions — {{ $course->code }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.show', $course) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="{{ route('instructor.courses.discussions.create', $course) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Discussion
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-comments"></i> Discussions ({{ $discussions->total() }})</h3>
        </div>
        <div style="padding: 16px 24px;">
            @forelse($discussions as $discussion)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(34,211,238,.13);color:#67e8f9"><i class="fa-solid fa-comments"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $discussion->title }}
                            @if($discussion->is_pinned ?? false)
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @endif
                        </div>
                        <div class="module-mini-meta">
                            {{ $discussion->class?->code ?? 'Course-wide' }}
                            @if($discussion->posts_count ?? null)
                                <span style="margin:0 8px;">·</span>{{ $discussion->posts_count }} posts
                            @endif
                            <span style="margin:0 8px;">·</span>{{ $discussion->created_at?->diffForHumans() }}
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.discussions.show', [$course, $discussion]) }}" class="btn-icon btn-view" title="View">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @empty
                <div class="tab-empty-state">
                    <i class="fa-solid fa-comments"></i>
                    No discussions yet. <a href="{{ route('instructor.courses.discussions.create', $course) }}">Start one</a>
                </div>
            @endforelse
        </div>
        @if($discussions->hasPages())
            <div style="padding:14px 24px;">{{ $discussions->links() }}</div>
        @endif
    </div>
@endsection

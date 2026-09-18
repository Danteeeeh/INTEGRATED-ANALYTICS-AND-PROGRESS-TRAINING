@extends('layouts.instructor')
@section('title', $discussion->title)
@php $activeNav = 'discussions'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $discussion->title }}"
        subtitle="Discussion thread for {{ $course->title }}"
        icon="fa-comments"
    >
        <x-slot name="meta">
            @if($discussion->is_pinned)<span class="user-status active">Pinned</span>@endif
            @if($discussion->is_locked)<span class="user-status">Locked</span>@endif
            <span class="user-status">{{ $discussion->posts->count() }} posts</span>
        </x-slot>
        <x-slot name="actions">
            <form method="POST" action="{{ route('instructor.courses.discussions.pin', [$course, $discussion]) }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-thumbtack"></i> {{ $discussion->is_pinned ? 'Unpin' : 'Pin' }}</button>
            </form>
            <form method="POST" action="{{ route('instructor.courses.discussions.lock', [$course, $discussion]) }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary"><i class="fa-solid {{ $discussion->is_locked ? 'fa-lock-open' : 'fa-lock' }}"></i> {{ $discussion->is_locked ? 'Unlock' : 'Lock' }}</button>
            </form>
            <a href="{{ route('instructor.courses.discussions.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment-dots"></i> Discussion</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $discussion->creator?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Context</label>
                    <div>{{ $discussion->class?->code ?? $discussion->module?->title ?? $discussion->lesson?->title ?? $course->code }}</div>
                </div>
                @if($discussion->body)
                    <div class="form-field full">
                        <label>Body</label>
                        <div style="white-space:pre-line">{{ $discussion->body }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list"></i> Posts</h3>
            <span class="user-status">{{ $discussion->posts->count() }} posts</span>
        </div>
        <div class="user-panel-body">
            @if($discussion->posts->count() > 0)
                @foreach($discussion->posts as $post)
                    <div class="user-toolbar" style="flex-direction:column;align-items:stretch;margin-bottom:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                            <strong>{{ $post->creator?->name ?? '—' }}</strong>
                            <span class="user-email">{{ $post->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        <div style="white-space:pre-line">{{ $post->body ?? $post->content ?? '—' }}</div>
                    </div>
                @endforeach
            @else
                <x-user-empty-state
                    icon="fa-comments"
                    title="No posts yet"
                    description="Students can post in this discussion once it is active."
                />
            @endif
        </div>
    </div>
</div>
@endsection

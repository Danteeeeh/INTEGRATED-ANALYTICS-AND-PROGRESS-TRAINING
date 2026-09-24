@extends('layouts.student')
@section('title', $discussion->title)
@php $activeNav = 'discussions'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $discussion->title }}"
        subtitle="{{ $course->title }} — discussion"
        icon="fa-comments"
    >
        <x-slot name="meta">
            @if($discussion->is_pinned)<span class="user-status active">Pinned</span>@endif
            @if($discussion->is_locked)<span class="user-status">Locked</span>@endif
            <span class="user-status">{{ $discussion->posts->count() }} posts</span>
        </x-slot>
        <x-slot name="actions">
            <form method="POST" action="{{ route('student.courses.discussions.subscribe', [$course, $discussion]) }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <i class="fa-solid {{ $isSubscribed ? 'fa-bell-slash' : 'fa-bell' }}"></i> {{ $isSubscribed ? 'Unsubscribe' : 'Subscribe' }}
                </button>
            </form>
            <a href="{{ route('student.courses.discussions.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-comment-dots"></i> Discussion</h3></div>
        <div class="user-panel-body">
            @if($discussion->body)
                <div style="white-space:pre-line">{{ $discussion->body }}</div>
            @else
                <p class="user-email">No description provided.</p>
            @endif
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
                            <strong>{{ $post->author?->name ?? $post->creator?->name ?? '—' }}</strong>
                            <span class="user-email">{{ $post->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        <div style="white-space:pre-line">{{ $post->body ?? $post->content ?? '—' }}</div>

                        @foreach(($post->children ?? collect()) as $child)
                            <div style="margin-left:18px;margin-top:8px;padding-left:10px;border-left:2px solid var(--dash-line)">
                                <strong>{{ $child->author?->name ?? '—' }}</strong>
                                <div class="user-email">{{ $child->created_at?->format('M j, Y g:i A') }}</div>
                                <div style="white-space:pre-line">{{ $child->body ?? $child->content ?? '—' }}</div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @else
                <x-user-empty-state icon="fa-comments" title="No posts yet" description="Be the first to post in this discussion." />
            @endif

            @if(!$discussion->is_locked)
                <form action="{{ route('student.courses.discussions.posts.store', [$course, $discussion]) }}" method="POST" style="margin-top:16px">
                    @csrf
                    <div class="form-field full">
                        <label for="postBody">Add a reply</label>
                        <textarea id="postBody" name="body" rows="4" required placeholder="Write your reply...">{{ old('body') }}</textarea>
                        <span class="field-error">{{ $errors->first('body') }}</span>
                    </div>
                    <div class="form-actions user-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Post Reply</button>
                    </div>
                </form>
            @else
                <div class="user-toolbar">
                    <span class="user-status">This discussion is locked</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

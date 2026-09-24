@extends('layouts.student')
@section('title', 'Discussions')
@php $activeNav = 'discussions'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Discussions"
        subtitle="{{ $course->title }} — course discussions"
        icon="fa-comments"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($discussions->count() > 0)
        <div class="learning-grid">
            @foreach($discussions as $discussion)
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            @if($discussion->is_pinned)<i class="fa-solid fa-thumbtack"></i> Pinned · @endif
                            {{ $discussion->posts_count ?? 0 }} posts
                        </div>
                        <h3>{{ $discussion->title }}</h3>
                        <p>{{ Str::limit($discussion->body ?? '', 90) }}</p>
                    </div>
                    <div class="user-actions" style="justify-content:space-between">
                        <span class="user-email">{{ $discussion->creator?->name ?? '' }}</span>
                        <a href="{{ route('student.courses.discussions.show', [$course, $discussion]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-comments"></i> Join</a>
                    </div>
                </div>
            @endforeach
        </div>

        @if($discussions->hasPages())
            <div class="pagination">{{ $discussions->links() }}</div>
        @endif
    @else
        <x-user-empty-state icon="fa-comments" title="No discussions" description="No discussions have been started for this course yet." />
    @endif
</div>
@endsection

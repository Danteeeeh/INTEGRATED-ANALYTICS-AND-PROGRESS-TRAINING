@extends('layouts.student')
@section('title', 'Announcements')
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Announcements"
        subtitle="{{ $course->title }} — announcements for your class"
        icon="fa-bullhorn"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($announcements->count() > 0)
        @foreach($announcements as $announcement)
            <div class="learning-card" style="min-height:auto;justify-content:flex-start;gap:10px">
                <div class="user-kicker">
                    @if($announcement->is_pinned)<i class="fa-solid fa-thumbtack"></i> Pinned · @endif
                    {{ $announcement->created_at?->format('M j, Y') }}
                </div>
                <h3>{{ $announcement->title }}</h3>
                <p>{{ Str::limit($announcement->body ?? '', 140) }}</p>
                <div class="user-actions" style="justify-content:flex-start">
                    <a href="{{ route('student.courses.announcements.show', [$course, $announcement]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Read</a>
                </div>
            </div>
        @endforeach

        @if($announcements->hasPages())
            <div class="pagination">{{ $announcements->links() }}</div>
        @endif
    @else
        <x-user-empty-state
            icon="fa-bullhorn"
            title="No announcements"
            description="No announcements have been posted for your class yet."
        />
    @endif
</div>
@endsection

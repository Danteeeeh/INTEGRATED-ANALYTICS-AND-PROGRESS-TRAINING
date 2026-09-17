@extends('layouts.student')

@section('title', 'My Courses')
@php
    $activeNav = 'courses';
    $pageTitle = 'My Courses';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
@endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Courses"
        subtitle="Your enrolled courses and learning progress."
        icon="fa-book"
    >
        <x-slot name="actions">
            <a href="{{ route('student.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-school"></i> Browse Classes</a>
        </x-slot>
    </x-user-page-header>

    @if($enrollments->count() > 0)
        <div class="learning-grid">
            @foreach($enrollments as $enrollment)
                @php $course = $enrollment->class?->course; @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            @if($course)
                                <i class="fa-solid fa-book"></i> {{ $course->code }}
                            @else
                                <i class="fa-solid fa-book"></i> Enrolled
                            @endif
                        </div>
                        <h3>{{ $course->title ?? $enrollment->class?->name ?? 'Course' }}</h3>
                        <p>{{ $enrollment->class?->instructor?->full_name ?? '—' }} · {{ $enrollment->class?->code ?? '' }}</p>
                    </div>
                    <div>
                        <div class="learning-progress">
                            <span style="width: {{ $enrollment->class?->progress ?? 0 }}%"></span>
                        </div>
                        <div class="user-actions" style="justify-content:space-between;margin-top:10px">
                            <x-user-status-badge status="{{ $enrollment->status }}" />
                            @if($course)
                                <a href="{{ route('student.courses.show', $course) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-right"></i> Continue</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($enrollments->hasPages())
            <div class="pagination">{{ $enrollments->links() }}</div>
        @endif
    @else
        <x-user-empty-state
            icon="fa-book"
            title="No courses enrolled yet"
            description="Browse available classes and enroll to start learning."
        >
            <x-slot name="action">
                <a href="{{ route('student.classes.index') }}" class="btn btn-primary"><i class="fa-solid fa-school"></i> Browse Classes</a>
            </x-slot>
        </x-user-empty-state>
    @endif
</div>
@endsection
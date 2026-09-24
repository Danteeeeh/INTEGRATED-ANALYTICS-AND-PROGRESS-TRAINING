@extends('layouts.student')
@section('title', 'Quizzes')
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Quizzes"
        subtitle="{{ $course->title }} — available quizzes"
        icon="fa-question-circle"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($quizzes->count() > 0)
        <div class="learning-grid">
            @foreach($quizzes as $quiz)
                @php
                    $best = $quiz->attempts->first();
                    $available = $quiz->available();
                    $attempted = $best !== null;
                @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            @if($attempted)
                                <span style="color:var(--user-success)"><i class="fa-solid fa-circle-check"></i> Attempted</span>
                            @elseif($available)
                                Available now
                            @else
                                Not available
                            @endif
                        </div>
                        <h3>{{ $quiz->title }}</h3>
                        <p>{{ Str::limit($quiz->description ?? '', 80) }}</p>
                    </div>
                    <div>
                        <div class="user-actions" style="justify-content:space-between">
                            <span class="user-status">
                                @if($quiz->time_limit_minutes){{ $quiz->time_limit_minutes }} min · @endif
                                @if($attempted)Best {{ number_format($best->score_percent ?? $best->score ?? 0, 1) }}%@else{{ $quiz->questions->count() }} questions@endif
                            </span>
                            <a href="{{ route('student.courses.quizzes.show', [$course, $quiz]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Open</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($quizzes->hasPages())
            <div class="pagination">{{ $quizzes->links() }}</div>
        @endif
    @else
        <x-user-empty-state icon="fa-question-circle" title="No quizzes" description="No quizzes have been assigned to this course yet." />
    @endif
</div>
@endsection

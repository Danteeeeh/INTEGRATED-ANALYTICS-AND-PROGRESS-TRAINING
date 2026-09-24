@extends('layouts.student')
@section('title', 'Attempt Results')
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Attempt Results"
        subtitle="{{ $quiz->title }} — attempt #{{ $attempt->attempt_number }}"
        icon="fa-clipboard-check"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $attempt->status }}" />
            <span class="user-status">{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.quizzes.show', [$course, $quiz]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Result Summary</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Score</label>
                    <div><strong>{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</strong></div>
                </div>
                <div class="form-field">
                    <label>Completed</label>
                    <div>{{ $attempt->completed_at?->format('M j, Y g:i A') ?? $attempt->created_at?->format('M j, Y g:i A') }}</div>
                </div>
                @if($quiz->passing_score_percent)
                    <div class="form-field">
                        <label>Passing Score</label>
                        <div>
                            {{ $quiz->passing_score_percent }}%
                            @if(($attempt->score_percent ?? 0) >= $quiz->passing_score_percent)
                                <span class="user-status active">Passed</span>
                            @else
                                <span class="user-status">Not passed</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-list-check"></i> Review Answers</h3></div>
        <div class="user-panel-body">
            @if($attempt->answers && $attempt->answers->count() > 0)
                @foreach($attempt->answers as $index => $answer)
                    <div class="user-toolbar" style="flex-direction:column;align-items:stretch;margin-bottom:12px">
                        <strong>Q{{ $index + 1 }}: {{ $answer->question?->question_text ?? $answer->question?->text ?? 'Question' }}</strong>
                        <div class="user-email" style="margin-top:6px">
                            Your answer:
                            @if($answer->choice)
                                {{ $answer->choice->choice_text ?? $answer->choice->text ?? 'Choice' }}
                            @elseif($answer->answer_text)
                                {{ $answer->answer_text }}
                            @else
                                —
                            @endif
                            @if($answer->is_correct === true)
                                <span class="user-status active"><i class="fa-solid fa-check"></i> Correct</span>
                            @elseif($answer->is_correct === false)
                                <span class="user-status"><i class="fa-solid fa-xmark"></i> Incorrect</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <x-user-empty-state icon="fa-list-check" title="No answers recorded" description="No answers were recorded for this attempt." />
            @endif
        </div>
    </div>

    <div class="user-actions">
        @if($quiz->available())
            <a href="{{ route('student.courses.quizzes.attempt.start', [$course, $quiz]) }}" class="btn btn-primary"><i class="fa-solid fa-rotate-right"></i> Try Again</a>
        @endif
        <a href="{{ route('student.courses.quizzes.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> All Quizzes</a>
    </div>
</div>
@endsection

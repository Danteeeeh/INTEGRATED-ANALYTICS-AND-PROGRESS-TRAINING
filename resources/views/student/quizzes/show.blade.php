@extends('layouts.student')
@section('title', $quiz->title)
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $quiz->title }}"
        subtitle="{{ $course->title }} — quiz"
        icon="fa-question-circle"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $quiz->status }}" />
            @if($quiz->time_limit_minutes)<span class="user-status">{{ $quiz->time_limit_minutes }} min</span>@endif
            <span class="user-status">{{ $quiz->questions->count() }} questions</span>
        </x-slot>
        <x-slot name="actions">
            @if($inProgressAttempt)
                <a href="{{ route('student.courses.quizzes.attempt.start', [$course, $quiz]) }}" class="btn btn-primary"><i class="fa-solid fa-play"></i> Resume Attempt</a>
            @elseif($quiz->available())
                <a href="{{ route('student.courses.quizzes.attempt.start', [$course, $quiz]) }}" class="btn btn-primary"><i class="fa-solid fa-play"></i> Start Attempt</a>
            @endif
            <a href="{{ route('student.courses.quizzes.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Quiz Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Time Limit</label>
                    <div>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'No limit' }}</div>
                </div>
                <div class="form-field">
                    <label>Questions</label>
                    <div>{{ $quiz->questions->count() }}</div>
                </div>
                <div class="form-field">
                    <label>Attempts Allowed</label>
                    <div>{{ $quiz->attempt_limit ?? 'Unlimited' }}</div>
                </div>
                <div class="form-field">
                    <label>Passing Score</label>
                    <div>{{ $quiz->passing_score_percent ? $quiz->passing_score_percent.'%' : '—' }}</div>
                </div>
                @if($quiz->instructions)
                    <div class="form-field full">
                        <label>Instructions</label>
                        <div style="white-space:pre-line">{{ $quiz->instructions }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> My Attempts</h3>
            <span class="user-status">{{ $myAttempts->total() }} attempts</span>
        </div>
        <div class="user-panel-body">
            @if($myAttempts->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Attempt</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myAttempts as $attempt)
                                <tr>
                                    <td>#{{ $attempt->attempt_number }}</td>
                                    <td>{{ $attempt->created_at?->format('M j, Y g:i A') }}</td>
                                    <td>{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</td>
                                    <td><x-user-status-badge status="{{ $attempt->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('student.courses.quizzes.attempts.show', [$course, $quiz, $attempt]) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-clock-rotate-left" title="No attempts yet" description="Take this quiz to see your results here." />
            @endif

            @if($myAttempts->hasPages())
                <div class="pagination">{{ $myAttempts->links() }}</div>
            @endif
        </div>
    </div>

    @if($quiz->available() && !$inProgressAttempt)
        <div class="learning-next-action">
            <div>
                <strong>Ready to take this quiz?</strong>
                <span>You have {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'unlimited time' }} once you start.</span>
            </div>
            <a href="{{ route('student.courses.quizzes.attempt.start', [$course, $quiz]) }}" class="btn btn-primary"><i class="fa-solid fa-play"></i> Start Attempt</a>
        </div>
    @endif
</div>
@endsection

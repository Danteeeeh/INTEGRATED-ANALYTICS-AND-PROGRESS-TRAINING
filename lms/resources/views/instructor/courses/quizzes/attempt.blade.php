@extends('layouts.instructor')
@section('title', 'Attempt Review')
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Quiz Attempt Review"
        subtitle="{{ $quiz->title }} — {{ $attempt->student?->full_name ?? $attempt->student?->name ?? 'Student' }}"
        icon="fa-question-circle"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $attempt->status }}" />
            <span class="user-status">{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.quizzes.attempts', [$course, $quiz]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-user"></i> Attempt Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div>{{ $attempt->student?->full_name ?? $attempt->student?->name ?? '—' }}</div>
                    <div class="user-email">{{ $attempt->student?->email }}</div>
                </div>
                <div class="form-field">
                    <label>Started</label>
                    <div>{{ $attempt->started_at?->format('M j, Y g:i A') ?? $attempt->created_at?->format('M j, Y g:i A') }}</div>
                </div>
                <div class="form-field">
                    <label>Score</label>
                    <div>{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $attempt->status }}" /></div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-list-check"></i> Answers</h3></div>
        <div class="user-panel-body">
            @if($attempt->answers && $attempt->answers->count() > 0)
                @foreach($attempt->answers as $index => $answer)
                    <div class="user-toolbar" style="flex-direction:column;align-items:stretch;margin-bottom:12px">
                        <strong>Q{{ $index + 1 }}: {{ $answer->question?->question_text ?? $answer->question?->text ?? 'Question' }}</strong>
                        <div class="user-email">
                            Answer:
                            @if($answer->choice)
                                {{ $answer->choice->choice_text ?? $answer->choice->text ?? 'Choice' }}
                            @elseif($answer->answer_text)
                                {{ $answer->answer_text }}
                            @else
                                —
                            @endif
                            @if($answer->is_correct)
                                <span class="user-status active"><i class="fa-solid fa-check"></i> Correct</span>
                            @elseif($answer->is_correct === false)
                                <span class="user-status"><i class="fa-solid fa-xmark"></i> Incorrect</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <x-user-empty-state icon="fa-list-check" title="No answers recorded" description="This attempt has no recorded answers." />
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-graduation-cap"></i> Manual Grade</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.quizzes.attempts.grade', [$course, $quiz, $attempt]) }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Score <span class="required">*</span></label>
                        <input type="number" name="score" min="0" step="0.1" required value="{{ old('score', $attempt->score) }}">
                        <span class="field-error">{{ $errors->first('score') }}</span>
                    </div>
                    <div class="form-field full">
                        <label>Feedback</label>
                        <textarea name="feedback" rows="3" placeholder="Optional feedback">{{ old('feedback') }}</textarea>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

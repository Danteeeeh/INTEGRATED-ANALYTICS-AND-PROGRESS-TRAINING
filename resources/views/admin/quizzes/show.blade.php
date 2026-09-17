@extends('layouts.admin')

@section('title', $quiz->title)
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $quiz->title }}"
        subtitle="Quiz metadata, questions, and attempt summaries."
        icon="fa-question-circle"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $quiz->status }}" />
            <span class="user-status">{{ $quiz->questions->count() }} questions</span>
            <span class="user-status">{{ $quiz->attempts->count() }} attempts</span>
        </x-slot>
        <x-slot name="actions">
            @if($quiz->status === 'published')
                <form method="POST" action="{{ route('admin.quizzes.close', $quiz) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-lock"></i> Close</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.quizzes.publish', $quiz) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                </form>
            @endif
            <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Quiz Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $quiz->class?->code ?? '—' }} — {{ $quiz->class?->course?->title ?? '' }}</div>
                </div>
                <div class="form-field">
                    <label>Time Limit</label>
                    <div>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Attempt Limit</label>
                    <div>{{ $quiz->attempt_limit ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Passing Score</label>
                    <div>{{ $quiz->passing_score_percent ? $quiz->passing_score_percent.'%' : '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Available From</label>
                    <div>{{ $quiz->availability_from?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Available Until</label>
                    <div>{{ $quiz->availability_until?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                @if($quiz->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $quiz->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-ol"></i> Questions</h3>
            <span class="user-status">{{ $quiz->questions->count() }} questions</span>
        </div>
        <div class="user-panel-body">
            @if($quiz->questions->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Choices</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quiz->questions as $index => $question)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ Str::limit($question->question_text ?? $question->text ?? 'Question', 70) }}</td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $question->question_type ?? 'choice')) }}</span></td>
                                    <td>{{ $question->choices->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-list-ol"
                    title="No questions yet"
                    description="Add questions to this quiz before publishing."
                />
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-user-clock"></i> Attempts</h3>
            <span class="user-status">{{ $quiz->attempts->count() }} attempts</span>
        </div>
        <div class="user-panel-body">
            @if($quiz->attempts->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Score</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quiz->attempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->student?->name ?? '—' }}</td>
                                    <td>{{ round($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</td>
                                    <td>{{ $attempt->created_at?->format('M j, Y g:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-user-clock"
                    title="No attempts yet"
                    description="Student attempts will appear here once the quiz is taken."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.quizzes.destroy', $quiz) }}" method="POST" onsubmit="return confirm('Delete this quiz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

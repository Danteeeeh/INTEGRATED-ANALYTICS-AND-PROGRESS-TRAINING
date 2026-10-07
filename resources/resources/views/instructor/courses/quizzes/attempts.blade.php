@extends('layouts.instructor')
@section('title', 'Attempts — '.$quiz->title)
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Quiz Attempts"
        subtitle="{{ $quiz->title }} — {{ $course->title }}"
        icon="fa-question-circle"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $attempts->total() }} attempts</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.quizzes.show', [$course, $quiz]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-users"></i> Attempts</h3></div>
        <div class="user-panel-body">
            @if($attempts->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->student?->full_name ?? $attempt->student?->name ?? '—' }}</td>
                                    <td>{{ $attempt->created_at?->format('M j, Y g:i A') }}</td>
                                    <td>{{ number_format($attempt->score_percent ?? $attempt->score ?? 0, 1) }}%</td>
                                    <td><x-user-status-badge status="{{ $attempt->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('instructor.courses.quizzes.attempts.show', [$course, $quiz, $attempt]) }}" class="btn btn-icon" title="Review"><i class="fa-solid fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-users" title="No attempts yet" description="Student attempts will appear here once the quiz is taken." />
            @endif

            @if($attempts->hasPages())
                <div class="pagination">{{ $attempts->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

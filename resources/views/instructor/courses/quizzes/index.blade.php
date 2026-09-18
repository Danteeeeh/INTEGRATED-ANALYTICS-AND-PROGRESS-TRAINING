@extends('layouts.instructor')

@section('title', 'Quizzes — ' . $course->code)
@php
    $activeNav = 'courses';
    $pageTitle = 'Quizzes';
    $pageIcon = '<i class="fa-solid fa-circle-question"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-circle-question"></i>
            Quizzes — {{ $course->code }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.show', $course) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="{{ route('instructor.courses.quizzes.create', $course) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Quiz
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-circle-question"></i> Quizzes ({{ $quizzes->total() }})</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Class</th>
                    <th>Time Limit</th>
                    <th>Passing</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quizzes as $quiz)
                    <tr>
                        <td>{{ $quiz->title }}</td>
                        <td>{{ $quiz->class?->code ?? '—' }}</td>
                        <td>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes . ' min' : '—' }}</td>
                        <td>{{ $quiz->passing_score_percent ? $quiz->passing_score_percent . '%' : '—' }}</td>
                        <td>
                            @if($quiz->status === 'published')
                                <span class="badge-published">Published</span>
                            @elseif($quiz->status === 'closed')
                                <span class="badge-draft">Closed</span>
                            @else
                                <span class="badge-draft">Draft</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('instructor.courses.quizzes.show', [$course, $quiz]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('instructor.courses.quizzes.edit', [$course, $quiz]) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="{{ route('instructor.courses.quizzes.attempts', [$course, $quiz]) }}" class="btn-icon" title="Attempts" style="color:#62c9f5;">
                                <i class="fa-solid fa-list-check"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No quizzes yet. <a href="{{ route('instructor.courses.quizzes.create', $course) }}" style="color:#2563eb;text-decoration:underline;">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($quizzes->hasPages())
            <div style="padding:14px 24px;">{{ $quizzes->links() }}</div>
        @endif
    </div>
@endsection

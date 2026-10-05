@extends('layouts.student')

@section('title', 'Incomplete Items')
@php
    $activeNav = 'incomplete';
    $pageTitle = 'Incomplete Items';
    $pageIcon = '<i class="fa-solid fa-clock"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-clock"></i>
            Incomplete Items
        </h2>
    </div>
@endsection

@section('content')
<div class="learning-shell">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="Incomplete Assignments & Quizzes"
        subtitle="Track and complete your pending tasks across all courses."
        icon="fa-clock"
        kicker="Task management"
    >
        <x-slot name="meta">
            <span>{{ ($incompleteAssignments ?? collect())->count() + ($incompleteQuizzes ?? collect())->count() + ($incompleteExams ?? collect())->count() }} pending items</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.dashboard') }}" class="btn btn-secondary"><i class="fa-solid fa-home"></i> Dashboard</a>
            <a href="{{ route('student.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-book-open"></i> My Courses</a>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ OVERDUE WARNING ═══ --}}
    @if(($overdueAssignments ?? collect())->isNotEmpty())
        <div class="overdue-strip" style="margin-bottom: 28px;">
            <div class="overdue-head">
                <span class="overdue-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <h4>{{ ($overdueAssignments ?? collect())->count() }} overdue assignment{{ ($overdueAssignments ?? collect())->count() > 1 ? 's' : '' }}</h4>
                    <p>Submit these immediately to avoid grade penalties.</p>
                </div>
            </div>
            <ul class="overdue-list">
                @foreach(($overdueAssignments ?? []) as $assignment)
                    <li>
                        <a href="{{ $assignment->class_id ? route('student.courses.assignments.show', [$assignment->class->course, $assignment]) : '#' }}">
                            <span class="od-name">{{ $assignment->title }}</span>
                            <span class="od-meta">{{ $assignment->class?->course?->title ?? '' }} · was due {{ $assignment->due_date?->diffForHumans() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="dash-grid">
        {{-- ═══ INCOMPLETE ASSIGNMENTS ═══ --}}
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-file-pen" style="color: #d97706;"></i> Incomplete Assignments</h4>
                <span class="panel-count">{{ ($incompleteAssignments ?? collect())->count() }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($incompleteAssignments ?? []) as $assignment)
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="{{ $assignment->class_id ? route('student.courses.assignments.show', [$assignment->class->course, $assignment]) : '#' }}">
                            <span class="dash-list-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fa-solid fa-file-pen"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">{{ $assignment->title }}</p>
                                <p class="dash-list-sub">{{ $assignment->class?->course?->title ?? '' }}</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-amber">Due {{ $assignment->due_date?->format('M j') }}</span>
                                <span class="dash-list-date">{{ $assignment->due_date?->diffForHumans() }}</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-check-circle"></i> All assignments completed</li>
                @endforelse
            </ul>
        </section>

        {{-- ═══ INCOMPLETE QUIZZES ═══ --}}
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-circle-question" style="color: #7c3aed;"></i> Incomplete Quizzes</h4>
                <span class="panel-count">{{ ($incompleteQuizzes ?? collect())->count() }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($incompleteQuizzes ?? []) as $quiz)
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="{{ $quiz->class_id ? route('student.courses.quizzes.show', [$quiz->class->course, $quiz]) : '#' }}">
                            <span class="dash-list-icon i-violet"><i class="fa-solid fa-circle-question"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">{{ $quiz->title }}</p>
                                <p class="dash-list-sub">Quiz · {{ $quiz->class?->course?->title ?? '' }}</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-blue">Until {{ $quiz->availability_until?->format('M j') ?? 'No deadline' }}</span>
                                <span class="dash-list-date">Available now</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-check-circle"></i> All quizzes completed</li>
                @endforelse
            </ul>
        </section>

        {{-- ═══ INCOMPLETE EXAMS ═══ --}}
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-graduation-cap" style="color: #db2777;"></i> Incomplete Exams</h4>
                <span class="panel-count">{{ ($incompleteExams ?? collect())->count() }}</span>
            </div>
            <ul class="dash-list">
                @forelse(($incompleteExams ?? []) as $exam)
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="{{ $exam->class_id ? route('student.courses.exams.show', [$exam->class->course, $exam]) : '#' }}">
                            <span class="dash-list-icon i-rose"><i class="fa-solid fa-graduation-cap"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title">{{ $exam->title }}</p>
                                <p class="dash-list-sub">Exam · {{ $exam->class?->course?->title ?? '' }}</p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-rose">Until {{ $exam->ends_at?->format('M j') ?? 'No deadline' }}</span>
                                <span class="dash-list-date">Available now</span>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="dash-list-empty"><i class="fa-solid fa-check-circle"></i> All exams completed</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- ═══ EMPTY STATE ═══ --}}
    @if(($incompleteAssignments ?? collect())->isEmpty() && ($incompleteQuizzes ?? collect())->isEmpty() && ($incompleteExams ?? collect())->isEmpty())
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 20px; border: 1px solid #e2e8f0; margin-top: 28px;">
            <i class="fa-solid fa-trophy" style="font-size: 4rem; color: #10b981; margin-bottom: 20px;"></i>
            <h3 style="color: #1e293b; font-size: 1.5rem; margin-bottom: 12px;">All caught up!</h3>
            <p style="color: #64748b; font-size: 1rem; margin-bottom: 24px;">You have completed all your assignments, quizzes, and exams.</p>
            <a href="{{ route('student.courses.index') }}" class="btn btn-primary">
                <i class="fa-solid fa-book-open" style="margin-right: 8px;"></i> Browse Courses
            </a>
        </div>
    @endif
</div>
@endsection

@extends('layouts.student')

@section('title', 'My Progress')
@php
    $activeNav = 'progress';
    $pageTitle = 'My Progress';
    $pageIcon = '<i class="fa-solid fa-chart-line"></i>';
@endphp

@section('content')
<div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="My Learning Progress"
        subtitle="Track your academic journey, achievements, and learning milestones."
        icon="fa-chart-line"
        kicker="Student progress"
    >
        <x-slot name="meta">
            <span><i class="fa-solid fa-fire"></i> {{ $learningStreak }} day streak</span>
            <span>·</span>
            <span>{{ $overallProgress }}% overall progress</span>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ OVERVIEW STATS ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-gauge"></i> Overview</h3>
            <span class="dash-section-kicker">Your learning at a glance</span>
        </div>
    </div>
    <div class="user-stat-grid">
        <x-user-stat-card
            label="Active Courses"
            value="{{ $totalCourses }}"
            icon="fa-book"
            trend="{{ round($overallProgress) }}% complete"
            footer="{{ $completedEnrollments->count() }} completed"
        />
        <x-user-stat-card
            label="Assignment Progress"
            value="{{ round($assignmentProgress) }}%"
            icon="fa-tasks"
            trend="{{ $submittedAssignments }}/{{ $totalAssignments }} submitted"
            footer="{{ $totalAssignments - $submittedAssignments }} pending"
        />
        <x-user-stat-card
            label="Quiz Progress"
            value="{{ round($quizProgress) }}%"
            icon="fa-question-circle"
            trend="{{ $quizAttempts }} attempts"
            footer="{{ $totalQuizzes - $quizAttempts }} remaining"
        />
        <x-user-stat-card
            label="Average Grade"
            value="{{ number_format($averageGrade, 1) }}%"
            icon="fa-graduation-cap"
            trend="{{ $recentGrades->count() }} graded items"
            footer="{{ $completions->count() }} courses completed"
        />
    </div>

    {{-- ═══ COURSE PROGRESS ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-book-open"></i> Course Progress</h3>
            <span class="dash-section-kicker">Detailed progress per enrolled course</span>
        </div>
    </div>
    <div class="card-grid">
        @forelse($courseProgressData as $progress)
            <div class="card">
                <div class="card-header">
                    <h4>{{ $progress['course']->title }}</h4>
                    <span class="badge">{{ $progress['class']->code }}</span>
                </div>
                <div class="card-body">
                    <div class="progress-overview">
                        <div class="progress-bar-container">
                            <div class="progress-bar" style="width: {{ $progress['progress'] }}%">
                                <span>{{ round($progress['progress']) }}%</span>
                            </div>
                        </div>
                        <p class="progress-meta">
                            Last accessed: {{ $progress['last_accessed'] ? $progress['last_accessed']->diffForHumans() : 'Never' }}
                        </p>
                    </div>
                    <div class="progress-details">
                        <div class="progress-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <span>Modules: {{ $progress['modules_completed'] }}/{{ $progress['total_modules'] }}</span>
                        </div>
                        <div class="progress-item">
                            <i class="fa-solid fa-book-open-reader"></i>
                            <span>Lessons: {{ $progress['lessons_completed'] }}/{{ $progress['total_lessons'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('student.courses.show', $progress['course']) }}" class="btn btn-primary btn-sm">
                        Continue Learning
                    </a>
                </div>
            </div>
        @empty
            <div class="card card--empty">
                <div class="card-body">
                    <i class="fa-solid fa-book-open"></i>
                    <p>No active courses enrolled</p>
                    <a href="{{ route('student.courses.index') }}" class="btn btn-secondary">Browse Courses</a>
                </div>
            </div>
        @endforelse
    </div>

    {{-- ═══ WEEKLY ACTIVITY ═══ --}}
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-calendar-week"></i> Weekly Activity</h3>
            <span class="dash-section-kicker">Your learning activity over the past 7 days</span>
        </div>
    </div>
    <section class="student-activity-panel" aria-label="Weekly learning activity">
        <div class="activity-chart">
            @foreach($weeklyActivity['data'] as $day)
                <div class="activity-day">
                    <div class="activity-label">{{ $day['date'] }}</div>
                    <div class="activity-bars">
                        <div class="activity-bar activity-bar--lessons" style="height: {{ $day['lessons_completed'] > 0 ? min(($day['lessons_completed'] / $weeklyActivity['max']['lessons']) * 100, 100) : 0 }}%" title="{{ $day['lessons_completed'] }} lessons">
                            <span>{{ $day['lessons_completed'] }}</span>
                        </div>
                        <div class="activity-bar activity-bar--quizzes" style="height: {{ $day['quizzes_taken'] > 0 ? min(($day['quizzes_taken'] / $weeklyActivity['max']['quizzes']) * 100, 100) : 0 }}%" title="{{ $day['quizzes_taken'] }} quizzes">
                            <span>{{ $day['quizzes_taken'] }}</span>
                        </div>
                        <div class="activity-bar activity-bar--assignments" style="height: {{ $day['assignments_submitted'] > 0 ? min(($day['assignments_submitted'] / $weeklyActivity['max']['assignments']) * 100, 100) : 0 }}%" title="{{ $day['assignments_submitted'] }} assignments">
                            <span>{{ $day['assignments_submitted'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="activity-legend">
            <span class="legend-item"><span class="legend-color lessons"></span> Lessons Completed</span>
            <span class="legend-item"><span class="legend-color quizzes"></span> Quizzes Taken</span>
            <span class="legend-item"><span class="legend-color assignments"></span> Assignments Submitted</span>
        </div>
    </section>

    {{-- ═══ COMPLETION TIMELINE ═══ --}}
    @if($completionTimeline)
        <div class="dash-section">
            <div>
                <h3><i class="fa-solid fa-trophy"></i> Completion Timeline</h3>
                <span class="dash-section-kicker">Courses you've completed</span>
            </div>
        </div>
        <section class="student-timeline-panel">
            <div class="student-timeline">
                @foreach($completionTimeline as $completion)
                    <article class="student-timeline-item">
                        <span class="student-timeline-dot" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                        <div>
                            <span class="student-timeline-date">{{ $completion['completed_at'] }}</span>
                            <h4>{{ $completion['course'] }}</h4>
                        </div>
                        <span class="user-status completed">{{ $completion['completion_percent'] }}% complete</span>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

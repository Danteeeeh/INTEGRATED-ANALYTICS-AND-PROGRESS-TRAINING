@extends('layouts.student')

@php
    $feature = request('feature');
    $featureMeta = [
        'modules' => ['label' => 'Modules', 'icon' => 'fa-layer-group', 'hint' => 'Review course modules', 'route' => 'student.courses.modules.index', 'color' => 'cyan'],
        'lessons' => ['label' => 'Lessons', 'icon' => 'fa-book-open-reader', 'hint' => 'Read course lessons', 'route' => 'student.courses.lessons.index', 'color' => 'emerald'],
        'assignments' => ['label' => 'Assignments', 'icon' => 'fa-tasks', 'hint' => 'Review and submit course tasks', 'route' => 'student.courses.assignments.index', 'color' => 'amber'],
        'quizzes' => ['label' => 'Quizzes', 'icon' => 'fa-question-circle', 'hint' => 'Take quizzes and review attempts', 'route' => 'student.courses.quizzes.index', 'color' => 'violet'],
        'exams' => ['label' => 'Exams', 'icon' => 'fa-file-signature', 'hint' => 'Take exams and review results', 'route' => 'student.courses.exams.index', 'color' => 'rose'],
        'announcements' => ['label' => 'Announcements', 'icon' => 'fa-bullhorn', 'hint' => 'Read course announcements', 'route' => 'student.courses.announcements.index', 'color' => 'rose'],
    ];
    $activeNav = ($feature && isset($featureMeta[$feature])) ? $feature : 'courses';
    $pageTitle = ($feature && isset($featureMeta[$feature])) ? $featureMeta[$feature]['label'] : 'My Courses';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
@endphp
@section('title', $pageTitle)

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $pageTitle }}"
        subtitle="{{ ($feature && isset($featureMeta[$feature])) ? $featureMeta[$feature]['hint'].'. Piliin ang course para makapagpatuloy.' : 'Your enrolled courses and learning progress.' }}"
        icon="{{ $feature && isset($featureMeta[$feature]) ? $featureMeta[$feature]['icon'] : 'fa-book' }}"
    >
        <x-slot name="actions">
            <a href="{{ route('student.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-school"></i> My Classes</a>
        </x-slot>
    </x-user-page-header>

    @if($feature && isset($featureMeta[$feature]))
        <section class="feature-picker" style="display:flex;align-items:center;gap:14px;margin-bottom:16px;padding:16px 18px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:14px;background:linear-gradient(135deg,rgba(36,73,198,.22),rgba(10,16,32,.72));">
            <div class="feature-picker-icon" style="display:grid;place-items:center;flex:none;border-radius:11px;width:42px;height:42px;font-size:16px;background:rgba(98,201,245,.14);color:#67e8f9"><i class="fa-solid {{ $featureMeta[$feature]['icon'] }}"></i></div>
            <div style="flex:1;min-width:0">
                <span style="display:block;color:var(--bcp-cyan-400,#62c9f5);font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase">Course feature</span>
                <h3 style="margin:3px 0;color:var(--bcp-ink,#eef4ff);font-size:.95rem">Choose a course for {{ $featureMeta[$feature]['label'] }}</h3>
                <p style="margin:0;color:var(--bcp-muted,#98a7c4);font-size:.75rem">{{ $featureMeta[$feature]['hint'] }}.</p>
            </div>
            <a href="{{ route('student.courses.index') }}" style="color:var(--bcp-muted,#98a7c4);font-size:.72rem;text-decoration:none;white-space:nowrap"><i class="fa-solid fa-xmark"></i> Clear</a>
        </section>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:18px">
            @forelse($enrollments as $enrollment)
                @php $course = $enrollment->class?->course; @endphp
                @if($course)
                    <a href="{{ route($featureMeta[$feature]['route'], $course) }}" style="display:flex;align-items:center;gap:11px;padding:14px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:12px;background:var(--bcp-card,var(--dash-surface,#151c2c));text-decoration:none;transition:transform .15s,border-color .15s">
                        <span style="display:grid;place-items:center;flex:none;border-radius:11px;width:42px;height:42px;font-size:16px;background:rgba(98,201,245,.14);color:#67e8f9"><i class="fa-solid {{ $featureMeta[$feature]['icon'] }}"></i></span>
                        <span style="display:flex;flex-direction:column;gap:3px;min-width:0;flex:1"><strong style="color:var(--bcp-ink,#eef4ff);font-size:.82rem">{{ $course->code }}</strong><small style="color:var(--bcp-muted,#98a7c4);font-size:.72rem">{{ $course->title }}</small></span>
                        <i class="fa-solid fa-arrow-right" style="color:var(--bcp-cyan-400,#62c9f5);font-size:.75rem"></i>
                    </a>
                @endif
            @empty
                <div style="grid-column:1/-1;text-align:center;color:var(--bcp-muted,#98a7c4);padding:24px">Wala ka pang enrolled na course para sa feature na ito.</div>
            @endforelse
        </div>
    @else
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
                description="You are not enrolled in any courses yet. Contact your administrator for enrollment."
            />
        @endif
    @endif
</div>
@endsection

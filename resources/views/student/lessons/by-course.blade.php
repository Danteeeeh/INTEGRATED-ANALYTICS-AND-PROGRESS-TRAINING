@extends('layouts.student')

@section('title', $course->title.' · Lessons')
@php $activeNav = 'lessons'; @endphp

{{--
    Lessons for a single course, grouped by module.

    Reached from the "Choose a course for Lessons" picker. Grouping by module
    is deliberate: a lesson belongs to a module, so this is where a student who
    was working inside a module can find their way back to it.
--}}
@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Lessons"
        subtitle="{{ $course->title }} — {{ $course->code }}"
        icon="fa-book-open-reader"
    >
        <x-slot name="meta">
            <span class="user-status">
                {{ $groups->sum(fn ($g) => $g['lessons']->count()) }} lessons
                · {{ $groups->count() }} modules
            </span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to Course
            </a>
        </x-slot>
    </x-user-page-header>

    @forelse ($groups as $group)
        @php $module = $group['module']; @endphp
        <div class="user-panel" style="margin-bottom:16px">
            <div class="user-panel-head">
                <h3>
                    <i class="fa-solid fa-layer-group"></i>
                    {{ $module->title }}
                    <span class="user-status">{{ $group['lessons']->count() }} lessons</span>
                </h3>
                <a href="{{ route('student.courses.modules.show', [$course, $module]) }}"
                   class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-cubes"></i> Open module
                </a>
            </div>
            <div class="user-panel-body">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
                    @foreach ($group['lessons'] as $lesson)
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker">
                                    Lesson {{ $lesson->position ?? $loop->iteration }}
                                </div>
                                <h3>{{ $lesson->title }}</h3>
                                <p>{{ Str::limit($lesson->description ?? $lesson->content ?? 'No summary yet.', 90) }}</p>
                            </div>
                            <div>
                                <small class="user-email">{{ $module->title }}</small>
                                <div class="user-actions" style="justify-content:space-between;margin-top:10px">
                                    <a href="{{ route('student.courses.modules.lessons.show', [$course, $module, $lesson]) }}"
                                       class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-book-open"></i> Read
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @empty
        <x-user-empty-state
            icon="fa-book-open-reader"
            title="No lessons yet"
            description="There are no published lessons in this course yet."
        />
    @endforelse
</div>
@endsection
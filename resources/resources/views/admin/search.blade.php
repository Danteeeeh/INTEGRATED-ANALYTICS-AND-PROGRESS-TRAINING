@extends('layouts.admin')

@section('title', 'Search')
@php
    $activeNav = '';
    $resultTotal = ($courses?->count() ?? 0) + ($classes?->count() ?? 0) + ($lessons?->count() ?? 0) + ($materials?->count() ?? 0) + ($modules?->count() ?? 0) + ($students?->count() ?? 0);
@endphp

@section('content')
    <section class="search-page-hero">
        <div>
            <span class="dash-hero-kicker"><i class="fa-solid fa-magnifying-glass"></i> Command search</span>
            <h1>{{ $search ? 'Search results' : 'Search the LMS' }}</h1>
            <p>{{ $search ? 'Showing matches for “' . $search . '”.' : 'Find courses, modules, classes, lessons, materials, and students from one place.' }}</p>
        </div>
        <span class="search-result-count">{{ $resultTotal }} {{ Str::plural('result', $resultTotal) }}</span>
    </section>

    <form class="search-page-form" action="{{ route('admin.search') }}" method="GET" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="search" value="{{ $search }}" placeholder="Search courses, modules, classes, lessons, or students..." aria-label="Search LMS" autofocus>
        <button type="submit"><i class="fa-solid fa-arrow-right"></i> Search</button>
    </form>

    @if(!$search)
        <section class="search-empty-state">
            <i class="fa-solid fa-compass"></i>
            <h2>Start searching</h2>
            <p>Type a keyword above to search the academic content available to you.</p>
        </section>
    @else
        <div class="search-results-grid">
            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-book"></i> Courses</h2><span>{{ $courses->count() }}</span></header>
                @forelse($courses as $course)
                    <a class="search-result-item" href="{{ route('admin.courses.show', $course) }}">
                        <span class="search-result-icon blue"><i class="fa-solid fa-book-open"></i></span>
                        <span><strong>{{ $course->title }}</strong><small>{{ $course->code }} · {{ $course->status }}</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No courses matched.</p>
                @endforelse
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-layer-group"></i> Modules</h2><span>{{ $modules->count() }}</span></header>
                @forelse($modules as $module)
                    <a class="search-result-item" href="{{ route('admin.courses.modules.show', [$module->course, $module]) }}">
                        <span class="search-result-icon indigo"><i class="fa-solid fa-layer-group"></i></span>
                        <span><strong>{{ $module->title }}</strong><small>{{ $module->course?->title ?? 'Course module' }}</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No modules matched.</p>
                @endforelse
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-school"></i> Classes</h2><span>{{ $classes->count() }}</span></header>
                @forelse($classes as $class)
                    <a class="search-result-item" href="{{ route('admin.classes.show', $class) }}">
                        <span class="search-result-icon cyan"><i class="fa-solid fa-school"></i></span>
                        <span><strong>{{ $class->code }}</strong><small>{{ $class->course?->title ?? 'Class' }} · {{ $class->schedule ?? 'No schedule' }}</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No classes matched.</p>
                @endforelse
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-book-open-reader"></i> Lessons</h2><span>{{ $lessons->count() }}</span></header>
                @forelse($lessons as $lesson)
                    <a class="search-result-item" href="{{ route('admin.courses.show', $lesson->module?->course) }}">
                        <span class="search-result-icon violet"><i class="fa-solid fa-file-lines"></i></span>
                        <span><strong>{{ $lesson->title }}</strong><small>{{ $lesson->module?->course?->title ?? 'Course lesson' }}</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No lessons matched.</p>
                @endforelse
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-paperclip"></i> Materials</h2><span>{{ $materials->count() }}</span></header>
                @forelse($materials as $material)
                    <a class="search-result-item" href="{{ route('admin.courses.show', $material->lesson?->module?->course) }}">
                        <span class="search-result-icon amber"><i class="fa-solid fa-paperclip"></i></span>
                        <span><strong>{{ $material->title }}</strong><small>{{ $material->lesson?->title ?? 'Lesson material' }}</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No materials matched.</p>
                @endforelse
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-user-graduate"></i> Students</h2><span>{{ $students->count() }}</span></header>
                @forelse($students as $student)
                    <a class="search-result-item" href="{{ route('admin.students.show', $student) }}">
                        <span class="search-result-icon green"><i class="fa-solid fa-user-graduate"></i></span>
                        <span><strong>{{ $student->name }}</strong><small>{{ $student->email }}@if($student->identifier) · {{ $student->identifier }} @endif</small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                @empty
                    <p class="search-no-results">No students matched.</p>
                @endforelse
            </section>
        </div>
    @endif
@endsection

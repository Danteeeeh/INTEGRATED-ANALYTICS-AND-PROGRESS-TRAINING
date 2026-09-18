@extends('layouts.student')
@section('title', 'Course Modules')
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Course Modules"
        subtitle="{{ $course->title }} — learning modules"
        icon="fa-cubes"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($modules->count() > 0)
        <div class="learning-grid">
            @foreach($modules as $module)
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">Module {{ $module->position ?? $loop->iteration }}</div>
                        <h3>{{ $module->title }}</h3>
                        <p>{{ Str::limit($module->description ?? '', 90) }}</p>
                    </div>
                    <div>
                        <div class="user-actions" style="justify-content:space-between">
                            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
                            <a href="{{ route('student.courses.modules.show', [$course, $module]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-book-open"></i> Open</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($modules->hasPages())
            <div class="pagination">{{ $modules->links() }}</div>
        @endif
    @else
        <x-user-empty-state icon="fa-cubes" title="No modules yet" description="No published modules for this course yet." />
    @endif
</div>
@endsection

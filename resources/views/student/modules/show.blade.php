@extends('layouts.student')
@section('title', $module->title)
@php $activeNav = 'modules'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $module->title }}"
        subtitle="{{ $course->title }} — module lessons"
        icon="fa-cubes"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $module->lessons->count() }} lessons</span>
            @if($module->is_required)<span class="user-status active">Required</span>@endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.modules.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($module->description)
        <div class="user-panel">
            <div class="user-panel-body">
                <p style="margin:0;color:var(--dash-muted)">{{ $module->description }}</p>
            </div>
        </div>
    @endif

    @if($module->lessons->count() > 0)
        <div class="learning-grid">
            @foreach($module->lessons as $lesson)
                @php
                    $p = $lesson->progress->first();
                    $completed = $p && $p->status === 'completed';
                @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            Lesson {{ $lesson->position ?? $loop->iteration }}
                            @if($completed)<span style="color:var(--user-success)"> · Completed</span>@endif
                        </div>
                        <h3>{{ $lesson->title }}</h3>
                        <p>{{ Str::limit($lesson->description ?? '', 80) }}</p>
                    </div>
                    <div>
                        @if($p)
                            <div class="learning-progress"><span style="width: {{ min(100, round($p->progress_percent ?? 0)) }}%"></span></div>
                        @endif
                        <div class="user-actions" style="justify-content:flex-end;margin-top:10px">
                            <a href="{{ route('student.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid {{ $completed ? 'fa-circle-check' : 'fa-play' }}"></i> {{ $completed ? 'Review' : 'Start' }}
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-user-empty-state icon="fa-cubes" title="No lessons" description="No published lessons in this module yet." />
    @endif
</div>
@endsection

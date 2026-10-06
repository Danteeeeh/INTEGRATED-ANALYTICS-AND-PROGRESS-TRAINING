@extends('layouts.student')

@section('title', 'Lessons')
@php $activeNav = 'lessons'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Lessons"
        subtitle="All published lessons from your enrolled classes."
        icon="fa-book-open-reader"
    />

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book-open-reader"></i> Published Lessons</h3>
            <span class="user-status">{{ $lessons->total() }} lessons</span>
        </div>
        <div class="user-panel-body">
            @if($lessons->isNotEmpty())
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
                    @foreach($lessons as $lesson)
                        @php
                            $module = $lesson->module;
                            $course = $module?->course;
                        @endphp
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker">
                                    @if($course)
                                        <i class="fa-solid fa-book"></i> {{ $course->code }}
                                    @endif
                                </div>
                                <h3>{{ $lesson->title }}</h3>
                                <p>{{ Str::limit($lesson->summary ?? $lesson->content ?? 'No summary yet.', 90) }}</p>
                            </div>
                            <div>
                                <small class="user-email">{{ $module?->title }}</small>
                                <div class="user-actions" style="justify-content:space-between;margin-top:10px">
                                    @if($course)
                                        <a href="{{ route('student.courses.modules.show', [$course, $module]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-layer-group"></i> Module</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{ $lessons->links() }}
            @else
                <x-user-empty-state icon="fa-book-open-reader" title="No lessons yet" description="You don't have any published lessons in your enrolled classes yet." />
            @endif
        </div>
    </div>
</div>
@endsection
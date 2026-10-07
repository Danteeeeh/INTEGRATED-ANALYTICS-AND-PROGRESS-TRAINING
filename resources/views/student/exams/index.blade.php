@extends('layouts.student')

@section('title', 'Exams')

@php
    $activeNav = 'exams';
    $pageTitle = 'Exams';
    $pageIcon = '<i class="fa-solid fa-file-alt"></i>';
@endphp

@section('content')
    <div class="user-page">
        <x-user-page-header
            title="Exams"
            subtitle="{{ $course->title }}"
            icon="fa-file-signature"
        >
            <x-slot name="actions">
                <a href="{{ route('student.courses.index') }}" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Courses
                </a>
            </x-slot>
        </x-user-page-header>

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        @if(session('status'))
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
            </div>
        @endif

        @forelse($exams as $exam)
            @php
                // An exam with nothing on it is still being built. Saying so
                // plainly stops a student opening it and finding a blank page.
                $isReady = ($exam->questions_count ?? 0) > 0;
                $attempts = $exam->attempts ?? collect();
                $hasStarted = $attempts->isNotEmpty();
            @endphp

            <div class="user-panel" style="margin-bottom:16px;">
                <div class="user-panel-head">
                    <h3><i class="fa-solid fa-file-alt"></i> {{ $exam->title }}</h3>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <x-user-status-badge :status="$exam->status" />
                        <span class="user-status {{ $isReady ? 'active' : '' }}">
                            {{ $exam->questions_count ?? 0 }} question{{ ($exam->questions_count ?? 0) === 1 ? '' : 's' }}
                        </span>
                    </div>
                </div>

                <div class="user-panel-body">
                    @if($exam->description)
                        <p style="margin:0 0 12px;font-size:.86rem;color:var(--dash-muted);">
                            {{ $exam->description }}
                        </p>
                    @endif

                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;font-size:.8rem;color:var(--dash-muted);">
                        <span class="user-status"><i class="fa-solid fa-clock"></i> {{ $exam->duration_minutes }} min</span>

                        @if($exam->passing_score_percent)
                            <span class="user-status"><i class="fa-solid fa-check"></i> {{ $exam->passing_score_percent }}% to pass</span>
                        @endif

                        @if(($exam->points_total ?? 0) > 0)
                            <span class="user-status"><i class="fa-solid fa-star"></i> {{ number_format((float) $exam->points_total, 0) }} points</span>
                        @endif

                        <span class="user-status"><i class="fa-solid fa-graduation-cap"></i> {{ $exam->getTypeLabel() }}</span>

                        @if($exam->attempt_limit)
                            <span class="user-status"><i class="fa-solid fa-redo"></i> {{ $attempts->count() }}/{{ $exam->attempt_limit }} used</span>
                        @endif
                    </div>

                    {{-- Availability, said in words rather than left to the student
                         to work out from a pair of dates. --}}
                    <p style="margin:0 0 14px;font-size:.8rem;color:var(--dash-muted);">
                        @if($exam->starts_at && $exam->ends_at)
                            <i class="fa-solid fa-calendar"></i>
                            {{ $exam->starts_at->format('M d, Y g:i A') }}
                            &rarr;
                            {{ $exam->ends_at->format('M d, Y g:i A') }}
                        @elseif($exam->ends_at)
                            <i class="fa-solid fa-calendar"></i> Closes {{ $exam->ends_at->format('M d, Y g:i A') }}
                        @elseif($exam->starts_at)
                            <i class="fa-solid fa-calendar"></i> Opens {{ $exam->starts_at->format('M d, Y g:i A') }}
                        @else
                            <i class="fa-solid fa-infinity"></i> No closing date
                        @endif
                    </p>

                    @unless($isReady)
                        <div class="empty-state" style="margin-bottom:12px">
                            <i class="fa-solid fa-hourglass-half"></i>
                            This exam has no questions yet, so it cannot be started.
                            Your instructor is still preparing it.
                        </div>
                    @endunless

                    <div class="user-actions" style="justify-content:space-between;flex-wrap:wrap;gap:10px;">
                        <div>
                            @if($hasStarted)
                                <span class="user-status">
                                    Last attempt {{ $attempts->first()->created_at?->diffForHumans() }}
                                </span>
                            @else
                                <span class="user-status">Not attempted yet</span>
                            @endif
                        </div>

                        <a href="{{ route('student.courses.exams.show', [$course, $exam]) }}"
                           class="btn btn-secondary">
                            <i class="fa-solid fa-eye"></i> View details
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <x-user-empty-state
                icon="fa-file-alt"
                title="No exams yet"
                description="There are no exams scheduled for this course yet."
            />
        @endforelse

        @if($exams->hasPages())
            <div style="margin-top:8px;">{{ $exams->links() }}</div>
        @endif
    </div>
@endsection
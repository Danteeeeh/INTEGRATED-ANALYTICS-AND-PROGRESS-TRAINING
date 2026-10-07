@extends('layouts.student')

@section('title', 'Classes')
@php $activeNav = 'classes'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Classes"
        subtitle="View your enrolled classes and progress."
        icon="fa-school"
    />

    @if($enrolledClasses->count() > 0)
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-user-graduate"></i> My Enrolled Classes</h3>
                <span class="user-status active">{{ $enrolledClasses->count() }} enrolled</span>
            </div>
            <div class="user-panel-body">
                <div class="learning-grid">
                    @foreach($enrolledClasses as $class)
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker">{{ $class->code }} · {{ $class->academicPeriod?->name ?? '' }}</div>
                                <h3>{{ $class->course?->title ?? $class->name }}</h3>
                                <p>{{ $class->instructor?->full_name ?? '—' }} · {{ $class->schedule ?? '' }}</p>
                            </div>
                            <div class="user-actions" style="justify-content:space-between;gap:8px;flex-wrap:wrap">
                                <x-user-status-badge status="active" label="Enrolled" />

                                <div class="user-actions" style="gap:8px">
                                    {{-- This page is what "My Grades" opens, so each card
                                         needs a way into its gradebook. Without it the
                                         only path was Open class, then find Gradebook
                                         among the resource cards — which read as a dead
                                         link. --}}
                                    <a href="{{ route('student.classes.gradebook.index', $class) }}"
                                       class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-chart-bar"></i> View Grades
                                    </a>
                                    <a href="{{ route('student.classes.show', $class) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Open</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <x-user-empty-state
            icon="fa-school"
            title="No enrolled classes"
            description="You are not enrolled in any classes yet. Contact your administrator for enrollment."
        />
    @endif
</div>
@endsection

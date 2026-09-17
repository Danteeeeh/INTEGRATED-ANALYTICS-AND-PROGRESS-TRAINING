@extends('layouts.student')

@section('title', 'Classes')
@php $activeNav = 'classes'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Classes"
        subtitle="Browse available classes and manage your enrollments."
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
                            <div class="user-actions" style="justify-content:space-between">
                                <x-user-status-badge status="active" label="Enrolled" />
                                <a href="{{ route('student.classes.show', $class) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> Open</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($availableClasses->count() > 0)
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-book-open"></i> Available Classes</h3>
                <span class="user-status">{{ $availableClasses->count() }} open</span>
            </div>
            <div class="user-panel-body">
                <div class="learning-grid">
                    @foreach($availableClasses as $class)
                        <div class="learning-card">
                            <div>
                                <div class="user-kicker">{{ $class->code }} · {{ $class->academicPeriod?->name ?? '' }}</div>
                                <h3>{{ $class->course?->title ?? $class->name }}</h3>
                                <p>{{ $class->instructor?->full_name ?? '—' }} · {{ $class->schedule ?? '' }}</p>
                            </div>
                            <div class="user-actions" style="justify-content:space-between">
                                <span class="user-status active">Seats open</span>
                                <a href="{{ route('student.classes.show', $class) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if($enrolledClasses->count() === 0 && $availableClasses->count() === 0)
        <x-user-empty-state
            icon="fa-school"
            title="No classes available"
            description="There are no open classes to enroll in right now."
        />
    @endif
</div>
@endsection

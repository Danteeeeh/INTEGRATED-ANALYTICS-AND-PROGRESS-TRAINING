@extends('layouts.student')

@section('title', 'My Enrollments')
@php $activeNav = 'enrollments'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="My Enrollments"
        subtitle="Your enrollment history and status."
        icon="fa-user-plus"
    />

    @if($enrollments->count() > 0)
        <div class="learning-grid">
            @foreach($enrollments as $enrollment)
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            {{ $enrollment->class?->code ?? 'Class' }} · {{ $enrollment->class?->academicPeriod?->name ?? '' }}
                        </div>
                        <h3>{{ $enrollment->class?->course?->title ?? 'Course' }}</h3>
                        <p>{{ $enrollment->class?->instructor?->full_name ?? '—' }}</p>
                    </div>
                    <div>
                        @if($enrollment->final_grade !== null)
                            <div class="learning-progress"><span style="width: {{ min(100, (float) $enrollment->final_grade) }}%"></span></div>
                        @endif
                        <div class="user-actions" style="justify-content:space-between;margin-top:10px">
                            <x-user-status-badge status="{{ $enrollment->status }}" />
                            <a href="{{ route('student.enrollments.show', $enrollment) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> Details</a>
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
            icon="fa-user-plus"
            title="No enrollments yet"
            description="You are not enrolled in any classes yet. Contact your administrator for enrollment."
        />
    @endif
</div>
@endsection

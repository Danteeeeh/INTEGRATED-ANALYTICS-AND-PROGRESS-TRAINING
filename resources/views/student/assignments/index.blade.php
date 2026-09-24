@extends('layouts.student')
@section('title', 'Assignments')
@php $activeNav = 'assignments'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="Assignments"
        subtitle="{{ $course->title }} — assignments and due dates"
        icon="fa-tasks"
    >
        <x-slot name="actions">
            <a href="{{ route('student.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($assignments->count() > 0)
        <div class="learning-grid">
            @foreach($assignments as $assignment)
                @php
                    $sub = $assignment->submissions->first();
                    $status = $sub ? $sub->status : ($assignment->due_date && $assignment->due_date->isPast() ? 'overdue' : 'open');
                @endphp
                <div class="learning-card">
                    <div>
                        <div class="user-kicker">
                            @if($assignment->due_date)
                                @if($assignment->due_date->isPast())
                                    <span style="color:var(--user-danger)">Due {{ $assignment->due_date->diffForHumans() }}</span>
                                @else
                                    Due {{ $assignment->due_date->format('M j, g:i A') }}
                                @endif
                            @else
                                No due date
                            @endif
                        </div>
                        <h3>{{ $assignment->title }}</h3>
                        <p>{{ Str::limit($assignment->description ?? '', 90) }}</p>
                    </div>
                    <div>
                        <div class="user-actions" style="justify-content:space-between">
                            <x-user-status-badge status="{{ $status }}" />
                            <a href="{{ route('student.courses.assignments.show', [$course, $assignment]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($assignments->hasPages())
            <div class="pagination">{{ $assignments->links() }}</div>
        @endif
    @else
        <x-user-empty-state
            icon="fa-tasks"
            title="No assignments"
            description="No assignments have been posted for this course yet."
        />
    @endif
</div>
@endsection

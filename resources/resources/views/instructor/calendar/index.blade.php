@extends('layouts.instructor')
@section('title', 'Calendar — ' . $class->code)
@php($activeNav = 'calendar')

@section('content')
<div class="user-page">
    <x-user-page-header
        :title="'Calendar — ' . $class->code"
        subtitle="Upcoming class events and schedules."
        icon="fa-calendar"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.calendar.create', $class) }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Add Event
            </a>
            <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        @if($events->total() > 0)
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Visibility</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>{{ $event->title }}</td>
                                <td>{{ ucfirst($event->event_type ?? 'event') }}</td>
                                <td>{{ $event->start_at?->format('M d, Y g:i A') }}</td>
                                <td>{{ $event->end_at?->format('g:i A') ?? '—' }}</td>
                                <td>{{ ucfirst($event->visibility ?? 'class') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-user-empty-state
                icon="fa-calendar"
                title="No calendar events yet"
                description="Add a class event to start building the schedule."
            />
        @endif

        @if($events->hasPages())
            <div class="pagination">{{ $events->links() }}</div>
        @endif
    </div>
</div>
@endsection
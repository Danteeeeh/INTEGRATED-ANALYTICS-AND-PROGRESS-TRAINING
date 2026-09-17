@extends('layouts.admin')

@section('title', 'Calendar')
@php $activeNav = 'calendar'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Calendar"
        subtitle="Manage institutional events, exams, and announcements."
        icon="fa-calendar"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.calendar.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Event</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.calendar.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search events..." aria-label="Search events">
            <select class="form-control" name="event_type" aria-label="Filter event type">
                <option value="">All Types</option>
                @foreach(['assignment' => 'Assignment', 'quiz' => 'Quiz', 'virtual_class' => 'Virtual Class', 'exam' => 'Exam', 'announcement' => 'Announcement', 'course' => 'Course', 'personal' => 'Personal'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('event_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-control" name="visibility" aria-label="Filter visibility">
                <option value="">All Visibility</option>
                @foreach(['private' => 'Private', 'course' => 'Course', 'class' => 'Class', 'public' => 'Public'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('visibility') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.calendar.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($calendarEvents->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Type</th>
                                <th>When</th>
                                <th>Context</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($calendarEvents as $event)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $event->title }}</div>
                                        <div class="user-email">{{ Str::limit($event->description ?? '', 50) }}</div>
                                    </td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $event->event_type)) }}</span></td>
                                    <td>
                                        <div>{{ $event->start_at?->format('M j, Y g:i A') }}</div>
                                        <div class="user-email">{{ $event->is_all_day ? 'All day' : '→ '.($event->end_at?->format('g:i A')) }}</div>
                                    </td>
                                    <td>
                                        @if($event->course)
                                            {{ $event->course->code }}
                                        @elseif($event->class)
                                            {{ $event->class->code }}
                                        @elseif($event->user)
                                            {{ $event->user->name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><x-user-status-badge status="{{ $event->visibility }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.calendar.show', $event) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.calendar.edit', $event) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-calendar"
                    title="No events found"
                    description="Create a calendar event to schedule exams, classes, or announcements."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.calendar.create') }}"><i class="fa-solid fa-plus"></i> New Event</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($calendarEvents->hasPages())
                <div class="pagination">{{ $calendarEvents->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.student')

@section('title', 'My Calendar')
@php
    $activeNav = 'calendar';
    $pageTitle = 'My Calendar';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
@endphp

@section('content')
<div class="user-page">
    {{-- ═══ HERO ═══ --}}
    <x-user-page-header
        title="My Calendar"
        subtitle="View your upcoming assignments, quizzes, virtual classes, and announcements."
        icon="fa-calendar"
        kicker="Schedule"
    >
        <x-slot name="meta">
            <span>{{ $events->count() }} upcoming events</span>
        </x-slot>
    </x-user-page-header>

    {{-- ═══ EVENTS BY DATE ═══ --}}
    @if($eventsByDate->count() > 0)
        @foreach($eventsByDate as $date => $dayEvents)
            <div class="dash-section">
                <div>
                    <h3><i class="fa-solid fa-calendar-day"></i> {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</h3>
                    <span class="dash-section-kicker">{{ $dayEvents->count() }} event(s) scheduled</span>
                </div>
            </div>
            <div class="card-grid">
                @foreach($dayEvents as $event)
                    <div class="card card--event card--event-{{ $event['type'] }}">
                        <div class="card-header">
                            <div class="event-icon">
                                @switch($event['type'])
                                    @case('assignment')
                                        <i class="fa-solid fa-tasks"></i>
                                    @break
                                    @case('quiz')
                                        <i class="fa-solid fa-question-circle"></i>
                                    @break
                                    @case('virtual_class')
                                        <i class="fa-solid fa-video"></i>
                                    @break
                                    @case('announcement')
                                        <i class="fa-solid fa-bullhorn"></i>
                                    @break
                                @endswitch
                            </div>
                            <h4>{{ $event['title'] }}</h4>
                            <span class="badge badge--{{ $event['type'] }}">
                                {{ ucfirst(str_replace('_', ' ', $event['type'])) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="event-details">
                                <div class="detail-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>{{ $event['time'] }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fa-solid fa-book"></i>
                                    <span>{{ $event['course'] }}</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fa-solid fa-school"></i>
                                    <span>{{ $event['class'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <a href="{{ $event['url'] }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @else
        <div class="card card--empty">
            <div class="card-body">
                <i class="fa-solid fa-calendar"></i>
                <h3>No upcoming events</h3>
                <p>You don't have any assignments, quizzes, virtual classes, or announcements scheduled.</p>
                <a href="{{ route('student.courses.index') }}" class="btn btn-primary">
                    <i class="fa-solid fa-book-open"></i> Browse Courses
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

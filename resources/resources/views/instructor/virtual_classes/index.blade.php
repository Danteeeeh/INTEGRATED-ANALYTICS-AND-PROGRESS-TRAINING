@extends('layouts.instructor')

@section('title', 'Virtual Classes - ' . $class->name)
@php($activeNav = 'virtual_classes')

@section('content')
<div class="user-page">
    <x-user-page-header
        :title="'Virtual Classes — ' . $class->code"
        :subtitle="$class->name . ' — scheduled online meetings for this class.'"
        icon="fa-video"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.virtual_classes.create', $class) }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> New Virtual Class
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        @if($virtualClasses->count() > 0)
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date/Time</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Attendees</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($virtualClasses as $vc)
                            <tr>
                                <td>{{ $vc->title }}</td>
                                <td>
                                    {{ $vc->meeting_date->format('M d, Y') }}
                                    <div class="table-sub">{{ \Carbon\Carbon::parse($vc->start_time)->format('g:i A') }} &ndash; {{ \Carbon\Carbon::parse($vc->end_time)->format('g:i A') }}</div>
                                </td>
                                <td>
                                    @switch($vc->meeting_provider)
                                        @case('zoom')
                                            <i class="fa-solid fa-video" style="color:#2D8CFF;"></i> Zoom
                                            @break
                                        @case('google_meet')
                                            <i class="fa-solid fa-video" style="color:#EA4335;"></i> Google Meet
                                            @break
                                        @case('microsoft_teams')
                                            <i class="fa-solid fa-video" style="color:#6264A7;"></i> MS Teams
                                            @break
                                        @default
                                            <i class="fa-solid fa-video"></i> Other
                                    @endswitch
                                </td>
                                <td>
                                    <x-user-status-badge :status="$vc->status" />
                                </td>
                                <td>
                                    {{ $vc->attendees->count() }}
                                    @if($class->enrollments && $class->enrollments->count() > 0)
                                        / {{ $class->enrollments->count() }}
                                    @endif
                                </td>
                                <td class="actions-cell">
                                    @if($vc->status === 'scheduled')
                                        <form method="POST" action="{{ route('instructor.classes.virtual_classes.start', [$class, $vc]) }}" style="display:inline;" onsubmit="return confirm('Start this virtual class?');">
                                            @csrf
                                            <button type="submit" class="btn-icon btn-edit" title="Start">
                                                <i class="fa-solid fa-play"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('instructor.classes.virtual_classes.show', [$class, $vc]) }}" class="btn-icon btn-view" title="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-user-empty-state
                icon="fa-video"
                title="No virtual classes scheduled"
                description="Schedule an online meeting for this class to get started."
            />
        @endif

        @if($virtualClasses->hasPages())
            <div class="pagination">{{ $virtualClasses->links() }}</div>
        @endif
    </div>

    <div style="margin-top: 16px;">
        <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Class
        </a>
    </div>
</div>
@endsection
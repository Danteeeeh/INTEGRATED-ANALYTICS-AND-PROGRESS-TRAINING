@extends('layouts.admin')

@section('title', 'Virtual Class Details')
@php($activeNav = 'virtual_classes')

@section('content')
<div class="user-page">
    <x-user-page-header
        :title="$virtualClass->title"
        subtitle="Virtual class details, schedule, and attendance."
        icon="fa-video"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.virtual_classes.edit', $virtualClass) }}" class="btn btn-secondary">
                <i class="fa-solid fa-pen-to-square"></i> Edit Class
            </a>
            @if($virtualClass->meeting_url)
                <a href="{{ $virtualClass->meeting_url }}" target="_blank" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Meeting
                </a>
            @endif
            <form method="POST" action="{{ route('admin.virtual_classes.destroy', $virtualClass) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this virtual class?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-trash"></i> Delete
                </button>
            </form>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
            <div class="modal-row"><span>Title:</span><span>{{ $virtualClass->title }}</span></div>
            <div class="modal-row"><span>Description:</span><span>{{ $virtualClass->description ?? 'No description provided' }}</span></div>

            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-link"></i> Associations</div>
            <div class="modal-row"><span>Course:</span><span>{{ $virtualClass->course ? ($virtualClass->course->code . ' - ' . $virtualClass->course->title) : 'Not assigned' }}</span></div>
            <div class="modal-row"><span>Class:</span><span>{{ $virtualClass->class ? ($virtualClass->class->code . ' - ' . ($virtualClass->class->name ?? '')) : 'Not assigned' }}</span></div>
            <div class="modal-row"><span>Instructor:</span><span>{{ $virtualClass->instructor->name ?? 'Not assigned' }} ({{ $virtualClass->instructor->email ?? 'N/A' }})</span></div>
            <div class="modal-row"><span>Created By:</span><span>{{ $virtualClass->creator->name ?? 'Unknown' }}</span></div>

            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-calendar-days"></i> Schedule</div>
            <div class="modal-row"><span>Date:</span><span>{{ $virtualClass->meeting_date->format('l, F j, Y') }}</span></div>
            <div class="modal-row"><span>Start Time:</span><span>{{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }}</span></div>
            <div class="modal-row"><span>End Time:</span><span>{{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}</span></div>
            <div class="modal-row"><span>Duration:</span><span>{{ $virtualClass->durationLabel() }}</span></div>

            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-video"></i> Meeting Details</div>
            @php
                $providerLabel = match($virtualClass->meeting_provider) {
                    'zoom' => 'Zoom',
                    'google_meet' => 'Google Meet',
                    'microsoft_teams' => 'Microsoft Teams',
                    'other' => 'Other',
                    default => ucfirst((string) $virtualClass->meeting_provider),
                };
            @endphp
            <div class="modal-row"><span>Provider:</span><span><x-user-status-badge :status="Str::slug($virtualClass->meeting_provider ?? 'other')" :label="$providerLabel" /></span></div>
            <div class="modal-row"><span>Meeting URL:</span><span>
                @if($virtualClass->meeting_url)
                    <a href="{{ $virtualClass->meeting_url }}" target="_blank" style="color: var(--bcp-cyan-400); text-decoration: none;">{{ $virtualClass->meeting_url }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i></a>
                @else
                    Not provided
                @endif
            </span></div>
            <div class="modal-row"><span>Meeting ID:</span><span>{{ $virtualClass->meeting_id ?? 'Not provided' }}</span></div>
            <div class="modal-row"><span>Password:</span><span>{{ $virtualClass->meeting_password ? '••••••' . $virtualClass->meeting_password : 'Not set' }}</span></div>

            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-flag"></i> Status</div>
            <div class="modal-row"><span>Status:</span><span><x-user-status-badge :status="$virtualClass->status" /></span></div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h4><i class="fa-solid fa-users"></i> Attendees ({{ $virtualClass->attendees->count() }})</h4>
        </div>
        @if($virtualClass->attendees->count() > 0)
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Joined At</th>
                            <th>Left At</th>
                            <th>Duration</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($virtualClass->attendees as $attendee)
                            <tr>
                                <td style="font-weight:500;">{{ $attendee->student->name ?? 'Unknown' }}</td>
                                <td>{{ $attendee->student->email ?? '-' }}</td>
                                <td>{{ $attendee->joined_at ? $attendee->joined_at->format('M d, Y g:i A') : '-' }}</td>
                                <td>{{ $attendee->left_at ? $attendee->left_at->format('M d, Y g:i A') : '-' }}</td>
                                <td>{{ $attendee->duration_minutes ? $attendee->duration_minutes . ' min' : '-' }}</td>
                                <td>
                                    <x-user-status-badge :status="$attendee->attendance_status ?? 'registered'" :label="ucfirst($attendee->attendance_status ?? 'registered')" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-user-empty-state icon="fa-users" title="No attendees yet" description="Attendance will appear here once students join." />
        @endif
    </div>

    <div style="margin-top:16px;">
        <a href="{{ route('admin.virtual_classes.index') }}" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to Virtual Classes
        </a>
    </div>
</div>
@endsection
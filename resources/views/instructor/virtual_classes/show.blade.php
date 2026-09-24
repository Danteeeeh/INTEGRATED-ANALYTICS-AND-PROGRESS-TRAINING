@extends('layouts.instructor')

@section('title', $virtualClass->title . ' - ' . $class->name)
@php
    $activeNav = 'classes';
    $pageTitle = 'Virtual Class Details';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            {{ $virtualClass->title }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Virtual Class Details</h3>

        <div style="margin-bottom: 16px; display: flex; gap: 10px; flex-wrap: wrap;">
            @if($virtualClass->status === 'scheduled')
                <form method="POST" action="{{ route('instructor.classes.virtual_classes.start', [$class, $virtualClass]) }}" onsubmit="return confirm('Start this virtual class now? This will change status to Ongoing.');">
                    @csrf
                    <button type="submit" class="btn-submit" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-play"></i>
                        Start Class
                    </button>
                </form>
            @endif

            @if($virtualClass->meeting_url)
                <a href="{{ $virtualClass->meeting_url }}" target="_blank" class="btn-add" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Open Meeting
                </a>
            @endif

            <span class="btn btn-secondary" aria-disabled="true" title="Virtual class editing is not available from this view">
                <i class="fa-solid fa-lock"></i>
                Edit unavailable
            </span>
        </div>

        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Title:</span><span>{{ $virtualClass->title }}</span></div>
        <div class="modal-row"><span>Class:</span><span>{{ $class->code }} &mdash; {{ $class->name }}</span></div>
        <div class="modal-row"><span>Course:</span><span>{{ $virtualClass->course->code ?? '-' }} &mdash; {{ ($virtualClass->course->name ?? '-') }}</span></div>
        <div class="modal-row"><span>Instructor:</span><span>{{ $virtualClass->instructor->full_name ?? '-' }}</span></div>
        <div class="modal-row">
            <span>Status:</span>
            <span>
                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                    {{ $virtualClass->status === 'ongoing' ? 'background: #dcfce7; color: #16a34a;' :
                       $virtualClass->status === 'scheduled' ? 'background: #dbeafe; color: #1e40af;' :
                       $virtualClass->status === 'completed' ? 'background: #f1f5f9; color: #64748b;' :
                       'background: #fee2e2; color: #dc2626;' }}">
                    {{ ucfirst($virtualClass->status) }}
                </span>
            </span>
        </div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-calendar-clock"></i> Schedule</div>
        <div class="modal-row"><span>Date:</span><span>{{ $virtualClass->meeting_date->format('l, F j, Y') }}</span></div>
        <div class="modal-row"><span>Time:</span><span>{{ \Carbon\Carbon::parse($virtualClass->start_time)->format('g:i A') }} &ndash; {{ \Carbon\Carbon::parse($virtualClass->end_time)->format('g:i A') }}</span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-plug"></i> Meeting Details</div>
        <div class="modal-row">
            <span>Provider:</span>
            <span>
                @switch($virtualClass->meeting_provider)
                    @case('zoom')
                        <i class="fa-solid fa-video" style="color:#2D8CFF;"></i> Zoom
                        @break
                    @case('google_meet')
                        <i class="fa-solid fa-video" style="color:#EA4335;"></i> Google Meet
                        @break
                    @case('microsoft_teams')
                        <i class="fa-solid fa-video" style="color:#6264A7;"></i> Microsoft Teams
                        @break
                    @default
                        <i class="fa-solid fa-video"></i> Other
                @endswitch
            </span>
        </div>
        @if($virtualClass->meeting_url)
            <div class="modal-row"><span>URL:</span><span><a href="{{ $virtualClass->meeting_url }}" target="_blank" style="color:#2563eb;">{{ $virtualClass->meeting_url }}</a></span></div>
        @endif
        @if($virtualClass->meeting_id)
            <div class="modal-row"><span>Meeting ID:</span><span><code style="background:#f1f5f9; padding:2px 8px; border-radius:4px;">{{ $virtualClass->meeting_id }}</code></span></div>
        @endif
        @if($virtualClass->meeting_password)
            <div class="modal-row"><span>Password:</span><span><code style="background:#f1f5f9; padding:2px 8px; border-radius:4px;">{{ $virtualClass->meeting_password }}</code></span></div>
        @endif

        @if($virtualClass->description)
            <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description / Agenda</div>
            <div class="modal-row"><span></span><span style="white-space: pre-wrap;">{{ $virtualClass->description }}</span></div>
        @endif
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Attendees ({{ $virtualClass->attendees->count() }})</h3>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Joined At</th>
                    <th>Attendance</th>
                </tr>
            </thead>
            <tbody>
                @if($virtualClass->attendees->count() > 0)
                    @foreach($virtualClass->attendees as $attendee)
                        <tr>
                            <td>{{ $attendee->student->full_name ?? 'User #' . $attendee->user_id }}</td>
                            <td>{{ $attendee->student->email ?? '-' }}</td>
                            <td>{{ $attendee->joined_at ? $attendee->joined_at->format('M d, Y g:i A') : '-' }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'present' => 'background: #dcfce7; color: #16a34a;',
                                        'late' => 'background: #fef3c7; color: #d97706;',
                                        'absent' => 'background: #fee2e2; color: #dc2626;',
                                        'excused' => 'background: #dbeafe; color: #1e40af;',
                                    ];
                                @endphp
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; {{ $statusColors[$attendee->attendance_status] ?? 'background: #f1f5f9; color: #64748b;' }}">
                                    {{ ucfirst($attendee->attendance_status ?? 'Unknown') }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:#aaa;">
                            No attendees have joined this virtual class yet.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="{{ route('instructor.classes.virtual_classes.index', $class) }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            &larr; Back to Virtual Classes
        </a>
    </div>
@endsection

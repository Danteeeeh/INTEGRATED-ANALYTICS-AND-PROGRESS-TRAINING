@extends('layouts.admin')

@section('title', 'Attendance Record')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Attendance Record"
        subtitle="Attendance details for a single student session."
        icon="fa-clipboard-user"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $attendanceRecord->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.attendance.edit', $attendanceRecord) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Record Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div>{{ $attendanceRecord->student?->name ?? '—' }}</div>
                    <div class="user-email">{{ $attendanceRecord->student?->email }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $attendanceRecord->class?->code ?? '—' }}</div>
                    <div class="user-email">{{ $attendanceRecord->class?->course?->title }}</div>
                </div>
                <div class="form-field">
                    <label>Date</label>
                    <div>{{ $attendanceRecord->attendance_date?->format('l, F j, Y') }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $attendanceRecord->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Session</label>
                    <div>{{ $attendanceRecord->session_title ?? $attendanceRecord->virtualClass?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Recorded By</label>
                    <div>{{ $attendanceRecord->recordedBy?->name ?? '—' }}</div>
                </div>
                @if($attendanceRecord->joined_at)
                    <div class="form-field">
                        <label>Joined</label>
                        <div>{{ $attendanceRecord->joined_at?->format('g:i A') }}</div>
                    </div>
                @endif
                @if($attendanceRecord->left_at)
                    <div class="form-field">
                        <label>Left</label>
                        <div>{{ $attendanceRecord->left_at?->format('g:i A') }}</div>
                    </div>
                @endif
                @if($attendanceRecord->duration_minutes !== null)
                    <div class="form-field">
                        <label>Duration</label>
                        <div>{{ $attendanceRecord->duration_minutes }} min</div>
                    </div>
                @endif
                @if($attendanceRecord->notes)
                    <div class="form-field full">
                        <label>Notes</label>
                        <div>{{ $attendanceRecord->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.attendance.destroy', $attendanceRecord) }}" method="POST" onsubmit="return confirm('Delete this attendance record?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

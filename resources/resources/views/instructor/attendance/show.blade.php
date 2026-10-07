@extends('layouts.instructor')
@section('title', 'Attendance Details')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Attendance Details"
        subtitle="{{ $class->code }} — {{ $attendance->attendance_date?->format('l, F j, Y') }}"
        icon="fa-clipboard-user"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $attendance->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.attendance.edit', [$class, $attendance]) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('instructor.classes.attendance.index', $class) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Record</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Student</label>
                    <div>{{ $attendance->student?->full_name ?? $attendance->student?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $attendance->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Session</label>
                    <div>{{ $attendance->session_title ?? $attendance->virtualClass?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Recorded By</label>
                    <div>{{ $attendance->recordedBy?->name ?? '—' }}</div>
                </div>
                @if($attendance->notes)
                    <div class="form-field full">
                        <label>Notes</label>
                        <div>{{ $attendance->notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-users"></i> All Records for this Session</h3>
            <span class="user-status">{{ $allRecords->count() }} students</span>
        </div>
        <div class="user-panel-body">
            @if($allRecords->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allRecords as $record)
                                <tr>
                                    <td>{{ $record->student?->full_name ?? $record->student?->name ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $record->status }}" /></td>
                                    <td>{{ $record->notes ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-users" title="No session records" description="No other records exist for this session." />
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Attendance Report')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-calendar-check"></i> Attendance report</h3>
        <span class="dash-section-kicker">Attendance records and rates</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.attendance') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search attendance">
        <select name="status" aria-label="Filter status">
            <option value="">All statuses</option>
            <option value="present" @selected(request('status')==='present')>Present</option>
            <option value="late" @selected(request('status')==='late')>Late</option>
            <option value="absent" @selected(request('status')==='absent')>Absent</option>
            <option value="excused" @selected(request('status')==='excused')>Excused</option>
        </select>
        <select name="class_id" aria-label="Filter class">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.attendance') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'attendance'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th>Status</th>
                <th>Session</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendanceRecords as $record)
                <tr>
                    <td>{{ $record->attendance_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $record->student?->name ?? '—' }}</td>
                    <td>{{ $record->class?->code ?? '—' }}</td>
                    <td><span class="dash-meta-chip {{ $record->status === 'present' ? 'm-green' : ($record->status === 'absent' ? 'm-rose' : 'm-amber') }}">{{ ucfirst($record->status) }}</span></td>
                    <td>{{ $record->session_title ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $attendanceRecords->appends(request()->query())->links() }}
</section>
@endsection

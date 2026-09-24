@extends('layouts.registrar')
@section('title', 'Attendance')
@php($activeNav = 'attendance')

@section('page-title-bar')
<div class="page-title-bar">
    <h2 class="page-title"><i class="fa-solid fa-clipboard-user"></i> Attendance Records</h2>
    <div class="page-actions"></div>
</div>
@endsection

@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-filter"></i> Filter attendance</h3>
    </div>
    <form method="GET" action="{{ route('registrar.attendance.index') }}" class="user-toolbar" style="padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;">
        <select name="class_id" class="form-control" aria-label="Filter by class">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->code }} — {{ $class->course?->title ?? '' }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach(['present', 'late', 'absent', 'excused'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('registrar.attendance.index') }}" class="btn btn-cancel">Clear</a>
    </form>
</div>

<div class="crud-card" style="margin-top:16px">
    <div class="crud-header">
        <h3><i class="fa-solid fa-clipboard-user"></i> Records ({{ $records->total() }})</h3>
    </div>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Class</th>
                <th>Student</th>
                <th>Session</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $record)
                <tr>
                    <td>{{ $record->attendance_date?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $record->class?->code ?? '—' }}</td>
                    <td>{{ $record->student?->full_name ?? '—' }}</td>
                    <td>{{ $record->session_title ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $record->status === 'present' ? 'badge-active' : 'badge-inactive' }}">{{ ucfirst($record->status) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 18px">@if(method_exists($records, 'links')){{ $records->links() }}@endif</div>
</div>
@endsection

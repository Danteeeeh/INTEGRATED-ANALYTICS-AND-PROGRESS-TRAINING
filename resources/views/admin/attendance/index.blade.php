@extends('layouts.admin')

@section('title', 'Attendance')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Attendance"
        subtitle="Record and review attendance across classes and virtual sessions."
        icon="fa-clipboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.attendance.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Record Attendance</a>
            <a href="{{ route('admin.attendance.export') }}" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Export</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.attendance.index') }}">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }}</option>
                @endforeach
            </select>
            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->code }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-control" type="date" name="attendance_date" value="{{ request('attendance_date') }}" aria-label="Filter by date">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.attendance.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($attendanceRecords->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Session</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendanceRecords as $record)
                                <tr>
                                    <td>{{ $record->attendance_date?->format('M j, Y') }}</td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">{{ $record->student?->name ?? '—' }}</div>
                                            <div class="user-email">{{ $record->student?->identifier ?? '' }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $record->class?->code ?? '—' }}</td>
                                    <td>
                                        @if($record->session_title)
                                            {{ $record->session_title }}
                                        @elseif($record->virtualClass)
                                            {{ $record->virtualClass->title }}
                                        @else
                                            — 
                                        @endif
                                    </td>
                                    <td><x-user-status-badge status="{{ $record->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.attendance.show', $record) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.attendance.edit', $record) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-clipboard-user"
                    title="No attendance records found"
                    description="Record attendance for a class to see it here."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.attendance.create') }}"><i class="fa-solid fa-plus"></i> Record Attendance</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($attendanceRecords->hasPages())
                <div class="pagination">{{ $attendanceRecords->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
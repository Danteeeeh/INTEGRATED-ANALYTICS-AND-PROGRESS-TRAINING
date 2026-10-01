@extends('layouts.admin')
@section('title', 'Class Schedules')
@php($activeNav = 'schedules')

@section('page-title-bar')
<div class="page-title-bar">
    <h2 class="page-title"><i class="fa-solid fa-clock"></i> Class Schedules</h2>
    <div class="page-actions"></div>
</div>
@endsection

@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-filter"></i> Filter schedules</h3>
    </div>
    <form method="GET" action="{{ route('admin.classes.schedules.index') }}" class="user-toolbar" style="padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;">
        <select name="academic_period_id" class="form-control" aria-label="Filter by academic period">
            <option value="">All academic periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected(request('academic_period_id') == $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        <select name="course_id" class="form-control" aria-label="Filter by course">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
            @endforeach
        </select>
        <select name="instructor_id" class="form-control" aria-label="Filter by instructor">
            <option value="">All instructors</option>
            @foreach($instructors as $instructor)
                <option value="{{ $instructor->id }}" @selected(request('instructor_id') == $instructor->id)>{{ $instructor->full_name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('admin.classes.schedules.index') }}" class="btn btn-cancel">Clear</a>
    </form>
</div>

<div class="crud-card" style="margin-top:16px">
    <div class="crud-header">
        <h3><i class="fa-solid fa-calendar-days"></i> Schedules ({{ $classes->total() }})</h3>
    </div>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Section</th>
                <th>Course</th>
                <th>Instructor</th>
                <th>Schedule</th>
                <th>Room</th>
                <th>Period</th>
                <th>Students</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                <tr>
                    <td><a href="{{ route('admin.classes.show', $class) }}">{{ $class->code }}</a></td>
                    <td>{{ $class->course?->code ?? '—' }} · {{ $class->course?->title ?? '—' }}</td>
                    <td>{{ $class->instructor?->full_name ?? 'Unassigned' }}</td>
                    <td>{{ $class->schedule ?? '—' }}</td>
                    <td>{{ $class->room ?? '—' }}</td>
                    <td>{{ $class->academicPeriod?->name ?? '—' }}</td>
                    <td>{{ $class->enrollments_count ?? $class->enrollments->count() }}</td>
                    <td>
                        <span class="badge {{ $class->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                            {{ ucfirst($class->status ?? 'active') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="search-no-results">No schedules found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 18px">@if(method_exists($classes, 'links')){{ $classes->links() }}@endif</div>
</div>
@endsection

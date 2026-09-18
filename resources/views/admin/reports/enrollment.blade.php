@extends('layouts.admin')
@section('title', 'Enrollment Report')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-plus"></i> Enrollment report</h3>
        <span class="dash-section-kicker">Active and completed enrollments</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.enrollment') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search enrollments">
        <select name="status" aria-label="Filter status">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="completed" @selected(request('status')==='completed')>Completed</option>
            <option value="dropped" @selected(request('status')==='dropped')>Dropped</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
        </select>
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.enrollment') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'enrollment'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Course</th>
                <th>Status</th>
                <th>Grade</th>
                <th>Enrolled</th>
            </tr>
        </thead>
        <tbody>
            @forelse($enrollments as $enrollment)
                <tr>
                    <td>{{ $enrollment->student?->name ?? $enrollment->student?->email ?? '—' }}</td>
                    <td>{{ $enrollment->class?->code ?? '—' }}</td>
                    <td>{{ $enrollment->class?->course?->title ?? '—' }}</td>
                    <td><span class="dash-meta-chip {{ $enrollment->status === 'active' ? 'm-green' : 'm-gray' }}">{{ ucfirst($enrollment->status) }}</span></td>
                    <td>{{ $enrollment->final_grade !== null ? number_format($enrollment->final_grade, 1) : '—' }}</td>
                    <td>{{ $enrollment->enrolled_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="search-no-results">No enrollments found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $enrollments->appends(request()->query())->links() }}
</section>
@endsection

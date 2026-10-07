@extends('layouts.admin')
@section('title', 'Student Enrollment Report')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-plus"></i> New student registrations</h3>
        <span class="dash-section-kicker">Students who enrolled into the school</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.enrollment') }}" id="enrollmentFilterForm">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search enrollments" id="enrollmentSearch">
        <select name="status" aria-label="Filter status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="completed" @selected(request('status')==='completed')>Completed</option>
            <option value="dropped" @selected(request('status')==='dropped')>Dropped</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
        </select>
        <select name="academic_period_id" aria-label="Filter academic period" onchange="this.form.submit()">
            <option value="">All periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        <select name="course_id" aria-label="Filter course" onchange="this.form.submit()">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }}</option>
            @endforeach
        </select>
        <a class="btn btn-secondary" href="{{ route('admin.reports.enrollment') }}">Clear</a>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('importEnrollmentModal').classList.add('active')"><i class="fa-solid fa-file-import"></i> Import</button>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'enrollment'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Identifier</th>
                <th>Department</th>
                <th>Program</th>
                <th>Section</th>
                <th>Status</th>
                <th>Registered</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td><strong>{{ $student->full_name }}</strong></td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $student->identifier ?? '—' }}</td>
                    <td>{{ $student->department?->name ?? '—' }}</td>
                    <td>{{ $student->program?->name ?? '—' }}</td>
                    <td>{{ $student->section?->name ?? '—' }}@if($student->section?->code) ({{ $student->section->code }})@endif</td>
                    <td><span class="dash-meta-chip {{ $student->status === 'active' ? 'm-green' : 'm-gray' }}">{{ ucfirst($student->status) }}</span></td>
                    <td>{{ $student->created_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="search-no-results">No enrolled students found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $students->appends(request()->query())->links() }}
</section>

<script>
(function () {
    const search = document.getElementById('enrollmentSearch');
    const form = document.getElementById('enrollmentFilterForm');
    if (!search || !form) return;
    let timer = null;
    search.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { form.submit(); }, 600);
    });
})();
</script>
@include('admin.reports._import_modal', [
    'modalId' => 'importEnrollmentModal',
    'importTitle' => 'Students',
    'importAction' => route('admin.reports.enrollment.import'),
    'importColumns' => 'first_name, last_name, email, identifier, status',
])

@endsection

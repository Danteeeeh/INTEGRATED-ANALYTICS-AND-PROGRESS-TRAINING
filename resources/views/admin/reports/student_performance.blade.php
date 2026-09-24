@extends('layouts.admin')
@section('title', 'Student Performance')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> Student performance</h3>
        <span class="dash-section-kicker">Grades and academic results</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.student-performance') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search students">
        <select name="class_id" aria-label="Filter class">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }}</option>
            @endforeach
        </select>
        <select name="academic_period_id" aria-label="Filter academic period">
            <option value="">All periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->code }} — {{ $period->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.student-performance') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'student-performance'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Enrollments</th>
                <th>Average grade</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                @php($avg = $student->enrollments->whereNotNull('final_grade')->avg('final_grade'))
                <tr>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->email }}</td>
                    <td>{{ $student->enrollments->count() }}</td>
                    <td>{{ $avg !== null ? number_format($avg, 1) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="search-no-results">No students found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $students->appends(request()->query())->links() }}
</section>
@endsection

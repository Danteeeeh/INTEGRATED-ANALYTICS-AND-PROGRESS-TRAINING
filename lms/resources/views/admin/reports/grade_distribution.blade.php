@extends('layouts.admin')
@section('title', 'Grade Distribution')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-chart-pie"></i> Grade distribution</h3>
        <span class="dash-section-kicker">Grade ranges and distribution</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.grade-distribution') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search grades">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }}</option>
            @endforeach
        </select>
        <select name="class_id" aria-label="Filter class">
            <option value="">All classes</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }}</option>
            @endforeach
        </select>
        <select name="student_id" aria-label="Filter student">
            <option value="">All students</option>
            @foreach($students as $student)
                <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>{{ $student->full_name }}</option>
            @endforeach
        </select>
        <select name="letter_grade" aria-label="Filter letter grade">
            <option value="">All grades</option>
            @foreach(['A', 'B', 'C', 'D', 'F'] as $grade)
                <option value="{{ $grade }}" @selected(request('letter_grade') === $grade)>{{ $grade }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.grade-distribution') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'grade-distribution'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Item</th>
                <th>Percent</th>
                <th>Letter</th>
                <th>Graded</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $grade)
                <tr>
                    <td>{{ $grade->student?->name ?? '—' }}</td>
                    <td>{{ $grade->item?->title ?? '—' }}</td>
                    <td>{{ $grade->score_percent !== null ? number_format($grade->score_percent, 1) . '%' : '—' }}</td>
                    <td>{{ $grade->letter_grade ?? '—' }}</td>
                    <td>{{ $grade->graded_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No grades found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $grades->appends(request()->query())->links() }}
</section>
@endsection

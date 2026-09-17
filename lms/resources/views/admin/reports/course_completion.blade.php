@extends('layouts.admin')
@section('title', 'Course Completion')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-certificate"></i> Course completion</h3>
        <span class="dash-section-kicker">Completion progress by course</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.course-completion') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search completions">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }}</option>
            @endforeach
        </select>
        <select name="academic_period_id" aria-label="Filter academic period">
            <option value="">All periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->code }} — {{ $period->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.course-completion') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'course-completion'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Course</th>
                <th>Class</th>
                <th>Progress</th>
                <th>Final grade</th>
                <th>Completed</th>
            </tr>
        </thead>
        <tbody>
            @forelse($completions as $completion)
                <tr>
                    <td>{{ $completion->student?->name ?? '—' }}</td>
                    <td>{{ $completion->class?->course?->title ?? '—' }}</td>
                    <td>{{ $completion->class?->code ?? '—' }}</td>
                    <td>{{ number_format($completion->completion_percent ?? 0, 1) }}%</td>
                    <td>{{ $completion->final_grade !== null ? number_format($completion->final_grade, 1) : '—' }}</td>
                    <td>{{ $completion->completed_at?->format('M d, Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="search-no-results">No completions found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $completions->appends(request()->query())->links() }}
</section>
@endsection

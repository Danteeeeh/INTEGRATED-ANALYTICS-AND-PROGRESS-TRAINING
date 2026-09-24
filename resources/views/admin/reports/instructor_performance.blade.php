@extends('layouts.admin')
@section('title', 'Instructor Performance')
@php($activeNav = 'reports')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-chalkboard-user"></i> Instructor performance</h3>
        <span class="dash-section-kicker">Teaching activity overview</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.reports.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.reports.instructor-performance') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search instructor..." aria-label="Search instructors">
        <select name="academic_period_id" aria-label="Filter academic period">
            <option value="">All periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->code }} — {{ $period->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.reports.instructor-performance') }}">Clear</a>
        <a class="btn btn-primary" href="{{ route('admin.reports.export', array_merge(['type' => 'instructor-performance'], request()->query())) }}"><i class="fa-solid fa-download"></i> Export</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Instructor</th>
                <th>Email</th>
                <th>Classes</th>
                <th>Students</th>
            </tr>
        </thead>
        <tbody>
            @forelse($instructors as $instructor)
                <tr>
                    <td>{{ $instructor->name }}</td>
                    <td>{{ $instructor->email }}</td>
                    <td>{{ $instructor->classesInstructing->count() }}</td>
                    <td>{{ $instructor->classesInstructing->sum(fn ($class) => $class->enrollments->count()) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="search-no-results">No instructors found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $instructors->appends(request()->query())->links() }}
</section>
@endsection

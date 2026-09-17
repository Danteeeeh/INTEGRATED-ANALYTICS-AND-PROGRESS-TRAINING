@extends('layouts.admin')
@section('title', 'Gradebook')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> Gradebook</h3>
        <span class="dash-section-kicker">Open a class to view and release grades</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.gradebook.grades.history') }}"><i class="fa-solid fa-clock-rotate-left"></i> Grade history</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.gradebook.index') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search class or course..." aria-label="Search gradebook">
        <select name="course_id" aria-label="Filter course">
            <option value="">All courses</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }} — {{ $course->title }}</option>
            @endforeach
        </select>
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('admin.gradebook.index') }}">Clear</a>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Class</th>
                <th>Course</th>
                <th>Instructor</th>
                <th>Students</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                <tr>
                    <td>{{ $class->code }}</td>
                    <td>{{ $class->course?->title ?? '—' }}</td>
                    <td>{{ $class->instructor?->name ?? $class->instructor?->full_name ?? '—' }}</td>
                    <td>{{ $class->enrollments->where('status', 'active')->count() }}</td>
                    <td class="action-buttons">
                        <a class="btn btn-sm btn-primary" href="{{ route('admin.gradebook.class', $class) }}"><i class="fa-solid fa-table"></i> Open</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No classes found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $classes->appends(request()->query())->links() }}
</section>
@endsection

@extends('layouts.admin')
@section('title', 'Grade History')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-clock-rotate-left"></i> Grade history</h3>
        <span class="dash-section-kicker">Track grade changes by class and student</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.gradebook.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET">
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
        <button class="btn btn-secondary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
    <table class="dash-table">
        <thead>
            <tr>
                <th>When</th>
                <th>Student</th>
                <th>Item</th>
                <th>Changed by</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gradeHistory as $history)
                <tr>
                    <td>{{ $history->changed_at?->diffForHumans() ?? $history->created_at?->diffForHumans() }}</td>
                    <td>{{ $history->grade?->student?->name ?? '—' }}</td>
                    <td>{{ $history->grade?->item?->title ?? '—' }}</td>
                    <td>{{ $history->changedBy?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="search-no-results">No grade history found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $gradeHistory->links() }}
</section>
@endsection

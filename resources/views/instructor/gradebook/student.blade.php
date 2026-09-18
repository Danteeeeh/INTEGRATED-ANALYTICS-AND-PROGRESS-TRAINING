@extends('layouts.instructor')
@section('title', 'Student Grades')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-graduate"></i> {{ $student->name ?? $student->full_name }}</h3>
        <span class="dash-section-kicker">{{ $class->code }} · {{ $class->course?->title ?? 'Gradebook' }}</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('instructor.classes.gradebook.index', $class) }}"><i class="fa-solid fa-arrow-left"></i> Back to gradebook</a>
</div>
<div class="dash-stats">
    <div class="dash-stat"><span class="dash-stat-label">Earned</span><span class="dash-stat-value">{{ number_format($earnedPoints, 1) }}</span></div>
    <div class="dash-stat"><span class="dash-stat-label">Possible</span><span class="dash-stat-value">{{ number_format($totalPoints, 1) }}</span></div>
</div>
<section class="dash-panel">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Score</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gradeItems as $item)
                @php($grade = $item->grades->first())
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->category?->name ?? '—' }}</td>
                    <td>{{ $grade ? number_format($grade->points, 1) . ' / ' . number_format($item->max_points, 1) : '—' }}</td>
                    <td>{{ $item->is_released ? 'Released' : 'Hidden' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="search-no-results">No grade items for this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection

@extends('layouts.admin')
@section('title', 'Class Gradebook')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> {{ $class->code }} gradebook</h3>
        <span class="dash-section-kicker">{{ $class->course?->title ?? 'Class grades' }}</span>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('admin.gradebook.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <a class="btn btn-secondary" href="{{ route('admin.gradebook.grades.history', ['class_id' => $class->id]) }}"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
    </div>
</div>
<section class="dash-panel">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Student</th>
                @foreach($gradeItems as $item)
                    <th>{{ $item->title }}<br><small>{{ $item->max_points }} pts</small></th>
                @endforeach
                <th>Released</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $student)
                <tr>
                    <td>{{ $student?->name ?? $student?->full_name ?? 'Student' }}</td>
                    @foreach($gradeItems as $item)
                        @php($grade = $grades->get($item->id . '-' . $student->id))
                        <td>{{ $grade ? number_format($grade->score_percent, 1) . '%' : '—' }}</td>
                    @endforeach
                    <td>{{ $gradeItems->where('is_released', true)->count() }}/{{ $gradeItems->count() }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 2 + $gradeItems->count() }}" class="search-no-results">No active students in this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection

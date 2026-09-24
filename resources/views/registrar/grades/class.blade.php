@extends('layouts.registrar')
@section('title', 'Grade Status — ' . $class->code)
@php($activeNav = 'grades')

@section('page-title-bar')
<div class="page-title-bar">
    <h2 class="page-title"><i class="fa-solid fa-graduation-cap"></i> Grade Status — {{ $class->code }}</h2>
    <div class="page-actions">
        <a href="{{ route('registrar.grades.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <form method="POST" action="{{ route('registrar.grades.return', $class) }}" style="display:inline" onsubmit="return confirm('Return all grades for this class to the instructor for correction?')">
            @csrf
            <button type="submit" class="btn btn-warning"><i class="fa-solid fa-rotate-left"></i> Return for correction</button>
        </form>
    </div>
</div>
@endsection

@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-users"></i> {{ $class->course?->code ?? '' }} · {{ $class->course?->title ?? 'Course' }} — {{ $class->instructor?->full_name ?? 'Unassigned' }}</h3>
    </div>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Identifier</th>
                <th>Status</th>
                <th>Final Grade</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($class->enrollments as $enrollment)
                <tr>
                    <td>{{ $enrollment->student?->full_name ?? '—' }}</td>
                    <td>{{ $enrollment->student?->identifier ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $enrollment->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                            {{ ucfirst($enrollment->status) }}
                        </span>
                    </td>
                    <td>
                        @if($enrollment->final_grade !== null)
                            <span class="badge badge-active">{{ number_format((float) $enrollment->final_grade, 2) }}</span>
                        @else
                            <span class="badge badge-inactive">Not graded</span>
                        @endif
                    </td>
                    <td style="font-size:.74rem;color:var(--bcp-muted)">{{ $enrollment->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="search-no-results">No enrollments in this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

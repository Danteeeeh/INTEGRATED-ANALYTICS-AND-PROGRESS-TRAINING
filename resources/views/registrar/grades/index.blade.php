@extends('layouts.registrar')
@section('title', 'Grade Status')
@php($activeNav = 'grades')

@section('page-title-bar')
<div class="page-title-bar">
    <h2 class="page-title"><i class="fa-solid fa-graduation-cap"></i> Grade Status</h2>
    <div class="page-actions"></div>
</div>
@endsection

@section('content')
<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-filter"></i> Filter grade status</h3>
    </div>
    <form method="GET" action="{{ route('registrar.grades.index') }}" class="user-toolbar" style="padding:12px 16px;display:flex;gap:8px;flex-wrap:wrap;">
        <select name="academic_period_id" class="form-control" aria-label="Filter by academic period">
            <option value="">All academic periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected(request('academic_period_id') == $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('registrar.grades.index') }}" class="btn btn-cancel">Clear</a>
    </form>
</div>

<div class="crud-card" style="margin-top:16px">
    <div class="crud-header">
        <h3><i class="fa-solid fa-clipboard-check"></i> Classes ({{ $classes->total() }})</h3>
    </div>
    <table class="crud-table">
        <thead>
            <tr>
                <th>Section</th>
                <th>Course</th>
                <th>Instructor</th>
                <th>Active students</th>
                <th>Graded</th>
                <th>Completion</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                @php($pct = $class->active_enrollments > 0 ? round($class->graded_enrollments / $class->active_enrollments * 100) : 0)
                <tr>
                    <td><a href="{{ route('registrar.grades.class', $class) }}">{{ $class->code }}</a></td>
                    <td>{{ $class->course?->code ?? '—' }} · {{ $class->course?->title ?? '—' }}</td>
                    <td>{{ $class->instructor?->full_name ?? 'Unassigned' }}</td>
                    <td>{{ $class->active_enrollments }}</td>
                    <td>{{ $class->graded_enrollments }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div style="flex:1;min-width:90px;height:8px;border-radius:99px;background:rgba(153,174,214,.16);overflow:hidden">
                                <div style="height:100%;width:{{ $pct }}%;border-radius:99px;background:linear-gradient(90deg,#4d8ff0,#62c9f5)"></div>
                            </div>
                            <span style="font-size:.72rem;font-weight:700;color:var(--bcp-muted)">{{ $pct }}%</span>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('registrar.grades.class', $class) }}" class="btn btn-icon btn-view" title="View grade status"><i class="fa-solid fa-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="search-no-results">No classes found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="padding:14px 18px">@if(method_exists($classes, 'links')){{ $classes->links() }}@endif</div>
</div>
@endsection

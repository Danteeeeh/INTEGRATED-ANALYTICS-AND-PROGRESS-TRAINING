@extends('layouts.admin')
@section('title', 'Gradebook by Program')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-graduation-cap"></i> Gradebook by Program</h3>
        <span class="dash-section-kicker">View grades organized by program and section</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('admin.gradebook.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Classes</a>
</div>
<section class="dash-panel">
    <form class="table-toolbar" method="GET" action="{{ route('admin.gradebook.programs') }}">
        <select name="academic_period_id" aria-label="Filter academic period" onchange="this.form.submit()">
            <option value="">All academic periods</option>
            @foreach($academicPeriods as $period)
                <option value="{{ $period->id }}" @selected((string) request('academic_period_id') === (string) $period->id)>{{ $period->name }}</option>
            @endforeach
        </select>
        <a class="btn btn-secondary" href="{{ route('admin.gradebook.programs') }}">Clear</a>
    </form>
    @forelse($programs as $program)
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px; border-radius: 8px 8px 0 0;">
                <h4 style="margin: 0;">{{ $program->code }} — {{ $program->name }}</h4>
            </div>
            <div class="card-body" style="padding: 16px;">
                @forelse($program->sections as $section)
                    <div style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <h5 style="margin: 0; color: #1e293b;">{{ $section->code }} — {{ $section->name }}</h5>
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.gradebook.sections', $section) }}">
                                <i class="fa-solid fa-table"></i> View Grades
                            </a>
                        </div>
                        <div style="display: flex; gap: 16px; flex-wrap: wrap;">
                            <span><strong>Classes:</strong> {{ $section->classes->count() }}</span>
                            <span><strong>Students:</strong> {{ $section->students->count() }}</span>
                        </div>
                    </div>
                @empty
                    <p style="color: #64748b; margin: 0;">No sections found.</p>
                @endforelse
            </div>
        </div>
    @empty
        <div class="search-no-results">No programs found.</div>
    @endforelse
</section>
@endsection

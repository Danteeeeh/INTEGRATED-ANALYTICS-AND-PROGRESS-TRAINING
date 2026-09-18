@extends('layouts.instructor')

@section('title', 'My Classes')
@php
    $activeNav = 'classes';
    $pageTitle = 'My Classes';
    $pageIcon = '<i class="fa-solid fa-school"></i>';
    $feature = request('feature');
    $featureMeta = [
        'gradebook' => ['label' => 'Gradebook', 'icon' => 'fa-graduation-cap', 'hint' => 'Enter grades and review student performance', 'route' => 'instructor.classes.gradebook.index', 'color' => 'violet'],
        'attendance' => ['label' => 'Attendance', 'icon' => 'fa-clipboard-user', 'hint' => 'Record and review attendance', 'route' => 'instructor.classes.attendance.index', 'color' => 'green'],
        'virtual_classes' => ['label' => 'Virtual Classes', 'icon' => 'fa-video', 'hint' => 'Manage online class sessions', 'route' => 'instructor.classes.virtual_classes.index', 'color' => 'cyan'],
        'calendar' => ['label' => 'Calendar', 'icon' => 'fa-calendar', 'hint' => 'View class events and schedule', 'route' => 'instructor.classes.calendar.index', 'color' => 'amber'],
    ];
@endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="My Classes"
        subtitle="Manage the classes assigned to you."
        icon="fa-school"
    />

    <form class="user-toolbar" method="GET" action="{{ route('instructor.classes.index') }}" style="margin-bottom:16px">
        <select class="form-control" name="status" aria-label="Filter class status">
            <option value="">All Status</option>
            @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        <a class="btn btn-secondary" href="{{ route('instructor.classes.index') }}">Clear</a>
    </form>

    @if($feature && isset($featureMeta[$feature]))
        <section class="feature-picker">
            <div class="feature-picker-icon fp-{{ $featureMeta[$feature]['color'] }}"><i class="fa-solid {{ $featureMeta[$feature]['icon'] }}"></i></div>
            <div class="feature-picker-copy"><span class="feature-picker-kicker">Class feature</span><h3>Choose a class for {{ $featureMeta[$feature]['label'] }}</h3><p>{{ $featureMeta[$feature]['hint'] }}. Piliin ang class na gusto mong buksan.</p></div>
            <a href="{{ route('instructor.classes.index') }}" class="feature-picker-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        </section>
        <div class="class-choice-grid">
            @forelse($classes as $class)
                <a href="{{ route($featureMeta[$feature]['route'], $class) }}" class="class-choice-card">
                    <span class="choice-icon fp-{{ $featureMeta[$feature]['color'] }}"><i class="fa-solid {{ $featureMeta[$feature]['icon'] }}"></i></span>
                    <span class="choice-copy"><strong>{{ $class->code }}</strong><small>{{ $class->course->name ?? $class->course->title ?? 'Class' }} · {{ $class->schedule ?? 'No schedule' }}</small></span>
                    <i class="fa-solid fa-arrow-right choice-arrow"></i>
                </a>
            @empty
                <div class="feature-empty">Wala pang class na naka-assign sa iyo.</div>
            @endforelse
        </div>
    @endif

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-school"></i> My Classes</h3></div>
        <div class="user-panel-body">
            @if($classes->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Course</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                <tr>
                                    <td><strong>{{ $class->code }}</strong></td>
                                    <td>{{ $class->name }}</td>
                                    <td>{{ $class->course->name ?? $class->course->title ?? '-' }}</td>
                                    <td>
                                        {{ $class->enrolled_count ?? $class->enrollments_count ?? 0 }} / {{ $class->max_students }}
                                        @if(($class->enrolled_count ?? 0) >= ($class->max_students ?? 0))
                                            <x-user-status-badge status="inactive" label="Full" />
                                        @else
                                            <x-user-status-badge status="active" label="Available" />
                                        @endif
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('instructor.classes.show', $class) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('instructor.classes.gradebook.index', $class) }}" class="btn btn-icon" title="Gradebook"><i class="fa-solid fa-graduation-cap"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-school"
                    title="No classes assigned"
                    description="Wala pang class na naka-assign sa iyo."
                />
            @endif
        </div>
    </div>

    <style>
        .feature-picker{display:flex;align-items:center;gap:14px;margin-bottom:16px;padding:16px 18px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:14px;background:linear-gradient(135deg,rgba(36,73,198,.22),rgba(10,16,32,.72))}.feature-picker-icon,.choice-icon{display:grid;place-items:center;flex:none;border-radius:11px;width:42px;height:42px;font-size:16px}.feature-picker-copy{flex:1;min-width:0}.feature-picker-kicker{display:block;color:var(--bcp-cyan-400,#62c9f5);font-size:.62rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}.feature-picker-copy h3{margin:3px 0;color:var(--bcp-ink,#eef4ff);font-size:.95rem}.feature-picker-copy p{margin:0;color:var(--bcp-muted,#98a7c4);font-size:.75rem}.feature-picker-clear{color:var(--bcp-muted,#98a7c4);font-size:.72rem;text-decoration:none;white-space:nowrap}.class-choice-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:18px}.class-choice-card{display:flex;align-items:center;gap:11px;padding:14px;border:1px solid var(--bcp-line,rgba(153,174,214,.18));border-radius:12px;background:var(--bcp-card,var(--dash-surface,#151c2c));text-decoration:none;transition:transform .15s,border-color .15s}.class-choice-card:hover{transform:translateY(-2px);border-color:rgba(98,201,245,.45)}.choice-copy{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}.choice-copy strong{color:var(--bcp-ink,#eef4ff);font-size:.82rem}.choice-copy small{color:var(--bcp-muted,#98a7c4);font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.choice-arrow{color:var(--bcp-cyan-400,#62c9f5);font-size:.75rem}.feature-empty{grid-column:1/-1;text-align:center;color:var(--bcp-muted,#98a7c4);padding:24px}.fp-violet{background:rgba(139,92,246,.15);color:#c4b5fd}.fp-green{background:rgba(52,211,153,.15);color:#6ee7b7}.fp-cyan{background:rgba(34,211,238,.14);color:#67e8f9}.fp-amber{background:rgba(251,191,36,.14);color:#fcd34d}@media(max-width:640px){.feature-picker{align-items:flex-start}.feature-picker-clear{margin-left:auto}.class-choice-grid{grid-template-columns:1fr}}
    </style>
</div>
@endsection
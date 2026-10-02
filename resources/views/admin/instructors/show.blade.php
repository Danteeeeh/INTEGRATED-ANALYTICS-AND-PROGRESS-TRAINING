@extends('layouts.admin')

@section('title', 'Instructor Details')
@php $activeNav = 'instructors'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $instructor->name }}"
        subtitle="Instructor account overview, contact details, and assigned classes."
        icon="fa-chalkboard-user"
    >
        <x-slot name="meta">
            <span class="live-dot"></span>
            <span>{{ $instructor->role?->name ?? 'Instructor' }}</span>
            <span>·</span>
            <span>{{ ucfirst($instructor->status ?? 'active') }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.instructors.edit', $instructor) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.instructors.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-stat-grid">
        <x-user-stat-card label="Classes" value="{{ $instructor->classesInstructing->count() }}" icon="fa-school" footer="Assigned classes" />
        <x-user-stat-card label="Status" value="{{ ucfirst($instructor->status ?? 'active') }}" icon="fa-circle-check" footer="Account state" />
        <x-user-stat-card label="Email" value="{{ $instructor->email }}" icon="fa-envelope" footer="Contact" />
        <x-user-stat-card label="Joined" value="{{ $instructor->created_at?->format('M Y') ?? '—' }}" icon="fa-calendar" footer="Member since" />
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-address-card"></i> Contact Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Email</label>
                    <div>{{ $instructor->email }}</div>
                </div>
                <div class="form-field">
                    <label>Phone</label>
                    <div>{{ $instructor->phone ?? '—' }}</div>
                </div>
                <div class="form-field full">
                    <label>Address</label>
                    <div>{{ $instructor->address ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Department</label>
                    <div>{{ $instructor->department?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Program</label>
                    <div>{{ $instructor->program?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Section</label>
                    <div>{{ $instructor->section?->name ?? '—' }}@if($instructor->section?->code) ({{ $instructor->section->code }})@endif</div>
                </div>
                <div class="form-field full">
                    <label>Assign Section</label>
                    <form method="POST" action="{{ route('admin.instructors.assign-section', $instructor) }}" style="display:inline-flex;gap:8px;align-items:center">
                        @csrf
                        <select name="section_id" style="padding:6px 10px;border:1px solid var(--dash-line,#dce4f0);border-radius:8px;font-size:.78rem">
                            <option value="">No Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected($instructor->section_id === $section->id)>{{ $section->name }} ({{ $section->code }}) — {{ $section->program?->code ?? '—' }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary" style="padding:6px 14px;font-size:.75rem"><i class="fa-solid fa-check"></i> Save</button>
                    </form>
                </div>
                <div class="form-field">
                    <label>Instructor ID</label>
                    <div>{{ $instructor->identifier ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Last Login</label>
                    <div>{{ $instructor->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-school"></i> Assigned Classes</h3>
            <span class="user-status active">{{ $instructor->classesInstructing->count() }} classes</span>
        </div>
        <div class="user-panel-body">
            @if($instructor->classesInstructing->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Course</th>
                                <th>Schedule</th>
                                <th>Period</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instructor->classesInstructing as $class)
                                <tr>
                                    <td><strong>{{ $class->code }}</strong></td>
                                    <td>{{ $class->course?->title ?? '—' }}</td>
                                    <td>{{ $class->schedule ?? '—' }}@if($class->room) · {{ $class->room }}@endif</td>
                                    <td>{{ $class->academicPeriod?->name ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $class->status ?? 'active' }}" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-school"
                    title="No classes assigned"
                    description="This instructor has not been assigned to any classes yet."
                />
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-chalkboard-user"></i> Assign Classes</h3>
            <span class="user-status active">Check classes to assign</span>
        </div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.instructors.assign-classes', $instructor) }}">
                @csrf
                @if($availableClasses->count() > 0)
                    <div class="user-table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:40px"></th>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Schedule</th>
                                    <th>Period</th>
                                    <th>Current Instructor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableClasses as $class)
                                    <tr>
                                        <td><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked($class->instructor_id === $instructor->id)></td>
                                        <td><strong>{{ $class->code }}</strong></td>
                                        <td>{{ $class->course?->title ?? '—' }}</td>
                                        <td>{{ $class->schedule ?? '—' }}@if($class->room) · {{ $class->room }}@endif</td>
                                        <td>{{ $class->academicPeriod?->name ?? '—' }}</td>
                                        <td>{{ $class->instructor?->full_name ?? 'Unassigned' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Assignments</button>
                    </div>
                @else
                    <x-user-empty-state
                        icon="fa-chalkboard-user"
                        title="No classes available"
                        description="Create classes first, then assign them here."
                    />
                @endif
            </form>
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.instructors.destroy', $instructor) }}" method="POST" onsubmit="return confirm('Deactivate this instructor?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-user-slash"></i> Deactivate</button>
        </form>
    </div>
</div>
@endsection

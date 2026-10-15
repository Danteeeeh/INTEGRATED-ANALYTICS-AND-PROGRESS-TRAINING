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
                    <label>Section Changes</label>
                    <div style="color:#64748b;font-size:.8rem;">Handled by Sync from AA — faculty section assignment is authoritative in AA.</div>
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
            <h3><i class="fa-solid fa-chalkboard-user"></i> Class Assignments</h3>
        </div>
        <div class="user-panel-body">
            <div style="padding:16px;display:flex;align-items:center;gap:10px;font-size:.85rem;color:#475569;flex-wrap:wrap;">
                <i class="fa-solid fa-circle-info" style="color:#0ea5e9"></i>
                <span>Class assignment comes from <strong>Sync from AA</strong> — faculty-to-course assignments are authoritative in AA.</span>
                <a href="{{ route('admin.aa-sync.index') }}" style="color:#0ea5e9;font-weight:700;text-decoration:underline;">Go to Sync from AA</a>
            </div>
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

@extends('layouts.registrar')
@section('title', 'Class Details')
@php $activeNav = 'classes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $class->code }} — {{ $class->course?->title }}"
        subtitle="Class details and roster."
        icon="fa-school"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $class->enrollments->count() }} enrolled</span>
            @if($class->academicPeriod)<span class="user-status">{{ $class->academicPeriod->name }}</span>@endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('registrar.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Class Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $class->course?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Instructor</label>
                    <div>{{ $class->instructor?->name ?? 'Unassigned' }}</div>
                </div>
                <div class="form-field">
                    <label>Schedule</label>
                    <div>{{ $class->schedule ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Capacity</label>
                    <div>{{ $class->enrollments->count() }} / {{ $class->max_students ?? '∞' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-users"></i> Roster</h3>
            <span class="user-status">{{ $class->enrollments->count() }} students</span>
        </div>
        <div class="user-panel-body">
            @if($class->enrollments->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Final Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($class->enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->student?->name ?? '—' }}</td>
                                    <td>{{ $enrollment->student?->email ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $enrollment->status }}" /></td>
                                    <td>{{ $enrollment->final_grade ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-users" title="No students enrolled" description="No students are enrolled in this class yet." />
            @endif
        </div>
    </div>
</div>
@endsection

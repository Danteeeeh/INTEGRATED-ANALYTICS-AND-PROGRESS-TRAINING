@extends('layouts.registrar')
@section('title', 'Student Record')
@php $activeNav = 'students'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $student->name }}"
        subtitle="Student record and enrollment summary."
        icon="fa-user-graduate"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $student->status }}" />
            <span class="user-status">{{ $student->enrollments->count() }} enrollments</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('registrar.students.edit', $student) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit Record</a>
            <a href="{{ route('registrar.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-address-card"></i> Student Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Email</label>
                    <div>{{ $student->email }}</div>
                </div>
                <div class="form-field">
                    <label>Student ID</label>
                    <div>{{ $student->identifier ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $student->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Joined</label>
                    <div>{{ $student->created_at?->format('M j, Y') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-user-plus"></i> Enrollments</h3>
            <span class="user-status">{{ $student->enrollments->count() }} total</span>
        </div>
        <div class="user-panel-body">
            @if($student->enrollments->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Final Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student->enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->class?->course?->title ?? '—' }}</td>
                                    <td>{{ $enrollment->class?->code ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $enrollment->status }}" /></td>
                                    <td>{{ $enrollment->final_grade ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-user-plus"
                    title="No enrollments"
                    description="This student has no enrollment records."
                />
            @endif
        </div>
    </div>
</div>
@endsection

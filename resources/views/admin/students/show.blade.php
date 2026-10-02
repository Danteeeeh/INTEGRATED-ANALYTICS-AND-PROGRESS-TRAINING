@extends('layouts.admin-sms')

@section('title', 'Student Details')
@php
    $activeNav = 'students';
    $pageTitle = 'Student Details';
    $pageIcon = '<i class="fa-solid fa-user-graduate"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate"></i>
            Student Details
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Student Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-user"></i> Personal Information</div>
        <div class="modal-row"><span>Name:</span><span>{{ $student->full_name }}</span></div>
        <div class="modal-row"><span>Email:</span><span>{{ $student->email }}</span></div>
        <div class="modal-row"><span>Phone:</span><span>{{ $student->phone_number ?? 'Not provided' }}</span></div>
        <div class="modal-row"><span>Address:</span><span>{{ $student->address ?? 'Not provided' }}</span></div>
        <div class="modal-row"><span>Department:</span><span>{{ $student->department->name ?? '—' }}</span></div>
        <div class="modal-row"><span>Program:</span><span>{{ $student->program->name ?? '—' }}</span></div>
        <div class="modal-row"><span>Section:</span><span>{{ $student->section->name ?? '—' }}@if($student->section?->code) ({{ $student->section->code }})@endif</span></div>
        <div class="modal-row"><span>Assign Section:</span><span>
            <form method="POST" action="{{ route('admin.students.assign-section', $student) }}" style="display:inline-flex;gap:8px;align-items:center">
                @csrf
                <select name="section_id" style="padding:6px 10px;border:1px solid var(--dash-line,#dce4f0);border-radius:8px;font-size:.78rem">
                    <option value="">No Section</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" @selected($student->section_id === $section->id)>{{ $section->name }} ({{ $section->code }}) — {{ $section->program?->code ?? '—' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-add" style="padding:6px 14px;font-size:.75rem"><i class="fa-solid fa-check"></i> Save</button>
            </form>
        </span></div>
        <div class="modal-row"><span>Status:</span><span>
            @if($student->status === 'active')
                <span class="badge-active">Active</span>
            @else
                <span class="badge-inactive">Inactive</span>
            @endif
        </span></div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Enrollments ({{ $student->enrollments->count() }})</h3>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @if($student->enrollments->count() > 0)
                    @foreach($student->enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->class->code ?? '-' }} - {{ $enrollment->class->name ?? '-' }}</td>
                            <td>{{ $enrollment->class->course->name ?? '-' }}</td>
                            <td>{{ $enrollment->class->instructor->full_name ?? '-' }}</td>
                            <td>{{ $enrollment->class->schedule ?? '—' }}@if($enrollment->class->room) · {{ $enrollment->class->room }}@endif</td>
                            <td>
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;
                                    {{ $enrollment->status === 'active' ? 'background: #dcfce7; color: #16a34a;' : 
                                       ($enrollment->status === 'completed' ? 'background: #dbeafe; color: #1e40af;' : 
                                       ($enrollment->status === 'dropped' ? 'background: #fee2e2; color: #dc2626;' : 'background: #f1f5f9; color: #64748b;')) }}>
                                    {{ ucfirst($enrollment->status) }}
                                </span>
                            </td>
                            <td>{{ $enrollment->final_grade ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="crud-card" style="margin: 16px 24px 0;">
        <div class="crud-header">
            <h3><i class="fa-solid fa-user-graduate"></i> Assign Classes</h3>
        </div>
        <form method="POST" action="{{ route('admin.students.assign-classes', $student) }}">
            @csrf
            <div style="padding:16px;">
                <p style="margin:0 0 12px;color:#888;font-size:.8rem;">Check the classes to assign this student to. Unchecking an active/pending class drops it.</p>
                <table class="crud-table">
                    <thead><tr><th style="width:40px"></th><th>Class</th><th>Course</th><th>Schedule</th><th>Period</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($availableClasses as $class)
                        @php($isAssigned = $student->enrollments->contains(fn ($e) => $e->class_id === $class->id && in_array($e->status, ['active', 'pending'])))
                        <tr>
                            <td><input type="checkbox" name="class_ids[]" value="{{ $class->id }}" @checked($isAssigned)></td>
                            <td>{{ $class->code }}</td>
                            <td>{{ $class->course?->code ?? '—' }} {{ $class->course?->title ?? '' }}</td>
                            <td>{{ $class->schedule ?? '—' }}@if($class->room) · {{ $class->room }}@endif</td>
                            <td>{{ $class->academicPeriod?->name ?? '—' }}</td>
                            <td>{{ ucfirst($class->status ?? '—') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align:center;padding:20px;color:#aaa;">No classes available yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px;">
                    <button type="submit" class="btn-add"><i class="fa-solid fa-check"></i> Save Assignments</button>
                </div>
            </div>
        </form>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="{{ route('admin.students.edit', $student) }}" class="btn-add" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-right: 8px;">
            <i class="fa-solid fa-pen-to-square"></i> Edit Student
        </a>
        <a href="{{ route('admin.students.index') }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Students
        </a>
    </div>
@endsection
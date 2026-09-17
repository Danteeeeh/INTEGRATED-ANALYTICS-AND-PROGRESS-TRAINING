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
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
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
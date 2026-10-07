@extends('layouts.admin-sms')

@section('title', 'Academic Period Details')
@php
    $activeNav = 'academic_periods';
    $pageTitle = 'Academic Period Details';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            {{ $academicPeriod->name }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Academic Period Information</h3>
        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Code:</span><span>{{ $academicPeriod->code }}</span></div>
        <div class="modal-row"><span>Dates:</span><span>{{ $academicPeriod->start_date->format('M d, Y') }} - {{ $academicPeriod->end_date->format('M d, Y') }}</span></div>
        <div class="modal-row"><span>Status:</span><span>
            <div style="display: flex; gap: 8px;">
                @if($academicPeriod->is_current)
                    <span class="badge-active">Current</span>
                @endif
                @if($academicPeriod->is_enrollment_open)
                    <span style="background: #dbeafe; color: #1e40af; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600;">Enrollment Open</span>
                @endif
            </div>
        </span></div>
        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Description</div>
        <div class="modal-row"><span></span><span>{{ $academicPeriod->description ?? 'No description' }}</span></div>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Classes ({{ $academicPeriod->classes->count() }})</h3>
            <a href="{{ route('admin.classes.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Class
            </a>
        </div>
        
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Instructor</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if($academicPeriod->classes->count() > 0)
                    @foreach($academicPeriod->classes as $class)
                        <tr>
                            <td>{{ $class->code }}</td>
                            <td>{{ $class->name }}</td>
                            <td>{{ $class->course->code }} - {{ $class->course->name }}</td>
                            <td>{{ $class->instructor->full_name }}</td>
                            <td class="actions-cell">
                                <a href="{{ route('admin.classes.show', $class) }}" class="btn-icon btn-view" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:#aaa;">
                            No classes found for this period. <a href="{{ route('admin.classes.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="{{ route('admin.academic_periods.index') }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Academic Periods
        </a>
    </div>
@endsection
@extends('layouts.student')

@section('title', 'Enrollment Details')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Enrollment Details';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enrollment Details
        </h2>
        <a href="{{ route('student.enrollments.index') }}" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Enrollments
        </a>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Enrollment Information</h3>
        </div>
        
        <div style="padding: 24px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Class</label>
                    <div style="font-size: 1.1rem; font-weight: 500;">
                        {{ $enrollment->class->code ?? '-' }} - {{ $enrollment->class->name ?? '-' }}
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Course</label>
                    <div style="font-size: 1.1rem; font-weight: 500;">
                        {{ $enrollment->class->course->name ?? '-' }}
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Status</label>
                    <div>
                        <span style="padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;
                            {{ ($enrollment->status === 'active' ? 'background: #dcfce7; color: #16a34a;' : 
                               ($enrollment->status === 'completed' ? 'background: #dbeafe; color: #1e40af;' : 
                               ($enrollment->status === 'dropped' ? 'background: #fee2e2; color: #dc2626;' : 
                               ($enrollment->status === 'pending' ? 'background: #fef3c7; color: #d97706;' : 'background: #f1f5f9; color: #64748b;')))) }}">
                            {{ ucfirst($enrollment->status) }}
                        </span>
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Final Grade</label>
                    <div style="font-size: 1.1rem; font-weight: 500;">
                        {{ $enrollment->final_grade ?? 'Not graded' }}
                    </div>
                </div>
                
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Enrolled Date</label>
                    <div style="font-size: 1.1rem; font-weight: 500;">
                        {{ $enrollment->enrolled_at->format('M d, Y') }}
                    </div>
                </div>
                
                @if($enrollment->completed_at)
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Completed Date</label>
                    <div style="font-size: 1.1rem; font-weight: 500;">
                        {{ $enrollment->completed_at->format('M d, Y') }}
                    </div>
                </div>
                @endif
            </div>
            
            @if($enrollment->notes)
            <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #64748b;">Notes</label>
                <div>{{ $enrollment->notes }}</div>
            </div>
            @endif
        </div>
    </div>
@endsection
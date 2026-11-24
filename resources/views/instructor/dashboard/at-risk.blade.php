@extends('layouts.instructor')

@section('title', 'At-risk Students')
@php
    $activeNav = 'dashboard';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-triangle-exclamation"></i>
            At-risk Students
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Active enrollments below {{ number_format($passingGrade, 0) }}% ({{ $enrollments->count() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            These students have a released LMS grade below the passing mark. The percentage shown is the class grade computed from released grade items.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Course</th>
                        <th style="text-align:center;">LMS Score</th>
                        <th>Enrollment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->student?->name ?? '—' }}</td>
                            <td>{{ $enrollment->class?->code ?? '—' }}</td>
                            <td>{{ $enrollment->class?->course?->title ?? '—' }}</td>
                            <td style="text-align:center;">
                                <span style="color:#f87171;font-weight:700;">
                                    {{ $enrollment->computed_percent !== null ? number_format((float) $enrollment->computed_percent, 1).'%' : '—' }}
                                </span>
                            </td>
                            <td>{{ ucfirst($enrollment->status) }}</td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('instructor.enrollments.show', $enrollment) }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i> View enrollment
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
                                No students are currently below the passing mark.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

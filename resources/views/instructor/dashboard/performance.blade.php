@extends('layouts.instructor')

@section('title', 'Class Performance')
@php
    $activeNav = 'dashboard';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-chart-simple"></i>
            Class Performance
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
            <a href="{{ route('instructor.reports.index') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-file-lines"></i> Reports
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Class performance ({{ count($classPerformance) }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            LMS assessment performance only. Official final grades remain with AA.
        </div>
        <div class="table-responsive">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Course</th>
                        <th class="text-center">Active Students</th>
                        <th class="text-center">Average LMS Grade</th>
                        <th class="text-center">Completion</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($classPerformance as $performance)
                        <tr>
                            <td>{{ $performance['class']->code }}</td>
                            <td>{{ $performance['class']->course?->title ?? '—' }}</td>
                            <td class="text-center">{{ $performance['active_students'] }}</td>
                            <td class="text-center">
                                @if($performance['graded_students'] > 0)
                                    {{ number_format((float) $performance['average_grade'], 1) }}%
                                    <div style="font-size:.72rem;color:#64748b;">
                                        {{ $performance['graded_students'] }} graded
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">{{ number_format((float) $performance['completion_rate'], 1) }}%</td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('instructor.classes.show', $performance['class']) }}" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-eye"></i> Open class
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:20px;color:#64748b;">
                                No classes are assigned to you yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

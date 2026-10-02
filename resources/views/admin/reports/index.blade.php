@extends('layouts.admin')
@section('title', 'Reports')
@php $activeNav = 'reports'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Reports"
        subtitle="Academic operations at a glance."
        icon="fa-chart-bar"
    />

    <div class="user-stat-grid">
        <x-user-stat-card label="Courses" value="{{ $stats['courses'] }}" icon="fa-book" />
        <x-user-stat-card label="Classes" value="{{ $stats['classes'] }}" icon="fa-school" />
        <x-user-stat-card label="Students" value="{{ $stats['students'] }}" icon="fa-user-graduate" />
        <x-user-stat-card label="Instructors" value="{{ $stats['instructors'] }}" icon="fa-chalkboard-user" />
    </div>
    <div class="user-stat-grid" style="margin-top:12px">
        <x-user-stat-card label="Enrollments" value="{{ $stats['enrollments'] }}" icon="fa-user-plus" />
        <x-user-stat-card label="Active" value="{{ $stats['active_enrollments'] }}" icon="fa-user-check" />
        <x-user-stat-card label="Completed" value="{{ $stats['completed_enrollments'] }}" icon="fa-flag-checkered" />
        <x-user-stat-card label="Grades recorded" value="{{ $stats['grades'] }}" icon="fa-marker" />
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h4><i class="fa-solid fa-file-lines"></i> Available Reports</h4>
        </div>
        <div class="user-panel-body">
            <div class="qa-grid" style="padding:0">
                <a class="qa-card" href="{{ route('admin.reports.enrollment') }}">
                    <i class="fa-solid fa-user-plus"></i>
                    <span><b>Enrollment report</b><small class="qa-sub">Active and completed enrollments</small></span>
                    <span class="qa-badge">{{ $stats['enrollments'] }}</span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.course-completion') }}">
                    <i class="fa-solid fa-certificate"></i>
                    <span><b>Course completion</b><small class="qa-sub">Completion progress by course</small></span>
                    <span class="qa-badge">{{ $stats['completions'] }}</span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.student-performance') }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span><b>Student performance</b><small class="qa-sub">Grades and academic results</small></span>
                    <span class="qa-badge">{{ $stats['students'] }}</span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.instructor-performance') }}">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span><b>Instructor performance</b><small class="qa-sub">Teaching activity overview</small></span>
                    <span class="qa-badge">{{ $stats['instructors'] }}</span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.attendance') }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span><b>Attendance report</b><small class="qa-sub">Attendance records and rates</small></span>
                    <span class="qa-badge">{{ $stats['attendance'] }}</span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.grade-distribution') }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span><b>Grade distribution</b><small class="qa-sub">Grade ranges and distribution</small></span>
                    <span class="qa-badge">{{ $stats['grades'] }}</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.qa-card {
    position: relative;
}
.qa-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    background: var(--primary, #4f46e5);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    line-height: 22px;
    text-align: center;
}
</style>
@endpush
@endsection

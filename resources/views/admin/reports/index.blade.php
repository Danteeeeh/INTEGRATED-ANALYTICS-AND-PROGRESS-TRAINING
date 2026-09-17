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
        <x-user-stat-card label="Courses" value="{{ $totalCourses }}" icon="fa-book" />
        <x-user-stat-card label="Classes" value="{{ $totalClasses }}" icon="fa-school" />
        <x-user-stat-card label="Students" value="{{ $totalStudents }}" icon="fa-user-graduate" />
        <x-user-stat-card label="Enrollments" value="{{ $totalEnrollments }}" icon="fa-user-plus" />
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
                </a>
                <a class="qa-card" href="{{ route('admin.reports.course-completion') }}">
                    <i class="fa-solid fa-certificate"></i>
                    <span><b>Course completion</b><small class="qa-sub">Completion progress by course</small></span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.student-performance') }}">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span><b>Student performance</b><small class="qa-sub">Grades and academic results</small></span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.instructor-performance') }}">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span><b>Instructor performance</b><small class="qa-sub">Teaching activity overview</small></span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.attendance') }}">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span><b>Attendance report</b><small class="qa-sub">Attendance records and rates</small></span>
                </a>
                <a class="qa-card" href="{{ route('admin.reports.grade-distribution') }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span><b>Grade distribution</b><small class="qa-sub">Grade ranges and distribution</small></span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

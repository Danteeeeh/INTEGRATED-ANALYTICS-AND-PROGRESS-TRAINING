@extends('layouts.aa')

@section('title', 'Course — View Only')
@php
    $activeNav = 'courses';
    $pageTitle = $course->title;
    $pageIcon = '<i class="fa-solid fa-book"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book"></i>
            {{ $course->title }} <span style="opacity:.6;font-weight:400;">({{ $course->code }})</span>
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Classes</h3>
            <a href="{{ route('aa.courses.index') }}" class="btn btn-secondary">Back to courses</a>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class Code</th>
                    <th>Instructor</th>
                    <th style="text-align:center;">Enrolled Students</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->code }}</td>
                        <td>{{ $class->instructor?->name ?? 'TBA' }}</td>
                        <td style="text-align:center;">{{ $class->enrollments_count }}</td>
                        <td style="text-align:center;"><span style="text-transform:capitalize;">{{ $class->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b;">Walang class pa ang course na ito.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

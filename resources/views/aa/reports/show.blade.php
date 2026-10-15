@extends('layouts.aa')

@section('title', 'LMS Assessment Report — View Only')
@php
    $activeNav = 'reports';
    $pageTitle = $course->title . ' — LMS Assessment Report';
    $pageIcon = '<i class="fa-solid fa-file-lines"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-lines"></i>
            {{ $course->title }} <span style="opacity:.6;font-weight:400;">({{ $course->code }}) — LMS Assessment Report</span>
        </h2>
    </div>
@endsection

@section('content')
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>LMS assessment scores at progress — <strong>hindi official final grade</strong>. Ang official grades ay hawak ng AA.</span>
        <a href="{{ route('aa.reports.export', ['course_id' => $course->id]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-export"></i> Export CSV</a>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Students ({{ count($rows) }})</h3>
            <a href="{{ route('aa.reports.index') }}" class="btn btn-secondary">Back to reports</a>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Class</th>
                    <th style="text-align:center;">Enrollment</th>
                    <th style="text-align:center;">LMS Score %</th>
                    <th style="text-align:center;">Completed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row['student_id'] }}</td>
                        <td>{{ $row['student_name'] }}</td>
                        <td>{{ $row['class_code'] }}</td>
                        <td style="text-align:center;"><span style="text-transform:capitalize;">{{ $row['status'] }}</span></td>
                        <td style="text-align:center;">{{ $row['score'] !== '' ? $row['score'].'%' : '—' }}</td>
                        <td style="text-align:center;">{{ $row['completed_at'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:#64748b;">Walang enrollment pa ang course na ito.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

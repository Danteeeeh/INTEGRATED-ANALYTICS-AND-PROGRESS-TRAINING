@extends('layouts.aa')

@section('title', 'LMS Assessment Reports (View-Only)')
@php
    $activeNav = 'reports';
    $pageTitle = 'LMS Assessment Reports — View Only';
    $pageIcon = '<i class="fa-solid fa-file-lines"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-lines"></i>
            LMS Assessment Reports — View Only
        </h2>
    </div>
@endsection

@section('content')
    {{-- Reference only: ang official final grades ay hawak sa AA. --}}
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>Ito ay <strong>LMS assessment reports</strong> (quiz/assignment scores at progress) — para sa reference, hindi official final grade. Ang official grades ay hawak ng <strong>AA system</strong>.</span>
        <a href="{{ route('aa.reports.export') }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-export"></i> Export Lahat (CSV)</a>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Reports by course</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Code</th>
                    <th style="text-align:center;">Active Students</th>
                    <th style="text-align:center;">Report</th>
                    <th style="text-align:center;">Export</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->code }}</td>
                        <td style="text-align:center;">{{ $course->enrollments_count }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('aa.reports.show', $course) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('aa.reports.export', ['course_id' => $course->id]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-export"></i> CSV</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">Walang course pa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@extends('layouts.instructor')

@section('title', 'LMS Reports')
@php
    $activeNav = 'reports';
    $pageTitle = 'LMS Reports';
    $pageIcon = '<i class="fa-solid fa-file-lines"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-file-lines"></i>
            LMS Reports
        </h2>
    </div>
@endsection

@section('content')
    {{-- Clear labelling (Section 5): LMS assessment reports, not official AA grades. --}}
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>Ang mga report na ito ay <strong>LMS assessment reports / grade-reference exports</strong> — hindi official final grade. Ang official final grades ay hawak ng <strong>AA</strong>. Hindi automatic na ini-import ng AA ang mga ito; i-upload lang kapag hiningi.</span>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Reports for my classes</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Course</th>
                    <th style="text-align:center;">Students</th>
                    <th style="text-align:center;">Exports</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>{{ $class->code }}</td>
                        <td>{{ $class->course?->title ?? '—' }}</td>
                        <td style="text-align:center;">{{ $class->enrollments_count }}</td>
                        <td style="text-align:center;white-space:nowrap;">
                            <a href="{{ route('instructor.dashboard.export', ['type' => 'students', 'format' => 'csv']) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-users"></i> Students</a>
                            <a href="{{ route('instructor.dashboard.export', ['type' => 'grades', 'format' => 'csv']) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-chart-line"></i> Grades</a>
                            <a href="{{ route('instructor.dashboard.export', ['type' => 'performance', 'format' => 'csv']) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-poll"></i> Performance</a>
                            <a href="{{ route('instructor.dashboard.export', ['type' => 'aa', 'format' => 'csv']) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-file-export"></i> For AA</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b;">Walang class pa. Ang classes ay galing sa AA sync.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:12px 16px;font-size:.78rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            Ang "For AA" export ay per-student, per-subject LMS scores (Student ID, Name, Course, Class, Status, LMS Score %, Letter, Completed At) — format na kayang i-reconcile ng AA. Ang exports ay para sa lahat ng classes mo; piliin ang columns na kailangan ng AA kapag nag-upload.
        </div>
    </div>
@endsection

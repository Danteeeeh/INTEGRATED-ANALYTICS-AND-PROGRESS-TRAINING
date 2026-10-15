@extends('layouts.aa')

@section('title', 'Student Progress (View-Only)')
@php
    $activeNav = 'progress';
    $pageTitle = 'LMS Student Progress — View Only';
    $pageIcon = '<i class="fa-solid fa-chart-line"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-chart-line"></i>
            LMS Student Progress — View Only
        </h2>
    </div>
@endsection

@section('content')
    <div style="margin-bottom:14px;padding:12px 16px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.35);border-radius:10px;font-size:.85rem;display:flex;align-items:center;gap:10px;">
        <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
        <span>Ito ay <strong>LMS activity progress</strong> (modules, lessons, quizzes, assignments) — hindi ito official grade. Ang final grades ay hawak pa rin sa AA system.</span>
    </div>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Average LMS progress per course</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Code</th>
                    <th style="text-align:center;">Active Students</th>
                    <th style="text-align:center;">Avg. LMS Progress</th>
                </tr>
            </thead>
            <tbody>
                @forelse($progressData as $row)
                    <tr>
                        <td>{{ $row['course']->title }}</td>
                        <td>{{ $row['course']->code }}</td>
                        <td style="text-align:center;">{{ $row['students'] }}</td>
                        <td style="text-align:center;min-width:180px;">
                            <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                                <div class="dash-progress" style="flex:1;max-width:140px;"><span style="width: {{ round($row['average']) }}%"></span></div>
                                <strong>{{ round($row['average']) }}%</strong>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b;">Walang active enrollment pa.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

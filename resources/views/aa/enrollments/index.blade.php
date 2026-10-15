@extends('layouts.aa')

@section('title', 'Enrollments (View-Only)')
@php
    $activeNav = 'enrollments';
    $pageTitle = 'Enrollments — View Only';
    $pageIcon = '<i class="fa-solid fa-user-plus"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-plus"></i>
            Enrollments — View Only
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Enrollments (LMS copy, synced from AA)</h3>
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('aa.enrollments.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search student name or email..." aria-label="Search enrollments">
            <select name="status" aria-label="Filter status">
                <option value="">All statuses</option>
                @foreach(['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('aa.enrollments.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Course</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;">Enrolled</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->student?->name ?? 'N/A' }}</td>
                        <td>{{ $enrollment->class?->code ?? '—' }}</td>
                        <td>{{ $enrollment->class?->course?->title ?? '—' }}</td>
                        <td style="text-align:center;"><span style="text-transform:capitalize;">{{ $enrollment->status }}</span></td>
                        <td style="text-align:center;">{{ $enrollment->enrolled_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">Walang enrollment pa.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:14px;">
            {{ $enrollments->links() }}
        </div>
    </div>
@endsection

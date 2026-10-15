@extends('layouts.aa')

@section('title', 'Students (View-Only)')
@php
    $activeNav = 'students';
    $pageTitle = 'Students — View Only';
    $pageIcon = '<i class="fa-solid fa-user-graduate"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate"></i>
            Students — View Only
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All students (synced from AA)</h3>
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('aa.students.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search name, email, or student ID..." aria-label="Search students">
            <select name="status" aria-label="Filter status">
                <option value="">All statuses</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('aa.students.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Department</th>
                    <th>Program</th>
                    <th>Section</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->identifier ?? '—' }}</td>
                        <td>{{ $student->department?->name ?? '—' }}</td>
                        <td>{{ $student->program?->name ?? '—' }}</td>
                        <td>{{ $student->section?->name ?? '—' }}</td>
                        <td style="text-align:center;"><span style="text-transform:capitalize;">{{ $student->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:#64748b;">Walang student pa.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:14px;">
            {{ $students->links() }}
        </div>
    </div>
@endsection

@extends('layouts.aa')

@section('title', 'Courses (View-Only)')
@php
    $activeNav = 'courses';
    $pageTitle = 'Courses — View Only';
    $pageIcon = '<i class="fa-solid fa-book"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book"></i>
            Courses — View Only
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All courses</h3>
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('aa.courses.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search course title or code..." aria-label="Search courses">
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('aa.courses.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Code</th>
                    <th style="text-align:center;">Classes</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                    <tr>
                        <td><a href="{{ route('aa.courses.show', $course) }}" style="font-weight:600;">{{ $course->title }}</a></td>
                        <td>{{ $course->code }}</td>
                        <td style="text-align:center;">{{ $course->classes_count }}</td>
                        <td style="text-align:center;"><span style="text-transform:capitalize;">{{ $course->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:20px;color:#64748b;">Walang course pa.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:14px;">
            {{ $courses->links() }}
        </div>
    </div>
@endsection

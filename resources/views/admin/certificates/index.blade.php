@extends('layouts.admin')

@section('title', 'Certificates')
@php $activeNav = 'certificates'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Certificates"
        subtitle="Issue, verify, and manage completion certificates."
        icon="fa-certificate"
    >
        <x-slot name="actions">
            @can('certificates.create')
                <a href="{{ route('admin.certificates.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Issue Certificate</a>
            @endcan
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.certificates.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search certificates..." aria-label="Search certificates">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['issued' => 'Issued', 'revoked' => 'Revoked'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.certificates.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($certificates->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Certificate</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Issued</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($certificates as $certificate)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $certificate->certificate_number }}</div>
                                        <div class="user-email">{{ $certificate->template_name ?? 'Standard' }}</div>
                                    </td>
                                    <td>{{ $certificate->student?->name ?? $certificate->student_name_display ?? '—' }}</td>
                                    <td>{{ $certificate->course_name_display ?? $certificate->course?->title ?? '—' }}</td>
                                    <td>{{ $certificate->issued_at?->format('M j, Y') }}</td>
                                    <td><x-user-status-badge status="{{ $certificate->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.certificates.show', $certificate) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.certificates.download', $certificate) }}" title="Download"><i class="fa-solid fa-download"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-certificate"
                    title="No certificates found"
                    description="Issue certificates to students who completed a class."
                />
            @endif

            @if($certificates->hasPages())
                <div class="pagination">{{ $certificates->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Virtual Classes')
@php($activeNav = 'courses')

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Virtual Classes"
        subtitle="Manage video meeting rooms across courses and classes."
        icon="fa-video"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.virtual_classes.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> New Virtual Class
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form method="GET" action="{{ route('admin.virtual_classes.index') }}" class="user-toolbar" data-filter-form>
            <select name="course_id" class="form-control" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                        {{ $course->code }} - {{ $course->title }}
                    </option>
                @endforeach
            </select>
            <select name="class_id" class="form-control" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                        {{ $class->code }} ({{ $class->course->code ?? 'N/A' }})
                    </option>
                @endforeach
            </select>
            <select name="instructor_id" class="form-control" aria-label="Filter instructor">
                <option value="">All Instructors</option>
                @foreach($instructors as $instructor)
                    <option value="{{ $instructor->id }}" {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                        {{ $instructor->name }}
                    </option>
                @endforeach
            </select>
            <select name="meeting_provider" class="form-control" aria-label="Filter provider">
                <option value="">All Providers</option>
                <option value="zoom" {{ request('meeting_provider') == 'zoom' ? 'selected' : '' }}>Zoom</option>
                <option value="google_meet" {{ request('meeting_provider') == 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                <option value="microsoft_teams" {{ request('meeting_provider') == 'microsoft_teams' ? 'selected' : '' }}>Microsoft Teams</option>
                <option value="other" {{ request('meeting_provider') == 'other' ? 'selected' : '' }}>Other</option>
            </select>
            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">All Statuses</option>
                <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Title, desc, ID..." class="form-control" aria-label="Search virtual classes">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.virtual_classes.index') }}"><i class="fa-solid fa-xmark"></i> Reset</a>
        </form>

        @if($virtualClasses->count() > 0)
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Course</th>
                            <th>Class</th>
                            <th>Instructor</th>
                            <th>Date/Time</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($virtualClasses as $vc)
                            <tr>
                                <td>
                                    <div style="font-weight:600;">{{ $vc->title }}</div>
                                    @if($vc->meeting_id)
                                        <div class="table-sub">ID: {{ $vc->meeting_id }}</div>
                                    @endif
                                </td>
                                <td>{{ $vc->course->code ?? '-' }} - {{ ($vc->course->title ?? '-') }}</td>
                                <td>{{ $vc->class->code ?? '-' }}</td>
                                <td>{{ $vc->instructor->name ?? '-' }}</td>
                                <td>
                                    <div style="font-weight:500;">{{ $vc->meeting_date->format('M d, Y') }}</div>
                                    <div class="table-sub">
                                        {{ \Carbon\Carbon::parse($vc->start_time)->format('g:i A') }}
                                        -
                                        {{ \Carbon\Carbon::parse($vc->end_time)->format('g:i A') }}
                                    </div>
                                </td>
                                <td>
                                    <x-user-status-badge :status="Str::slug($vc->meeting_provider ?? 'other')" :label="ucfirst($vc->meeting_provider ?? 'Other')" />
                                </td>
                                <td>
                                    <x-user-status-badge :status="$vc->status" />
                                </td>
                                <td class="actions-cell">
                                    <a href="{{ route('admin.virtual_classes.show', $vc) }}" class="btn-icon btn-view" title="View">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.virtual_classes.edit', $vc) }}" class="btn-icon btn-edit" title="Edit">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if($vc->meeting_url)
                                        <a href="{{ $vc->meeting_url }}" target="_blank" class="btn-icon btn-view" title="Open Meeting">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.virtual_classes.destroy', $vc) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this virtual class?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-delete" title="Delete">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-user-empty-state
                icon="fa-video"
                title="No virtual classes found"
                description="Create a virtual class to schedule online meetings."
            />
        @endif

        @if($virtualClasses->hasPages())
            <div class="pagination">{{ $virtualClasses->appends(request()->query())->links() }}</div>
        @endif
    </div>
</div>
@endsection
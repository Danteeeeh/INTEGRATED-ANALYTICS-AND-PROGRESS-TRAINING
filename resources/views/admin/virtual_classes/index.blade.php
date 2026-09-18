@extends('layouts.admin-sms')

@section('title', 'Virtual Classes')
@php
    $activeNav = 'courses';
    $pageTitle = 'Virtual Classes';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Virtual Classes
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Virtual Classes</h3>
            <a href="{{ route('admin.virtual_classes.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Virtual Class
            </a>
        </div>

        <div style="padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #fafafa;">
            <form method="GET" action="{{ route('admin.virtual_classes.index') }}" data-filter-form style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Course</label>
                    <select name="course_id">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Class</label>
                    <select name="class_id">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->code }} ({{ $class->course->code ?? 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Instructor</label>
                    <select name="instructor_id">
                        <option value="">All Instructors</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" {{ request('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                {{ $instructor->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Provider</label>
                    <select name="meeting_provider">
                        <option value="">All Providers</option>
                        <option value="zoom" {{ request('meeting_provider') == 'zoom' ? 'selected' : '' }}>Zoom</option>
                        <option value="google_meet" {{ request('meeting_provider') == 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                        <option value="microsoft_teams" {{ request('meeting_provider') == 'microsoft_teams' ? 'selected' : '' }}>Microsoft Teams</option>
                        <option value="other" {{ request('meeting_provider') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, desc, ID...">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.virtual_classes.index') }}" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <table class="crud-table">
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
                @forelse($virtualClasses as $vc)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #111827;">{{ $vc->title }}</div>
                            @if($vc->meeting_id)
                                <div style="font-size: 0.75rem; color: #6b7280;">ID: {{ $vc->meeting_id }}</div>
                            @endif
                        </td>
                        <td>{{ $vc->course->code ?? '-' }} - {{ ($vc->course->title ?? '-') }}</td>
                        <td>{{ $vc->class->code ?? '-' }}</td>
                        <td>{{ $vc->instructor->name ?? '-' }}</td>
                        <td>
                            <div style="font-weight: 500;">{{ $vc->meeting_date->format('M d, Y') }}</div>
                            <div style="font-size: 0.78rem; color: #6b7280;">
                                {{ \Carbon\Carbon::parse($vc->start_time)->format('g:i A') }}
                                -
                                {{ \Carbon\Carbon::parse($vc->end_time)->format('g:i A') }}
                            </div>
                        </td>
                        <td>
                            @php
                                $providerBadgeStyle = match($vc->meeting_provider) {
                                    'zoom' => 'background: #e0e7ff; color: #4338ca;',
                                    'google_meet' => 'background: #fee2e2; color: #dc2626;',
                                    'microsoft_teams' => 'background: #dbeafe; color: #1e40af;',
                                    'other' => 'background: #f3f4f6; color: #374151;',
                                    default => 'background: #f3f4f6; color: #6b7280;',
                                };
                                $providerLabel = match($vc->meeting_provider) {
                                    'zoom' => 'Zoom',
                                    'google_meet' => 'Google Meet',
                                    'microsoft_teams' => 'Teams',
                                    'other' => 'Other',
                                    default => ucfirst($vc->meeting_provider),
                                };
                            @endphp
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; {{ $providerBadgeStyle }}">
                                {{ $providerLabel }}
                            </span>
                        </td>
                        <td>
                            @php
                                $statusBadgeStyle = match($vc->status) {
                                    'scheduled' => 'background: #dcfce7; color: #16a34a;',
                                    'ongoing' => 'background: #fef3c7; color: #d97706;',
                                    'completed' => 'background: #dbeafe; color: #1e40af;',
                                    'cancelled' => 'background: #fee2e2; color: #dc2626;',
                                    default => 'background: #f1f5f9; color: #64748b;',
                                };
                            @endphp
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; {{ $statusBadgeStyle }}">
                                {{ ucfirst($vc->status) }}
                            </span>
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('admin.virtual_classes.show', $vc) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.virtual_classes.edit', $vc) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            @if($vc->meeting_url)
                                <a href="{{ $vc->meeting_url }}" target="_blank" class="btn-icon btn-view" title="Open Meeting" style="background: #dbeafe; color: #1e40af;">
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
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:#aaa;">
                            No virtual classes found. <a href="{{ route('admin.virtual_classes.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($virtualClasses->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                {{ $virtualClasses->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

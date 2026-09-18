@extends('layouts.admin')

@section('title', 'Announcements')
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Announcements"
        subtitle="Create and manage announcements across institution, courses, and classes."
        icon="fa-bullhorn"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Announcement</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.announcements.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search announcements..." aria-label="Search announcements">
            <select class="form-control" name="audience_type" aria-label="Filter audience">
                <option value="">All Audiences</option>
                @foreach(['institution' => 'Institution', 'course' => 'Course', 'class' => 'Class', 'role' => 'Role', 'users' => 'Users'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('audience_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['draft' => 'Draft', 'scheduled' => 'Scheduled', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.announcements.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($announcements->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($announcements as $announcement)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name">
                                                @if($announcement->is_pinned)<i class="fa-solid fa-thumbtack" style="color:var(--user-accent)" aria-label="Pinned"></i>@endif
                                                {{ $announcement->title }}
                                            </div>
                                            <div class="user-email">{{ Str::limit($announcement->body, 60) }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="user-status">{{ ucfirst($announcement->audience_type) }}</span>
                                        @if($announcement->course)
                                            <div class="user-email">{{ $announcement->course->title }}</div>
                                        @elseif($announcement->class)
                                            <div class="user-email">{{ $announcement->class->code }}</div>
                                        @elseif($announcement->targetRole)
                                            <div class="user-email">{{ $announcement->targetRole->name }}</div>
                                        @endif
                                    </td>
                                    <td><x-user-status-badge status="{{ $announcement->status }}" /></td>
                                    <td>
                                        <div>{{ $announcement->created_at?->format('M j, Y') }}</div>
                                        <div class="user-email">{{ $announcement->creator?->name ?? '—' }}</div>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            @if($announcement->status === 'published')
                                                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}" onsubmit="return confirm('Unpublish this announcement?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-icon" title="Unpublish"><i class="fa-solid fa-eye-slash"></i></button>
                                                </form>
                                            @elseif(in_array($announcement->status, ['draft', 'scheduled'], true))
                                                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-icon" title="Publish"><i class="fa-solid fa-paper-plane"></i></button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.announcements.pin', $announcement) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-icon" title="{{ $announcement->is_pinned ? 'Unpin' : 'Pin' }}">
                                                    <i class="fa-solid {{ $announcement->is_pinned ? 'fa-thumbtack' : 'fa-regular fa-thumbtack' }}"></i>
                                                </button>
                                            </form>
                                            <a class="btn btn-icon" href="{{ route('admin.announcements.show', $announcement) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.announcements.edit', $announcement) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-bullhorn"
                    title="No announcements found"
                    description="Create an announcement to reach students, classes, or the whole institution."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.announcements.create') }}"><i class="fa-solid fa-plus"></i> New Announcement</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($announcements->hasPages())
                <div class="pagination">{{ $announcements->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

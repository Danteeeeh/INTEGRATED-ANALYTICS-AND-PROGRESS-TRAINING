@extends('layouts.admin')

@section('title', 'Notifications')
@php $activeNav = 'notifications'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Notifications"
        subtitle="Review system notifications sent to users across the platform."
        icon="fa-bell"
    >
        <x-slot name="meta">
            <span class="user-status active">{{ $unreadCount }} unread</span>
            <span class="user-status">{{ $totalCount }} total</span>
        </x-slot>
        <x-slot name="actions">
            @can('notifications.preferences')
                <a href="{{ route('admin.notifications.preferences') }}" class="btn btn-secondary"><i class="fa-solid fa-sliders"></i> Preferences</a>
            @endcan
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.notifications.index') }}">
            <select class="form-control" name="user_id" aria-label="Filter user">
                <option value="">All Users</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
            <select class="form-control" name="read_status" aria-label="Filter read state">
                <option value="">All States</option>
                <option value="read" @selected(request('read_status') === 'read')>Read</option>
                <option value="unread" @selected(request('read_status') === 'unread')>Unread</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.notifications.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($notifications->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Notification</th>
                                <th>User</th>
                                <th>Channel</th>
                                <th>State</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $notification->title }}</div>
                                        <div class="user-email">{{ Str::limit($notification->body ?? '', 60) }}</div>
                                    </td>
                                    <td>{{ $notification->user?->name ?? '—' }}</td>
                                    <td><span class="user-status">{{ $notification->channel ?? 'system' }}</span></td>
                                    <td>
                                        @if($notification->is_read || $notification->read_at)
                                            <x-user-status-badge status="active" label="Read" />
                                        @else
                                            <x-user-status-badge status="pending" label="Unread" />
                                        @endif
                                    </td>
                                    <td>{{ $notification->created_at?->format('M j, Y g:i A') }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <form method="POST" action="{{ route('admin.notifications.mark-read', $notification) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-icon" title="Toggle read">
                                                    <i class="fa-solid {{ ($notification->is_read || $notification->read_at) ? 'fa-envelope-open' : 'fa-envelope' }}"></i>
                                                </button>
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
                    icon="fa-bell"
                    title="No notifications found"
                    description="System notifications sent to users will appear here."
                />
            @endif

            @if($notifications->hasPages())
                <div class="pagination">{{ $notifications->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', $announcement->title)
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $announcement->title }}"
        subtitle="Announcement details and delivery status."
        icon="fa-bullhorn"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $announcement->status }}" />
            @if($announcement->is_pinned)<span class="user-status active">Pinned</span>@endif
        </x-slot>
        <x-slot name="actions">
            @if($announcement->status === 'published')
                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-eye-slash"></i> Unpublish</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.announcements.publish', $announcement) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                </form>
            @endif
            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Announcement</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Audience</label>
                    <div>
                        <x-user-status-badge status="{{ $announcement->audience_type }}" />
                        @if($announcement->course)
                            <div>{{ $announcement->course->title }}</div>
                        @elseif($announcement->class)
                            <div>{{ $announcement->class->code }}</div>
                        @elseif($announcement->targetRole)
                            <div>{{ $announcement->targetRole->name }}</div>
                        @endif
                    </div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $announcement->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $announcement->creator?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Scheduled</label>
                    <div>{{ $announcement->publish_at?->format('M j, Y g:i A') ?? 'Immediately' }}</div>
                </div>
                <div class="form-field full">
                    <label>Body</label>
                    <div style="white-space:pre-line">{{ $announcement->body }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($announcement->views->count() > 0)
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-eye"></i> Read By</h3>
                <span class="user-status active">{{ $announcement->views->count() }} views</span>
            </div>
            <div class="user-panel-body">
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Viewed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($announcement->views as $view)
                                <tr>
                                    <td>{{ $view->user?->name ?? '—' }}</td>
                                    <td>{{ $view->user?->role?->name ?? '—' }}</td>
                                    <td>{{ $view->viewed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="user-actions">
        <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

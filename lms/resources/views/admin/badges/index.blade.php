@extends('layouts.admin')

@section('title', 'Badges')
@php $activeNav = 'badges'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Badges"
        subtitle="Define achievement badges and award them to students."
        icon="fa-medal"
    >
        <x-slot name="actions">
            @can('badges.create')
                <a href="{{ route('admin.badges.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Badge</a>
            @endcan
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.badges.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search badges..." aria-label="Search badges">
            <select class="form-control" name="badge_type" aria-label="Filter badge type">
                <option value="">All Types</option>
                @foreach(['course' => 'Course', 'competency' => 'Competency', 'achievement' => 'Achievement', 'participation' => 'Participation'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('badge_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.badges.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($badges->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Badge</th>
                                <th>Type</th>
                                <th>Context</th>
                                <th>Issued</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($badges as $badge)
                                <tr>
                                    <td>
                                        <div class="user-name">
                                            @if($badge->icon)<i class="fa-solid {{ $badge->icon }}"></i>@endif
                                            {{ $badge->name }}
                                        </div>
                                        <div class="user-email">{{ Str::limit($badge->description ?? '', 50) }}</div>
                                    </td>
                                    <td><span class="user-status">{{ ucfirst($badge->badge_type) }}</span></td>
                                    <td>
                                        @if($badge->course)
                                            {{ $badge->course->code }}
                                        @elseif($badge->class)
                                            {{ $badge->class->code }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $badge->issued_count ?? 0 }}</td>
                                    <td><x-user-status-badge status="{{ $badge->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.badges.show', $badge) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.badges.edit', $badge) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            @can('delete', $badge)
                                                <form method="POST" action="{{ route('admin.badges.destroy', $badge) }}" onsubmit="return confirm('Delete this badge?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-medal"
                    title="No badges found"
                    description="Create a badge to recognize student achievements."
                />
            @endif

            @if($badges->hasPages())
                <div class="pagination">{{ $badges->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

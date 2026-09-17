@extends('layouts.admin')

@section('title', 'Instructors')
@php $activeNav = 'instructors'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Instructors"
        subtitle="Manage instructor accounts, teaching assignments, and CSV import/export."
        icon="fa-chalkboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.instructors.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Instructor</a>
            <a href="{{ route('admin.instructors.export', request()->query()) }}" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Export</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.instructors.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search instructors..." aria-label="Search instructors">
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.instructors.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($instructors->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Status</th>
                                <th>Classes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($instructors as $instructor)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($instructor->first_name, 0, 1)) }}</div>
                                            <div>
                                                <div class="user-name">{{ $instructor->full_name }}</div>
                                                <div class="user-email">Teaching account</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $instructor->email }}</td>
                                    <td>{{ $instructor->identifier ?: '—' }}</td>
                                    <td><x-user-status-badge status="{{ $instructor->status }}" /></td>
                                    <td>{{ $instructor->classesInstructing()->count() }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.instructors.show', $instructor) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.instructors.edit', $instructor) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.instructors.destroy', $instructor) }}" onsubmit="return confirm('Deactivate this instructor?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Deactivate"><i class="fa-solid fa-user-slash"></i></button>
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
                    icon="fa-chalkboard-user"
                    title="No instructors found"
                    description="Add an instructor account to assign classes and manage teaching."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.instructors.create') }}"><i class="fa-solid fa-plus"></i> Add Instructor</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($instructors->hasPages())
                <div class="pagination">{{ $instructors->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

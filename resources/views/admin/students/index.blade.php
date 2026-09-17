@extends('layouts.admin')

@section('title', 'Students')
@php $activeNav = 'students'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Students"
        subtitle="Manage student accounts, enrollment status, and CSV import/export."
        icon="fa-user-graduate"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Student</a>
            <a href="{{ route('admin.students.export', request()->query()) }}" class="btn btn-secondary"><i class="fa-solid fa-download"></i> Export</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form method="GET" action="{{ route('admin.students.index') }}" class="user-toolbar">
            <input type="search" name="search" placeholder="Search students..." value="{{ request('search') }}" class="form-control" aria-label="Search students">
            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended', 'pending' => 'Pending'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($students->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Status</th>
                                <th>Enrollments</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($student->first_name, 0, 1)) }}</div>
                                            <div>
                                                <div class="user-name">{{ $student->full_name }}</div>
                                                <div class="user-email">Student account</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->identifier ?: '—' }}</td>
                                    <td><x-user-status-badge status="{{ $student->status }}" /></td>
                                    <td>{{ $student->enrollments->count() }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" onsubmit="return confirm('Delete this student?')">
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
                    icon="fa-user-graduate"
                    title="No students found"
                    description="Add a student account to get started."
                >
                    <x-slot name="action">
                        <a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Student</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($students->hasPages())
                <div class="pagination">{{ $students->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

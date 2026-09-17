@extends('layouts.registrar')
@section('title', 'Students')
@php $activeNav = 'students'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Students"
        subtitle="Manage student records and accounts."
        icon="fa-user-graduate"
    >
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('registrar.students.create') }}"><i class="fa-solid fa-plus"></i> Add Student</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('registrar.students.index') }}">
            <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search students..." aria-label="Search students">
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending', 'suspended' => 'Suspended'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('registrar.students.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($students->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($student->first_name ?? 'U', 0, 1)) }}</div>
                                            <div class="user-name">{{ $student->full_name }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->identifier ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $student->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('registrar.students.show', $student) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('registrar.students.edit', $student) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
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
                    description="Add a student record to get started."
                />
            @endif

            @if($students->hasPages())
                <div class="pagination">{{ $students->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Departments')
@php($activeNav = 'departments')
@section('content')
<div class="user-page">
    <x-user-page-header
        title="Departments"
        subtitle="Academic departments of the school."
        icon="fa-building-columns"
    >
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('admin.departments.create') }}"><i class="fa-solid fa-plus"></i> Add Department</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            @if(session('status'))
                <div class="message success" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
            @endif
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Programs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $department)
                            <tr>
                                <td><strong>{{ $department->code }}</strong></td>
                                <td><a href="{{ route('admin.departments.show', $department) }}">{{ $department->name }}</a></td>
                                <td style="color:var(--bcp-muted);font-size:.78rem">{{ Str::limit($department->description ?? '—', 60) }}</td>
                                <td><span class="badge badge-active">{{ $department->programs_count }}</span></td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('admin.departments.edit', $department) }}" class="btn btn-icon btn-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Delete this department?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="search-no-results">No departments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($departments->hasPages())
                <div class="pagination">{{ $departments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

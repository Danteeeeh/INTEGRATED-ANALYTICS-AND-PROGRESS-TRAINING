@extends('layouts.admin')
@section('title', 'Programs')
@php($activeNav = 'programs')
@section('content')
<div class="user-page">
    <x-user-page-header title="Programs" subtitle="Academic programs under each department." icon="fa-book-open">
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('admin.programs.create') }}"><i class="fa-solid fa-plus"></i> Add Program</a>
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
                        <tr><th>Code</th><th>Name</th><th>Department</th><th>Sections</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td><strong>{{ $program->code }}</strong></td>
                                <td><a href="{{ route('admin.programs.show', $program) }}">{{ $program->name }}</a></td>
                                <td>{{ $program->department?->name ?? '—' }}</td>
                                <td><span class="badge badge-active">{{ $program->sections_count }}</span></td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-icon btn-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('admin.programs.destroy', $program) }}" onsubmit="return confirm('Delete this program?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="search-no-results">No programs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($programs->hasPages())
                <div class="pagination">{{ $programs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Sections')
@php($activeNav = 'sections')
@section('content')
<div class="user-page">
    <x-user-page-header title="Sections" subtitle="Class sections grouped by program." icon="fa-users-rectangle">
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('admin.sections.create') }}"><i class="fa-solid fa-plus"></i> Add Section</a>
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
                        <tr><th>Code</th><th>Name</th><th>Program</th><th>Department</th><th>Period</th><th>Classes</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td><strong>{{ $section->code }}</strong></td>
                                <td><a href="{{ route('admin.sections.show', $section) }}">{{ $section->name }}</a></td>
                                <td>{{ $section->program?->code ?? '—' }}</td>
                                <td>{{ $section->program?->department?->name ?? '—' }}</td>
                                <td>{{ $section->academicPeriod?->name ?? '—' }}</td>
                                <td><span class="badge badge-active">{{ $section->classes_count }}</span></td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('admin.sections.edit', $section) }}" class="btn btn-icon btn-secondary" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" onsubmit="return confirm('Delete this section?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="search-no-results">No sections yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sections->hasPages())
                <div class="pagination">{{ $sections->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

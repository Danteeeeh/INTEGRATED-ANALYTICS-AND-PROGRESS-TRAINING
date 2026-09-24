@extends('layouts.admin')
@section('title', $department->name)
@php($activeNav = 'departments')
@section('content')
<div class="user-page">
    <x-user-page-header title="{{ $department->name }}" subtitle="Department — {{ $department->code }}" icon="fa-building-columns">
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('admin.departments.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="btn btn-primary" href="{{ route('admin.departments.edit', $department) }}"><i class="fa-solid fa-pen"></i> Edit</a>
        </x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <p style="color:var(--bcp-muted)">{{ $department->description ?? 'No description.' }}</p>
            <div class="crud-card" style="margin-top:16px">
                <div class="crud-header"><h3><i class="fa-solid fa-book-open"></i> Programs ({{ $department->programs->count() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Code</th><th>Name</th><th>Sections</th></tr></thead>
                    <tbody>
                        @forelse($department->programs as $program)
                            <tr>
                                <td><strong>{{ $program->code }}</strong></td>
                                <td><a href="{{ route('admin.programs.show', $program) }}">{{ $program->name }}</a></td>
                                <td>{{ $program->sections->count() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="search-no-results">No programs in this department.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

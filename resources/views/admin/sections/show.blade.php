@extends('layouts.admin')
@section('title', $section->name)
@php($activeNav = 'sections')
@section('content')
<div class="user-page">
    <x-user-page-header title="{{ $section->name }}" subtitle="{{ $section->code }} · {{ $section->program?->name ?? 'No program' }}" icon="fa-users-rectangle">
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('admin.sections.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="btn btn-primary" href="{{ route('admin.sections.edit', $section) }}"><i class="fa-solid fa-pen"></i> Edit</a>
        </x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <div class="crud-card">
                <div class="crud-header"><h3><i class="fa-solid fa-school"></i> Classes ({{ $section->classes->count() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Code</th><th>Course</th><th>Instructor</th><th>Schedule</th></tr></thead>
                    <tbody>
                        @forelse($section->classes as $class)
                            <tr>
                                <td><strong>{{ $class->code }}</strong></td>
                                <td>{{ $class->course?->title ?? '—' }}</td>
                                <td>{{ $class->instructor?->full_name ?? '—' }}</td>
                                <td>{{ $class->schedule ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="search-no-results">No classes in this section.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

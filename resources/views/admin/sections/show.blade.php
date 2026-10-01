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

    <div class="user-stat-grid">
        <x-user-stat-card label="Students" value="{{ $students->total() }}" icon="fa-user-graduate" footer="in this section" />
        <x-user-stat-card label="Classes" value="{{ $section->classes->count() }}" icon="fa-school" footer="section classes" />
        <x-user-stat-card label="Max" value="{{ $section->max_students ?? '—' }}" icon="fa-users" footer="section capacity" />
        <x-user-stat-card label="Program" value="{{ $section->program?->code ?? '—' }}" icon="fa-book-open" footer="program code" />
    </div>

    <div class="user-panel">
        <div class="user-panel-body">
            <div class="crud-card">
                <div class="crud-header"><h3><i class="fa-solid fa-school"></i> Classes ({{ $section->classes->count() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Code</th><th>Course</th><th>Instructor</th><th>Schedule</th><th>Period</th></tr></thead>
                    <tbody>
                        @forelse($section->classes as $class)
                            <tr>
                                <td><strong>{{ $class->code }}</strong></td>
                                <td>{{ $class->course?->title ?? '—' }}</td>
                                <td>{{ $class->instructor?->full_name ?? '—' }}</td>
                                <td>{{ $class->schedule ?? '—' }}</td>
                                <td>{{ $class->academicPeriod?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="search-no-results">No classes in this section.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="crud-card" style="margin-top:16px">
                <div class="crud-header"><h3><i class="fa-solid fa-user-graduate"></i> Students ({{ $students->total() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Identifier</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><a href="{{ route('admin.students.show', $student) }}"><strong>{{ $student->full_name }}</strong></a></td>
                                <td>{{ $student->email }}</td>
                                <td>{{ $student->identifier ?? '—' }}</td>
                                <td><x-user-status-badge status="{{ $student->status }}" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="search-no-results">No students assigned to this section yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($students->hasPages())
                    <div class="pagination">{{ $students->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

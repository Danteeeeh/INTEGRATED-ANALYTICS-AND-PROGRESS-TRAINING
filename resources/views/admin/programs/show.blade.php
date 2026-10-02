@extends('layouts.admin')
@section('title', $program->name)
@php($activeNav = 'programs')
@section('content')
<div class="user-page">
    <x-user-page-header title="{{ $program->name }}" subtitle="{{ $program->code }} · {{ $program->department?->name ?? 'No department' }}" icon="fa-book-open">
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('admin.programs.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a class="btn btn-primary" href="{{ route('admin.programs.edit', $program) }}"><i class="fa-solid fa-pen"></i> Edit</a>
        </x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <p style="color:var(--bcp-muted)">{{ $program->description ?? 'No description.' }}</p>
            <div class="crud-card" style="margin-top:16px">
                <div class="crud-header"><h3><i class="fa-solid fa-users"></i> Sections ({{ $program->sections->count() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Code</th><th>Name</th><th>Academic Period</th><th>Max</th></tr></thead>
                    <tbody>
                        @forelse($program->sections as $section)
                            <tr>
                                <td><strong>{{ $section->code }}</strong></td>
                                <td><a href="{{ route('admin.sections.show', $section) }}">{{ $section->name }}</a></td>
                                <td>{{ $section->academicPeriod?->name ?? '—' }}</td>
                                <td>{{ $section->max_students ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="search-no-results">No sections in this program.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="crud-card" style="margin-top:16px">
                <div class="crud-header"><h3><i class="fa-solid fa-book"></i> Courses ({{ $courses->count() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Code</th><th>Title</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($courses as $course)
                            <tr>
                                <td><strong>{{ $course->code }}</strong></td>
                                <td><a href="{{ route('admin.courses.show', $course) }}">{{ $course->title }}</a></td>
                                <td><x-user-status-badge status="{{ $course->status ?? 'active' }}" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="search-no-results">No courses in this program.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="crud-card" style="margin-top:16px">
                <div class="crud-header"><h3><i class="fa-solid fa-user-graduate"></i> Students ({{ $students->total() }})</h3></div>
                <table class="crud-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Identifier</th><th>Section</th></tr></thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td><a href="{{ route('admin.students.show', $student) }}"><strong>{{ $student->full_name }}</strong></a></td>
                                <td>{{ $student->email }}</td>
                                <td>{{ $student->identifier ?? '—' }}</td>
                                <td>{{ $student->section?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="search-no-results">No students in this program.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($students->hasPages())
                    <div class="pagination">{{ $students->links() }}</div>
                @endif
            </div>

            @if($instructors->count() > 0)
                <div class="crud-card" style="margin-top:16px">
                    <div class="crud-header"><h3><i class="fa-solid fa-chalkboard-user"></i> Instructors ({{ $instructors->count() }})</h3></div>
                    <table class="crud-table">
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach($instructors as $instructor)
                                <tr>
                                    <td><a href="{{ route('admin.instructors.show', $instructor) }}"><strong>{{ $instructor->full_name }}</strong></a></td>
                                    <td>{{ $instructor->email }}</td>
                                    <td><x-user-status-badge status="{{ $instructor->status }}" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

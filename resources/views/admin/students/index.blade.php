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
            <button type="button" onclick="document.getElementById('importStudentModal').classList.add('active')" class="btn btn-secondary"><i class="fa-solid fa-upload"></i> Import</button>
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
                                <th>Department</th>
                                <th>Program</th>
                                <th>Section</th>
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
                                    <td>{{ optional($student->department)->name ?: '—' }}</td>
                                    <td>{{ optional($student->program)->name ?: '—' }}</td>
                                    <td>{{ optional($student->section)->name ?: '—' }}@if($student->section?->code) ({{ $student->section->code }})@endif</td>
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

<!-- Import Student Modal -->
<div class="modal" id="importStudentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-file-import"></i> Import Students</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('importStudentModal').classList.remove('active')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="form-control">
                    <small class="form-text">Upload a CSV or TXT file. Expected columns:<br>first_name, last_name, email, identifier, phone, department_name, program_name, section_name, section_code</small>
                    <small class="form-text" style="margin-top:6px;display:block">Sample CSV:<br><code>John,Doe,john.doe@example.com,STU001,555-1234,Engineering,Computer Science,Section A,CS-A</code></small>
                    <small class="form-text" style="margin-top:6px;display:block">Sample TXT (one per line):<br><code>John,Doe,john.doe@example.com,STU001,555-1234,Engineering,Computer Science,Section A,CS-A</code></small>
                    <small class="form-text" style="margin-top:6px;display:block;color:#667eea">Note: Password will be automatically set to <strong>Password123!</strong> and status to <strong>active</strong></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('importStudentModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

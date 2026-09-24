@extends('layouts.admin')

@section('title', 'Enrollments')
@php
    $activeNav = 'enrollment';
    $pageTitle = 'Enrollment Management';
    $pageIcon = '<i class="fa-solid fa-graduation-cap"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-graduation-cap"></i>
            Enrollment Management
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Enrollments</h3>
            <a href="{{ route('admin.enrollments.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Enrollment
            </a>
        </div>

        <form class="table-toolbar" method="GET" action="{{ route('admin.enrollments.index') }}">
            <input name="search" value="{{ request('search') }}" placeholder="Search student..." aria-label="Search enrollments">
            <select name="student_id" aria-label="Filter student">
                <option value="">All Students</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>{{ $student->full_name }}</option>
                @endforeach
            </select>
            <select name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                @endforeach
            </select>
            <select name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['pending' => 'Pending', 'active' => 'Active', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
            <a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary">Clear</a>
        </form>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Grade</th>
                    <th>Enrolled Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->student->full_name ?? '-' }}</td>
                        <td>{{ $enrollment->class->code ?? '-' }} - {{ $enrollment->class->name ?? '-' }}</td>
                        <td>{{ $enrollment->class->course->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-{{ $enrollment->status }}">{{ ucfirst($enrollment->status) }}</span>
                        </td>
                        <td>{{ $enrollment->final_grade ?? '-' }}</td>
                        <td>{{ $enrollment->enrolled_at->format('M d, Y') }}</td>
                        <td class="actions-cell action-buttons">
                            @if($enrollment->status === 'pending')
                                <form method="POST" action="{{ route('admin.enrollments.approve', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-success" title="Approve"><i class="fa-solid fa-check"></i></button></form>
                                <form method="POST" action="{{ route('admin.enrollments.reject', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-danger" title="Reject" onclick="return confirm('Reject this enrollment request?')"><i class="fa-solid fa-xmark"></i></button></form>
                            @elseif(in_array($enrollment->status, ['active', 'completed']))
                                <form method="POST" action="{{ route('admin.enrollments.drop', $enrollment) }}" class="inline-form">@csrf<button type="submit" class="btn-icon btn-warning" title="Drop" onclick="return confirm('Drop this enrollment?')"><i class="fa-solid fa-user-minus"></i></button></form>
                            @endif
                            <a href="{{ route('admin.enrollments.show', $enrollment) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.enrollments.edit', $enrollment) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.enrollments.destroy', $enrollment) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this enrollment?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:24px;color:#aaa;">
                            No enrollments found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($enrollments->hasPages())
            <div class="pagination">{{ $enrollments->appends(request()->query())->links() }}</div>
        @endif
    </div>
@endsection
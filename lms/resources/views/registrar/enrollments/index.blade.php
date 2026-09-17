@extends('layouts.registrar')
@section('title', 'Enrollments')
@php $activeNav = 'enrollments'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Enrollments"
        subtitle="Manage student enrollments across classes."
        icon="fa-user-plus"
    >
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('registrar.enrollments.create') }}"><i class="fa-solid fa-plus"></i> Enroll Student</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('registrar.enrollments.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search enrollments..." aria-label="Search enrollments">
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'pending' => 'Pending', 'completed' => 'Completed', 'dropped' => 'Dropped'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('registrar.enrollments.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($enrollments->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($enrollments as $enrollment)
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($enrollment->student?->first_name ?? 'U', 0, 1)) }}</div>
                                            <div class="user-name">{{ $enrollment->student?->name ?? '—' }}</div>
                                        </div>
                                    </td>
                                    <td>{{ $enrollment->class?->course?->title ?? $enrollment->class?->code ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $enrollment->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            @if($enrollment->status !== 'dropped')
                                                <form method="POST" action="{{ route('registrar.enrollments.destroy', $enrollment) }}" onsubmit="return confirm('Remove this student from the class?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-danger" title="Remove"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-user-plus"
                    title="No enrollments yet"
                    description="Enroll a student to see the record here."
                />
            @endif

            @if($enrollments->hasPages())
                <div class="pagination">{{ $enrollments->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

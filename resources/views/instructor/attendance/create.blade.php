@extends('layouts.instructor')
@section('title', 'Record Attendance — '.$class->code)
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Record Attendance"
        subtitle="{{ $class->code }} — {{ $class->course?->title ?? 'Class' }}"
        icon="fa-clipboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.attendance.index', $class) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-calendar-check"></i> Session Details</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.classes.attendance.store', $class) }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="attendance_date" value="{{ old('attendance_date', now()->format('Y-m-d')) }}" required>
                        <span class="field-error">{{ $errors->first('attendance_date') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Session Title</label>
                        <input type="text" name="session_title" value="{{ old('session_title') }}" placeholder="e.g. Week 3 lecture">
                        <span class="field-error">{{ $errors->first('session_title') }}</span>
                    </div>
                </div>

                <div class="form-section">
                    <div class="modal-section-title"><i class="fa-solid fa-users"></i> Students ({{ $students->count() }})</div>

                    @if($students->count() > 0)
                        <div class="user-table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($students as $index => $enrollment)
                                        <tr>
                                            <td>
                                                <div class="user-name">{{ $enrollment->student?->full_name ?? $enrollment->student?->name ?? '—' }}</div>
                                                <div class="user-email">{{ $enrollment->student?->email }}</div>
                                            </td>
                                            <td>
                                                <select name="records[{{ $index }}][student_id]" hidden>
                                                    <option value="{{ $enrollment->student_id }}">{{ $enrollment->student?->full_name ?? $enrollment->student?->name ?? '—' }}</option>
                                                </select>
                                                <select name="records[{{ $index }}][status]" aria-label="Status for {{ $enrollment->student?->full_name ?? 'student' }}">
                                                    @foreach($statusOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected(old("records.{$index}.status", 'present') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="records[{{ $index }}][notes]" value="{{ old("records.{$index}.notes") }}" placeholder="Optional note">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-user-empty-state
                            icon="fa-users"
                            title="No active students"
                            description="This class has no active enrolled students."
                        />
                    @endif
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.classes.attendance.index', $class) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

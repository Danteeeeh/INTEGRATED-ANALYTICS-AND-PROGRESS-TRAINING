@extends('layouts.instructor')
@section('title', 'Edit Attendance')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Attendance"
        subtitle="{{ $class->code }} — {{ $attendance->attendance_date?->format('l, F j, Y') }}"
        icon="fa-clipboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.classes.attendance.show', [$class, $attendance]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-pen"></i> Session Details</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.classes.attendance.update', [$class, $attendance]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="attendance_date" value="{{ old('attendance_date', $attendance->attendance_date?->format('Y-m-d')) }}" required>
                        <span class="field-error">{{ $errors->first('attendance_date') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Session Title</label>
                        <input type="text" name="session_title" value="{{ old('session_title', $attendance->session_title) }}" placeholder="e.g. Week 3 lecture">
                        <span class="field-error">{{ $errors->first('session_title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Student</label>
                        <select name="student_id" required>
                            @foreach($students as $enrollment)
                                <option value="{{ $enrollment->student_id }}" @selected(old('student_id', $attendance->student_id) == $enrollment->student_id)>
                                    {{ $enrollment->student?->full_name ?? $enrollment->student?->name ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('student_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $attendance->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" placeholder="Optional note">{{ old('notes', $attendance->notes) }}</textarea>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.classes.attendance.show', [$class, $attendance]) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

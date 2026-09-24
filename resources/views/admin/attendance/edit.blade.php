@extends('layouts.admin')

@section('title', 'Edit Attendance')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Attendance"
        subtitle="Update attendance details for a student session."
        icon="fa-clipboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.attendance.show', $attendanceRecord) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Record Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.attendance.update', $attendanceRecord) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field">
                        <label>Student <span class="required">*</span></label>
                        <select name="student_id" required>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id', $attendanceRecord->student_id) == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('student_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $attendanceRecord->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Virtual Class</label>
                        <select name="virtual_class_id">
                            <option value="">— None —</option>
                            @foreach($virtualClasses as $virtualClass)
                                <option value="{{ $virtualClass->id }}" @selected(old('virtual_class_id', $attendanceRecord->virtual_class_id) == $virtualClass->id)>{{ $virtualClass->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('virtual_class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="attendance_date" value="{{ old('attendance_date', $attendanceRecord->attendance_date?->format('Y-m-d')) }}" required>
                        <span class="field-error">{{ $errors->first('attendance_date') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Session Title</label>
                        <input type="text" name="session_title" value="{{ old('session_title', $attendanceRecord->session_title) }}" placeholder="e.g. Week 3 lecture">
                        <span class="field-error">{{ $errors->first('session_title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            @foreach(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $attendanceRecord->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Joined At</label>
                        <input type="datetime-local" name="joined_at" value="{{ old('joined_at', $attendanceRecord->joined_at?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('joined_at') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Left At</label>
                        <input type="datetime-local" name="left_at" value="{{ old('left_at', $attendanceRecord->left_at?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('left_at') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" min="0" value="{{ old('duration_minutes', $attendanceRecord->duration_minutes) }}">
                        <span class="field-error">{{ $errors->first('duration_minutes') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" maxlength="500" placeholder="Optional note">{{ old('notes', $attendanceRecord->notes) }}</textarea>
                        <span class="field-error">{{ $errors->first('notes') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.attendance.show', $attendanceRecord) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

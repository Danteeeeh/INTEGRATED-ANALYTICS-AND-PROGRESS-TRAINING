@extends('layouts.admin')

@section('title', 'Record Attendance')
@php $activeNav = 'attendance'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Record Attendance"
        subtitle="Mark attendance for students in a class session."
        icon="fa-clipboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-calendar-check"></i> Session Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.attendance.store') }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required id="classSelect">
                            <option value="">— Select class —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $preselectedClassId) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Virtual Class (optional)</label>
                        <select name="virtual_class_id">
                            <option value="">— None —</option>
                            @foreach($virtualClasses as $virtualClass)
                                <option value="{{ $virtualClass->id }}" @selected(old('virtual_class_id') == $virtualClass->id)>{{ $virtualClass->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('virtual_class_id') }}</span>
                    </div>

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
                    <div class="modal-section-title">
                        <i class="fa-solid fa-users"></i> Students
                    </div>

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
                                    @foreach($students as $index => $student)
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-name">{{ $student->name }}</div>
                                                    <div class="user-email">{{ $student->email }}</div>
                                                </div>
                                                <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                                            </td>
                                            <td>
                                                <select name="statuses[]" aria-label="Attendance status for {{ $student->name }}">
                                                    @foreach(['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused'] as $value => $label)
                                                        <option value="{{ $value }}" @selected(old("records.{$index}.status", 'present') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="notes[]" value="{{ old('notes.'.$index) }}" placeholder="Optional note" aria-label="Notes for {{ $student->name }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-user-empty-state
                            icon="fa-users"
                            title="No students available"
                            description="Select a class with enrolled students, then save the page to load the roster."
                        />
                    @endif
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.attendance.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

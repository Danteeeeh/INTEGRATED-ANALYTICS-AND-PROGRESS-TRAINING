@extends('layouts.registrar')
@section('title', 'Edit Student')
@php $activeNav = 'students'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Student"
        subtitle="Update student account details."
        icon="fa-user-graduate"
    >
        <x-slot name="actions">
            <a href="{{ route('registrar.students.show', $student) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-user-pen"></i> Student Information</h3></div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('registrar.students.update', $student) }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field">
                        <label>First Name <span class="required">*</span></label>
                        <input name="first_name" value="{{ old('first_name', $student->first_name) }}" required placeholder="Enter first name">
                        <span class="field-error">{{ $errors->first('first_name') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Last Name <span class="required">*</span></label>
                        <input name="last_name" value="{{ old('last_name', $student->last_name) }}" required placeholder="Enter last name">
                        <span class="field-error">{{ $errors->first('last_name') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $student->email) }}" required placeholder="student@example.com">
                        <span class="field-error">{{ $errors->first('email') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Student ID</label>
                        <input name="identifier" value="{{ old('identifier', $student->identifier) }}" placeholder="Student ID (optional)">
                        <span class="field-error">{{ $errors->first('identifier') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['active', 'inactive', 'pending', 'suspended'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $student->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('registrar.students.show', $student) }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

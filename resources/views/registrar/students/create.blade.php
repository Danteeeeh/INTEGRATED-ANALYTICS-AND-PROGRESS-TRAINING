@extends('layouts.registrar')
@section('title', 'Add Student')
@php $activeNav = 'students'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Add Student"
        subtitle="Create a new student account."
        icon="fa-user-graduate"
    >
        <x-slot name="actions">
            <a href="{{ route('registrar.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-user-plus"></i> Student Information</h3></div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('registrar.students.store') }}">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>First Name <span class="required">*</span></label>
                        <input name="first_name" value="{{ old('first_name') }}" required placeholder="Enter first name">
                        <span class="field-error">{{ $errors->first('first_name') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Last Name <span class="required">*</span></label>
                        <input name="last_name" value="{{ old('last_name') }}" required placeholder="Enter last name">
                        <span class="field-error">{{ $errors->first('last_name') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="student@example.com">
                        <span class="field-error">{{ $errors->first('email') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Student ID</label>
                        <input name="identifier" value="{{ old('identifier') }}" placeholder="Student ID (optional)">
                        <span class="field-error">{{ $errors->first('identifier') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Temporary Password <span class="required">*</span></label>
                        <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters">
                        <span class="field-error">{{ $errors->first('password') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('registrar.students.index') }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Cancel</a>
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Create Student</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

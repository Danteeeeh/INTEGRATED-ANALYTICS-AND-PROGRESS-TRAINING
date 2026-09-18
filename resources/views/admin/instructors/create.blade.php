@extends('layouts.admin')

@section('title', 'Add Instructor')
@php $activeNav = 'instructors'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Add Instructor"
        subtitle="Create a new instructor account with role permissions."
        icon="fa-chalkboard-user"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.instructors.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-user-plus"></i> Instructor Information</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.instructors.store') }}" method="POST" class="enhanced-form">
                @csrf

                <div class="form-section" data-section="1">
                    <div class="modal-section-title">
                        <i class="fa-solid fa-user"></i> Personal Information
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label>First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Enter first name" autocomplete="given-name">
                            <span class="field-error">{{ $errors->first('first_name') }}</span>
                        </div>
                        <div class="form-field">
                            <label>Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Enter last name" autocomplete="family-name">
                            <span class="field-error">{{ $errors->first('last_name') }}</span>
                        </div>
                        <div class="form-field full">
                            <label>Email Address <span class="required">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="instructor@example.com" autocomplete="email">
                            <span class="field-error">{{ $errors->first('email') }}</span>
                        </div>
                    </div>
                </div>

                <div class="form-section" data-section="2">
                    <div class="modal-section-title">
                        <i class="fa-solid fa-cog"></i> Account Settings
                    </div>
                    <div class="form-grid">
                        <div class="form-field full">
                            <label>Password <span class="required">*</span></label>
                            <input type="password" name="password" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
                            <span class="field-error">{{ $errors->first('password') }}</span>
                        </div>
                        <div class="form-field">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="09XXXXXXXXX" autocomplete="tel">
                            <span class="field-error">{{ $errors->first('phone') }}</span>
                        </div>
                        <div class="form-field">
                            <label>Instructor ID</label>
                            <input type="text" name="identifier" value="{{ old('identifier') }}" placeholder="Instructor ID (optional)">
                            <span class="field-error">{{ $errors->first('identifier') }}</span>
                        </div>
                        <div class="form-field full">
                            <label>Status</label>
                            <select name="status">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                            </select>
                            <span class="field-error">{{ $errors->first('status') }}</span>
                        </div>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.instructors.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Create Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

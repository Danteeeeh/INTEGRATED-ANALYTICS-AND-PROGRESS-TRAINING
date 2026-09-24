@extends('layouts.admin-sms')

@section('title', 'Edit Academic Period')
@php
    $activeNav = 'academic_periods';
    $pageTitle = 'Edit Academic Period';
    $pageIcon = '<i class="fa-solid fa-calendar"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-calendar"></i>
            Edit Academic Period
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Edit Academic Period</h3>
        <form method="POST" action="{{ route('admin.academic_periods.update', $academicPeriod) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field">
                    <label>Code <span class="req">*</span></label>
                    <input type="text" name="code" required placeholder="e.g. 2024-2025" value="{{ old('code', $academicPeriod->code) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Name <span class="req">*</span></label>
                    <input type="text" name="name" required placeholder="e.g. 2024-2025 Academic Year" value="{{ old('name', $academicPeriod->name) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Start Date <span class="req">*</span></label>
                    <input type="date" name="start_date" required value="{{ old('start_date', $academicPeriod->start_date->format('Y-m-d')) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>End Date <span class="req">*</span></label>
                    <input type="date" name="end_date" required value="{{ old('end_date', $academicPeriod->end_date->format('Y-m-d')) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Period description...">{{ old('description', $academicPeriod->description) }}</textarea>
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <select name="is_active">
                        <option value="1" {{ $academicPeriod->is_active ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !$academicPeriod->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Update Period</button>
                <a href="{{ route('admin.academic_periods.index') }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
@endsection
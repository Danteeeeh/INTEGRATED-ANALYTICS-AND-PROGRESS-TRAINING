@extends('layouts.instructor')

@section('title', 'Create Virtual Class - ' . $class->name)
@php
    $activeNav = 'classes';
    $pageTitle = 'Create Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Create Virtual Class &mdash; {{ $class->code }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>New Virtual Class</h3>
        <form method="POST" action="{{ route('instructor.classes.virtual_classes.store', $class) }}">
            @csrf
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Week 3 - Introduction to Algorithms" value="{{ old('title') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Agenda, topics, materials to prepare...">{{ old('description') }}</textarea>
                </div>

                <div class="form-field">
                    <label>Date <span class="req">*</span></label>
                    <input type="date" name="meeting_date" required value="{{ old('meeting_date') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Start Time <span class="req">*</span></label>
                    <input type="time" name="start_time" required value="{{ old('start_time') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>End Time <span class="req">*</span></label>
                    <input type="time" name="end_time" required value="{{ old('end_time') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Provider <span class="req">*</span></label>
                    <select name="meeting_provider" required>
                        <option value="">Select a provider</option>
                        @foreach($providers as $value => $label)
                            <option value="{{ $value }}" {{ old('meeting_provider') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Meeting URL</label>
                    <input type="url" name="meeting_url" placeholder="https://..." value="{{ old('meeting_url') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Meeting ID</label>
                    <input type="text" name="meeting_id" placeholder="e.g. 123 456 7890" value="{{ old('meeting_id') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Meeting Password</label>
                    <input type="text" name="meeting_password" placeholder="e.g. Abc123" value="{{ old('meeting_password') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" required>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ old('status', 'scheduled') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <span class="field-error"></span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Virtual Class</button>
                <a href="{{ route('instructor.classes.virtual_classes.index', $class) }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
@endsection

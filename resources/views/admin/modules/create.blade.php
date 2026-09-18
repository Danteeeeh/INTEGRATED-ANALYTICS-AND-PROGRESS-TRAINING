@extends('layouts.admin-sms')

@section('title', 'Create Module - ' . $course->name)
@php
    $activeNav = 'courses';
    $pageTitle = 'Create Module';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            Create Module for {{ $course->name }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Create Module</h3>
        <form method="POST" action="{{ route('admin.courses.modules.store', $course) }}">
            @csrf
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Introduction to Programming" value="{{ old('title') }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Module description...">{{ old('description') }}</textarea>
                </div>

                <div class="form-field full">
                    <label>Objectives</label>
                    <textarea name="objectives" rows="4" placeholder="Learning objectives for this module (one per line)...">{{ old('objectives') }}</textarea>
                </div>

                <div class="form-field">
                    <label>Position <span class="req">*</span></label>
                    <input type="number" name="position" required min="1" placeholder="e.g. 1" value="{{ old('position', $nextPosition ?? 1) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Status <span class="req">*</span></label>
                    <select name="status" required>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                    </select>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Module</button>
                <a href="{{ route('admin.courses.modules.index', $course) }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 60px; border-radius: 8px; font-size: 0.92rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; margin-left: 10px;">Cancel</a>
            </div>
        </form>
    </div>
@endsection

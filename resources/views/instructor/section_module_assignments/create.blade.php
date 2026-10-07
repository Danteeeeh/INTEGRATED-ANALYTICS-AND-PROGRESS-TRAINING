@extends('layouts.instructor')

@section('title', 'Assign Module to Section')
@php
    $activeNav = 'modules';
    $pageTitle = 'Assign Module';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            Assign a module to this section
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.classes.section_module_assignments.index', $class) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-plus"></i> Assign Module</h3>
        </div>

        <div style="padding: 18px;">
            @if (session('error'))
                <div style="padding: 12px 16px; color:#92400e; background:#fffbeb; border:1px solid #fde68a; border-radius: 9px; margin-bottom:16px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div style="padding: 12px 16px; color:#991b1b; background:#fef2f2; border:1px solid #fecaca; border-radius: 9px; margin-bottom:16px;">
                    <ul style="margin:6px 0 0;padding-left:18px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($modules->isEmpty())
                <p style="color:#aaa;">
                    This course has no published modules to assign yet.
                    <a href="{{ route('instructor.courses.modules.create', $class->course) }}" style="color:#2563eb;text-decoration:underline;">Create one</a>.
                </p>
            @else
                <form method="POST" action="{{ route('instructor.classes.section_module_assignments.store', $class) }}">
                    @csrf

                    <div class="form-field" style="margin-bottom:14px;">
                        <label>Module <span style="color:#fda4af;">*</span></label>
                        <select name="module_id" required style="width:100%; min-height:40px; padding:9px 12px; border:1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius:9px; background:#101625; color: var(--bcp-ink, #eef4ff); font-size:.88rem;">
                            <option value="">Select a module…</option>
                            @foreach ($modules as $module)
                                <option value="{{ $module->id }}" @selected(old('module_id') == $module->id)>
                                    {{ $module->title }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('module_id') }}</span>
                    </div>

                    <div style="display:flex;gap:10px;align-items:center;">
                        <button type="submit" class="btn-add" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;">
                            <i class="fa-solid fa-check"></i>
                            Assign Module
                        </button>
                        <a href="{{ route('instructor.classes.section_module_assignments.index', $class) }}" class="btn-modal-cancel" style="padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;">
                            Cancel
                        </a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
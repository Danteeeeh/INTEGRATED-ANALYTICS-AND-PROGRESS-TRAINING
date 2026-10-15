@extends('layouts.instructor')

@section('title', 'Create Virtual Class - ' . $class->name)
@php
    $activeNav = 'virtual_classes';
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

                {{-- Built-in Virtual Classroom (LiveKit) is the only workflow:
                     students join from inside the LMS, no external links. --}}
                <input type="hidden" name="meeting_provider" value="livekit">
                <div class="form-field full">
                    <div style="padding:12px 14px;background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.3);border-radius:8px;font-size:.85rem;color:#475569;display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-video" style="color:#0ea5e9"></i>
                        <span><strong>Built-in Virtual Classroom.</strong> Ang meeting ay gaganap sa loob ng LMS — ang students ay sumasali mula sa course page nila. Hindi na kailangan ng Zoom/Meet/Teams link.</span>
                    </div>
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <div style="padding:12px 14px;background:rgba(52,211,153,.08);border:1px solid rgba(52,211,153,.3);border-radius:8px;font-size:.85rem;color:#475569;display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-circle-info" style="color:#34d399"></i>
                        <span>Pagkatapos i-save, pindutin ang <strong>Start Class</strong> sa oras ng klase — awtomatiko kang makakapasok sa virtual classroom, at ang enrolled students ay may <strong>Join</strong> button sa kanilang page.</span>
                    </div>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Virtual Class</button>
                <a href="{{ route('instructor.classes.virtual_classes.index', $class) }}" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.instructor')

@section('title', 'Edit Virtual Class - ' . $class->name)
@php
    $activeNav = 'virtual_classes';
    $pageTitle = 'Edit Virtual Class';
    $pageIcon = '<i class="fa-solid fa-video"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-video"></i>
            Edit Virtual Class &mdash; {{ $class->code }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Reschedule / Edit Virtual Class</h3>
        <form method="POST" action="{{ route('instructor.classes.virtual_classes.update', [$class, $virtualClass]) }}">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field full">
                    <label>Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Week 3 - Introduction to Algorithms" value="{{ old('title', $virtualClass->title) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Agenda, topics, materials to prepare...">{{ old('description', $virtualClass->description) }}</textarea>
                </div>

                <div class="form-field">
                    <label>Date <span class="req">*</span></label>
                    <input type="date" name="meeting_date" required value="{{ old('meeting_date', $virtualClass->meeting_date->format('Y-m-d')) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>Start Time <span class="req">*</span></label>
                    <input type="time" name="start_time" required value="{{ old('start_time', \Carbon\Carbon::parse($virtualClass->start_time)->format('H:i')) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field">
                    <label>End Time <span class="req">*</span></label>
                    <input type="time" name="end_time" required value="{{ old('end_time', \Carbon\Carbon::parse($virtualClass->end_time)->format('H:i')) }}">
                    <span class="field-error"></span>
                </div>

                <div class="form-field full">
                    <div style="padding:12px 14px;background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.3);border-radius:8px;font-size:.85rem;color:#475569;display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-video" style="color:#0ea5e9"></i>
                        <span><strong>Built-in Virtual Classroom.</strong> Ang meeting ay gaganap sa loob ng LMS — ang students ay sumasali mula sa course page nila. Hindi na kailangan ng Zoom/Meet/Teams link.</span>
                    </div>
                    <span class="field-error"></span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Save Changes</button>
                <a href="{{ route('instructor.classes.virtual_classes.show', [$class, $virtualClass]) }}" class="btn-cancel">Cancel</a>
            </div>
        </form>

        <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <form method="POST" action="{{ route('instructor.classes.virtual_classes.cancel', [$class, $virtualClass]) }}" onsubmit="return confirm('Cancel this virtual class? Students will no longer see it as upcoming.');">
                @csrf
                <button type="submit" class="btn-submit" style="background:#dc2626; border-color:#dc2626;">
                    <i class="fa-solid fa-ban"></i> Cancel this Class
                </button>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', 'Edit Virtual Class')
@php($activeNav = 'virtual_classes')

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Virtual Class"
        subtitle="Schedule an online meeting for a course, class, or instructor."
        icon="fa-video"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.virtual_classes.index') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.virtual_classes.update', $virtualClass) }}">
                @csrf
                @method('PUT')

                <div class="modal-section-title"><i class="fa-solid fa-link"></i> Associations</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">Select Course (optional)</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('course_id', $virtualClass->course_id) == $course->id ? 'selected' : '' }}>
                                    {{ $course->code }} - {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field-error"></span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">Select Class (optional)</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ old('class_id', $virtualClass->class_id) == $class->id ? 'selected' : '' }}>
                                    {{ $class->code }} ({{ $class->course->code ?? 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                        <span class="field-error"></span>
                    </div>

                    <div class="form-field">
                        <label>Instructor <span class="req">*</span></label>
                        <select name="instructor_id" required>
                            <option value="">Select Instructor</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" {{ old('instructor_id', $virtualClass->instructor_id) == $instructor->id ? 'selected' : '' }}>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="field-error"></span>
                    </div>
                </div>

                <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="req">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Week 3: Introduction to Algorithms" value="{{ old('title', $virtualClass->title) }}">
                        <span class="field-error"></span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Agenda, topics, notes...">{{ old('description', $virtualClass->description) }}</textarea>
                    </div>
                </div>

                <div class="modal-section-title"><i class="fa-solid fa-calendar-days"></i> Schedule</div>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Meeting Date <span class="req">*</span></label>
                        <input type="date" name="meeting_date" required value="{{ old('meeting_date', $virtualClass->meeting_date->format('Y-m-d')) }}">
                        <span class="field-error"></span>
                    </div>

                    <div class="form-field">
                        <label>Start Time <span class="req">*</span></label>
                        <input type="time" name="start_time" required value="{{ old('start_time', $virtualClass->start_time) }}">
                        <span class="field-error"></span>
                    </div>

                    <div class="form-field">
                        <label>End Time <span class="req">*</span></label>
                        <input type="time" name="end_time" required value="{{ old('end_time', $virtualClass->end_time) }}">
                        <span class="field-error"></span>
                    </div>
                </div>

                <div class="modal-section-title"><i class="fa-solid fa-video"></i> Meeting Details</div>
                <div class="form-grid">
                    @if($virtualClass->meeting_provider === \App\Models\VirtualClass::PROVIDER_LIVEKIT)
                        {{-- Built-in Virtual Classroom: room auto-generated, no external links. --}}
                        <input type="hidden" name="meeting_provider" value="livekit">
                        <div class="form-field full">
                            <div style="padding:12px 14px;background:rgba(14,165,233,.08);border:1px solid rgba(14,165,233,.3);border-radius:8px;font-size:.85rem;color:#475569;display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-video" style="color:#0ea5e9"></i>
                                <span><strong>Built-in Virtual Classroom.</strong> Room: <code>{{ $virtualClass->livekit_room_name }}</code> — ang instructor at ang enrolled students ay sumasali sa loob ng LMS.</span>
                            </div>
                        </div>
                    @else
                        {{-- Legacy external-provider record: keep the editable fields so history stays intact. --}}
                        <div class="form-field full">
                            <div style="padding:12px 14px;background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.3);border-radius:8px;font-size:.85rem;color:#475569;">
                                <i class="fa-solid fa-triangle-exclamation" style="color:#fbbf24"></i>
                                Legacy external provider (<strong>{{ $virtualClass->providerLabel() }}</strong>). Ang bagong virtual class ay built-in na (LiveKit) — ito ay pinapanatili para sa existing records.
                            </div>
                        </div>
                        <div class="form-field">
                            <label>Meeting Provider</label>
                            <select name="meeting_provider">
                                <option value="zoom" {{ old('meeting_provider', $virtualClass->meeting_provider) == 'zoom' ? 'selected' : '' }}>Zoom</option>
                                <option value="google_meet" {{ old('meeting_provider', $virtualClass->meeting_provider) == 'google_meet' ? 'selected' : '' }}>Google Meet</option>
                                <option value="microsoft_teams" {{ old('meeting_provider', $virtualClass->meeting_provider) == 'microsoft_teams' ? 'selected' : '' }}>Microsoft Teams</option>
                                <option value="other" {{ old('meeting_provider', $virtualClass->meeting_provider) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="form-field full">
                            <label>Meeting URL</label>
                            <input type="url" name="meeting_url" placeholder="https://..." value="{{ old('meeting_url', $virtualClass->meeting_url) }}">
                        </div>
                        <div class="form-field">
                            <label>Meeting ID</label>
                            <input type="text" name="meeting_id" value="{{ old('meeting_id', $virtualClass->meeting_id) }}">
                        </div>
                        <div class="form-field">
                            <label>Meeting Password</label>
                            <input type="text" name="meeting_password" value="{{ old('meeting_password', $virtualClass->meeting_password) }}">
                        </div>
                    @endif

                    <div class="form-field">
                        <label>Status <span class="req">*</span></label>
                        <select name="status" required>
                            <option value="scheduled" {{ old('status', $virtualClass->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="ongoing" {{ old('status', $virtualClass->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                            <option value="completed" {{ old('status', $virtualClass->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ old('status', $virtualClass->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-submit">
                    <a href="{{ route('admin.virtual_classes.index') }}" class="btn btn-secondary" style="margin-right:10px;">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Virtual Class</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
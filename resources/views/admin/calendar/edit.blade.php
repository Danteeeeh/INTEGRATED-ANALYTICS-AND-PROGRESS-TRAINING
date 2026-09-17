@extends('layouts.admin')

@section('title', 'Edit Calendar Event')
@php $activeNav = 'calendar'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Calendar Event"
        subtitle="Update event schedule, type, and visibility."
        icon="fa-calendar"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.calendar.show', $calendarEvent) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Event Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.calendar.update', $calendarEvent) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $calendarEvent->title) }}" required placeholder="Event title">
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Event Type <span class="required">*</span></label>
                        <select name="event_type" required>
                            @foreach(['assignment' => 'Assignment', 'quiz' => 'Quiz', 'virtual_class' => 'Virtual Class', 'exam' => 'Exam', 'announcement' => 'Announcement', 'course' => 'Course', 'personal' => 'Personal'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('event_type', $calendarEvent->event_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('event_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Visibility <span class="required">*</span></label>
                        <select name="visibility" required>
                            @foreach(['private' => 'Private', 'course' => 'Course', 'class' => 'Class', 'public' => 'Public'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('visibility', $calendarEvent->visibility) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('visibility') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Starts <span class="required">*</span></label>
                        <input type="datetime-local" name="start_at" value="{{ old('start_at', $calendarEvent->start_at?->format('Y-m-d\TH:i')) }}" required>
                        <span class="field-error">{{ $errors->first('start_at') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Ends <span class="required">*</span></label>
                        <input type="datetime-local" name="end_at" value="{{ old('end_at', $calendarEvent->end_at?->format('Y-m-d\TH:i')) }}" required>
                        <span class="field-error">{{ $errors->first('end_at') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— Select course —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $calendarEvent->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— Select class —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $calendarEvent->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Location</label>
                        <input type="text" name="location" value="{{ old('location', $calendarEvent->location) }}" placeholder="Room, link, or venue">
                        <span class="field-error">{{ $errors->first('location') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_all_day" value="1" @checked(old('is_all_day', $calendarEvent->is_all_day))>
                            All day event
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Event description">{{ old('description', $calendarEvent->description) }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.calendar.show', $calendarEvent) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

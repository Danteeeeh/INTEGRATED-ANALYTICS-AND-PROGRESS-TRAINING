@extends('layouts.admin')

@section('title', $calendarEvent->title)
@php $activeNav = 'calendar'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $calendarEvent->title }}"
        subtitle="Calendar event details."
        icon="fa-calendar"
    >
        <x-slot name="meta">
            <span class="user-status">{{ ucfirst(str_replace('_', ' ', $calendarEvent->event_type)) }}</span>
            <x-user-status-badge status="{{ $calendarEvent->visibility }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.calendar.edit', $calendarEvent) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.calendar.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Event Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Starts</label>
                    <div>{{ $calendarEvent->start_at?->format('l, F j, Y g:i A') }}</div>
                </div>
                <div class="form-field">
                    <label>Ends</label>
                    <div>{{ $calendarEvent->is_all_day ? 'All day' : ($calendarEvent->end_at?->format('l, F j, Y g:i A') ?? '—') }}</div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $calendarEvent->course?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $calendarEvent->class?->code ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Location</label>
                    <div>{{ $calendarEvent->location ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $calendarEvent->creator?->name ?? '—' }}</div>
                </div>
                @if($calendarEvent->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div style="white-space:pre-line">{{ $calendarEvent->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.calendar.destroy', $calendarEvent) }}" method="POST" onsubmit="return confirm('Delete this event?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

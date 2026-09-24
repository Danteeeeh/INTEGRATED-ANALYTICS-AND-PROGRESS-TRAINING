@extends('layouts.instructor')
@section('title', $announcement->title)
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $announcement->title }}"
        subtitle="Announcement for {{ $course->title }}"
        icon="fa-bullhorn"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $announcement->status }}" />
            @if($announcement->is_pinned)<span class="user-status active">Pinned</span>@endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.announcements.edit', [$course, $announcement]) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('instructor.courses.announcements.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Announcement</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Audience</label>
                    <div>{{ ucfirst($announcement->audience_type ?? 'course') }}</div>
                    @if($announcement->class)
                        <div class="user-email">{{ $announcement->class->code }}</div>
                    @endif
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $announcement->status }}" /></div>
                </div>
                <div class="form-field full">
                    <label>Body</label>
                    <div style="white-space:pre-line">{{ $announcement->body }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('instructor.courses.announcements.destroy', [$course, $announcement]) }}" method="POST" onsubmit="return confirm('Delete this announcement?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

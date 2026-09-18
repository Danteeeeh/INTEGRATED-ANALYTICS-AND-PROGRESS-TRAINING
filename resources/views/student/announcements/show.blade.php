@extends('layouts.student')
@section('title', $announcement->title)
@php $activeNav = 'announcements'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $announcement->title }}"
        subtitle="{{ $course->title }} — announcement"
        icon="fa-bullhorn"
    >
        <x-slot name="meta">
            @if($announcement->is_pinned)<span class="user-status active">Pinned</span>@endif
            <span>{{ $announcement->created_at?->format('l, F j, Y') }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.announcements.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-bullhorn"></i> Announcement</h3>
            <span class="user-email">{{ $announcement->creator?->name ?? '' }}</span>
        </div>
        <div class="user-panel-body">
            <div style="white-space:pre-line">{{ $announcement->body }}</div>

            @if($announcement->attachment)
                <div class="user-actions" style="justify-content:flex-start;margin-top:14px">
                    <a href="{{ $announcement->attachment->url ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-paperclip"></i> Attachment
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

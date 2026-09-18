@extends('layouts.instructor')

@section('title', 'Announcements — ' . $course->code)
@php
    $activeNav = 'courses';
    $pageTitle = 'Announcements';
    $pageIcon = '<i class="fa-solid fa-bullhorn"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-bullhorn"></i>
            Announcements — {{ $course->code }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.show', $course) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Course
            </a>
            <a href="{{ route('instructor.courses.announcements.create', $course) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Create Announcement
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-bullhorn"></i> Announcements ({{ $announcements->total() }})</h3>
        </div>
        <div style="padding: 16px 24px;">
            @forelse($announcements as $announcement)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(244,63,94,.13);color:#fda4af"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $announcement->title }}
                            @if($announcement->is_pinned ?? false)
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @endif
                        </div>
                        <div class="module-mini-meta">
                            {{ $announcement->class?->code ?? 'Course-wide' }}
                            <span style="margin:0 8px;">·</span>{{ $announcement->created_at?->diffForHumans() }}
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <a href="{{ route('instructor.courses.announcements.show', [$course, $announcement]) }}" class="btn-icon btn-view" title="View">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <a href="{{ route('instructor.courses.announcements.edit', [$course, $announcement]) }}" class="btn-icon btn-edit" title="Edit">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="tab-empty-state">
                    <i class="fa-solid fa-bullhorn"></i>
                    No announcements yet. <a href="{{ route('instructor.courses.announcements.create', $course) }}">Post one</a>
                </div>
            @endforelse
        </div>
        @if($announcements->hasPages())
            <div style="padding:14px 24px;">{{ $announcements->links() }}</div>
        @endif
    </div>
@endsection

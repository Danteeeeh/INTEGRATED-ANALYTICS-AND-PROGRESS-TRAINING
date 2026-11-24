@extends('layouts.instructor')

@section('title', 'All Announcements')
@php
    $activeNav = 'announcements';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-bullhorn"></i>
            All Announcements
        </h2>
        <div class="page-actions" style="margin-left:auto;">
            <a href="{{ route('instructor.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Announcements you posted ({{ $announcements->total() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            Every announcement across your courses and classes, not just the three newest.
        </div>
        <div style="padding:16px 24px;">
            @forelse($announcements as $announcement)
                @php
                    $courseId = $announcement->course_id ?? $announcement->class?->course_id;
                @endphp
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(244,63,94,.13);color:#fda4af"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">
                            {{ $announcement->title }}
                            @if($announcement->is_pinned)
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @endif
                        </div>
                        <div class="module-mini-meta">
                            {{ $announcement->class?->code ?? $announcement->course?->code ?? 'Institution' }}
                            <span style="margin:0 8px;">·</span>
                            {{ $announcement->created_at?->diffForHumans() }}
                        </div>
                        <div style="margin-top:4px;font-size:.8rem;color:#94a3b8;">
                            {{ Str::limit($announcement->body ?? '', 120) }}
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        @if($courseId)
                            <a href="{{ route('instructor.courses.announcements.show', [$courseId, $announcement]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            @if($announcement->created_by === auth()->id())
                                <a href="{{ route('instructor.courses.announcements.edit', [$courseId, $announcement]) }}" class="btn-icon btn-edit" title="Edit">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @empty
                <div class="tab-empty-state">
                    <i class="fa-solid fa-bullhorn"></i>
                    No announcements yet.
                </div>
            @endforelse
        </div>
        @if($announcements->hasPages())
            <div style="padding:14px 24px;">{{ $announcements->links() }}</div>
        @endif
    </div>
@endsection

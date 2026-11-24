@extends('layouts.student')

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
            <a href="{{ route('student.dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to dashboard
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>Announcements for your courses ({{ $announcements->total() }})</h3>
        </div>
        <div style="padding:12px 16px;font-size:.82rem;color:#64748b;">
            <i class="fa-solid fa-circle-info"></i>
            Every announcement visible to you across your enrolled courses and classes.
        </div>
        <div style="padding:16px 24px;">
            @forelse($announcements as $announcement)
                @php
                    $courseId = $announcement->course_id ?? $announcement->class?->course_id;
                @endphp
                <div class="learning-card" style="min-height:auto;justify-content:flex-start;gap:10px;margin-bottom:12px;">
                    <div class="user-kicker">
                        @if($announcement->is_pinned)<i class="fa-solid fa-thumbtack"></i> Pinned · @endif
                        {{ $announcement->class?->code ?? $announcement->course?->code ?? 'Institution' }}
                        · {{ $announcement->created_at?->format('M j, Y') }}
                    </div>
                    <h3>{{ $announcement->title }}</h3>
                    <p>{{ Str::limit($announcement->body ?? '', 180) }}</p>
                    @if($courseId)
                        <div class="user-actions" style="justify-content:flex-start">
                            <a href="{{ route('student.courses.announcements.show', [$courseId, $announcement]) }}" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-eye"></i> Read
                            </a>
                        </div>
                    @endif
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

@extends('layouts.admin-sms')

@section('title', 'Discussion Details')
@php
    $activeNav = 'courses';
    $pageTitle = 'Discussion Details';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            {{ $discussion->title }}
        </h2>
    </div>
@endsection

@section('content')
    <div class="form-card">
        <h3>Discussion Information</h3>

        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px;">
            @php
                $typeBadge = match($discussion->type) {
                    'general' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'General'],
                    'academic' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Academic'],
                    'qna' => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Q&amp;A'],
                    'graded' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Graded'],
                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => ucfirst($discussion->type)],
                };
            @endphp
            <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: {{ $typeBadge['bg'] }}; color: {{ $typeBadge['color'] }};">
                {!! $typeBadge['label'] !!}
            </span>
            @if($discussion->is_pinned)
                <span class="badge-active"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
            @endif
            @if($discussion->is_locked)
                <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; background: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-lock"></i> Locked
                </span>
            @endif
        </div>

        <div class="modal-section-title"><i class="fa-solid fa-info-circle"></i> Basic Information</div>
        <div class="modal-row"><span>Course:</span><span>{{ $discussion->course->code ?? '-' }} - {{ ($discussion->course->name ?? $discussion->course->title) ?? 'N/A' }}</span></div>
        <div class="modal-row"><span>Class:</span><span>{{ $discussion->class->code ?? 'N/A' }}</span></div>
        <div class="modal-row"><span>Author:</span><span>{{ $discussion->user->name ?? 'N/A' }}</span></div>
        <div class="modal-row"><span>Created:</span><span>{{ $discussion->created_at?->format('M d, Y g:i A') ?? 'N/A' }}</span></div>
        <div class="modal-row"><span>Posts:</span><span>{{ ($discussion->posts_count ?? $discussion->posts->count()) ?? 0 }}</span></div>

        <div class="modal-section-title" style="margin-top:14px;"><i class="fa-solid fa-align-left"></i> Content</div>
        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; line-height: 1.7; color: #374151; white-space: pre-wrap;">
            {{ $discussion->content ?? 'No content' }}
        </div>

        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="{{ route('admin.discussions.edit', $discussion) }}" class="btn-submit" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px;">
                <i class="fa-solid fa-pen-to-square" style="margin-right: 6px;"></i> Edit
            </a>
            <form method="POST" action="{{ route('admin.discussions.pin', $discussion) }}">
                @csrf
                <button type="submit" style="padding: 10px 24px; background: {{ ($discussion->is_pinned ? '#fef3c7' : '#e5e7eb') }}; color: {{ ($discussion->is_pinned ? '#b45309' : '#374151') }}; border: none; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-thumbtack" style="margin-right: 6px;"></i> {{ $discussion->is_pinned ? 'Unpin' : 'Pin' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.discussions.lock', $discussion) }}">
                @csrf
                <button type="submit" style="padding: 10px 24px; background: {{ ($discussion->is_locked ? '#fee2e2' : '#e5e7eb') }}; color: {{ ($discussion->is_locked ? '#dc2626' : '#374151') }}; border: none; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-lock" style="margin-right: 6px;"></i> {{ $discussion->is_locked ? 'Unlock' : 'Lock' }}
                </button>
            </form>
        </div>
    </div>

    <div style="margin: 0 24px 24px;">
        <a href="{{ route('admin.discussions.index') }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Discussions
        </a>
    </div>
@endsection

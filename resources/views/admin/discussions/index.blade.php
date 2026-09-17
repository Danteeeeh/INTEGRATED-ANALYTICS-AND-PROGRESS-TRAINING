@extends('layouts.admin-sms')

@section('title', 'Discussions')
@php
    $activeNav = 'courses';
    $pageTitle = 'Discussions';
    $pageIcon = '<i class="fa-solid fa-comments"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-comments"></i>
            Discussions
        </h2>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3>All Discussions</h3>
            <a href="{{ route('admin.discussions.create') }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                New Discussion
            </a>
        </div>

        <div style="padding: 16px 24px; border-bottom: 1px solid #e5e7eb; background: #fafafa;">
            <form method="GET" action="{{ route('admin.discussions.index') }}" data-filter-form style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Course</label>
                    <select name="course_id">
                        <option value="">All Courses</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name ?? $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Class</label>
                    <select name="class_id">
                        <option value="">All Classes</option>
                        @foreach($classes ?? [] as $class)
                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                {{ $class->code }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="general" {{ request('type') == 'general' ? 'selected' : '' }}>General</option>
                        <option value="academic" {{ request('type') == 'academic' ? 'selected' : '' }}>Academic</option>
                        <option value="qna" {{ request('type') == 'qna' ? 'selected' : '' }}>Q&amp;A</option>
                        <option value="graded" {{ request('type') == 'graded' ? 'selected' : '' }}>Graded</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.78rem; font-weight: 600; color: #4b5563;">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, content...">
                </div>

                <div style="display: flex; gap: 8px;">
                    <button type="submit" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.discussions.index') }}" style="padding: 8px 16px; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <table class="crud-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Course</th>
                    <th>Class</th>
                    <th>Type</th>
                    <th>Posts</th>
                    <th>Pinned</th>
                    <th>Locked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($discussions as $discussion)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #111827;">{{ $discussion->title }}</div>
                            @if($discussion->user)
                                <div style="font-size: 0.75rem; color: #6b7280;">by {{ $discussion->user->name }}</div>
                            @endif
                        </td>
                        <td>{{ $discussion->course->code ?? '-' }} - {{ ($discussion->course->name ?? $discussion->course->title) ?? '-' }}</td>
                        <td>{{ $discussion->class->code ?? '-' }}</td>
                        <td>
                            @php
                                $typeBadge = match($discussion->type) {
                                    'general' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'General'],
                                    'academic' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Academic'],
                                    'qna' => ['bg' => '#fef3c7', 'color' => '#b45309', 'label' => 'Q&amp;A'],
                                    'graded' => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Graded'],
                                    default => ['bg' => '#f3f4f6', 'color' => '#6b7280', 'label' => ucfirst($discussion->type)],
                                };
                            @endphp
                            <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: {{ $typeBadge['bg'] }}; color: {{ $typeBadge['color'] }};">
                                {!! $typeBadge['label'] !!}
                            </span>
                        </td>
                        <td style="text-align: center; font-weight: 600;">{{ ($discussion->posts_count ?? $discussion->posts->count()) ?? 0 }}</td>
                        <td style="text-align: center;">
                            @if($discussion->is_pinned)
                                <span class="badge-active" title="Pinned"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @else
                                <span style="color: #9ca3af; font-size: 0.85rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($discussion->is_locked)
                                <span style="padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; background: #fee2e2; color: #dc2626;" title="Locked">
                                    <i class="fa-solid fa-lock"></i> Locked
                                </span>
                            @else
                                <span style="color: #9ca3af; font-size: 0.85rem;">—</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <form method="POST" action="{{ route('admin.discussions.pin', $discussion) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-icon" title="{{ $discussion->is_pinned ? 'Unpin' : 'Pin' }}" style="background: {{ ($discussion->is_pinned ? '#fef3c7' : '#f3f4f6') }}; color: {{ ($discussion->is_pinned ? '#b45309' : '#6b7280') }};">
                                    <i class="fa-solid fa-thumbtack"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.discussions.lock', $discussion) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn-icon" title="{{ $discussion->is_locked ? 'Unlock' : 'Lock' }}" style="background: {{ ($discussion->is_locked ? '#fee2e2' : '#f3f4f6') }}; color: {{ ($discussion->is_locked ? '#dc2626' : '#6b7280') }};">
                                    <i class="fa-solid fa-lock"></i>
                                </button>
                            </form>
                            <a href="{{ route('admin.discussions.show', $discussion) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.discussions.edit', $discussion) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.discussions.destroy', $discussion) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this discussion?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:32px;color:#aaa;">
                            No discussions found. <a href="{{ route('admin.discussions.create') }}" class="text-blue-600 hover:underline">Create one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(method_exists($discussions, 'hasPages') && $discussions->hasPages())
            <div style="padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                {{ $discussions->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
@endsection

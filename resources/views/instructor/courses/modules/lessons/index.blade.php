@extends('layouts.instructor')

@section('title', 'Lessons — ' . $module->title)
@php
    $activeNav = 'courses';
    $pageTitle = 'Lessons';
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-book-open"></i>
            Lessons — {{ $module->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.modules.show', [$course, $module]) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Module
            </a>
            <a href="{{ route('instructor.courses.modules.lessons.create', [$course, $module]) }}" class="btn-add">
                <i class="fa-solid fa-plus"></i>
                Add Lesson
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-book-open"></i> Lessons ({{ $lessons->total() }})</h3>
        </div>
        <table class="crud-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lessons as $lesson)
                    <tr>
                        <td>{{ $lesson->position ?? $loop->iteration }}</td>
                        <td>{{ $lesson->title }}</td>
                        <td>{{ ucfirst($lesson->lesson_type ?? 'text') }}</td>
                        <td>{{ $lesson->duration_minutes ? $lesson->duration_minutes . ' min' : '—' }}</td>
                        <td>
                            @if($lesson->status === 'published')
                                <span class="badge-published">Published</span>
                            @elseif($lesson->status === 'archived')
                                <span class="badge-draft">Archived</span>
                            @else
                                <span class="badge-draft">Draft</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson]) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="{{ route('instructor.courses.modules.lessons.materials', [$course, $module, $lesson]) }}" class="btn-icon" title="Materials" style="color:#62c9f5;">
                                <i class="fa-solid fa-folder-open"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No lessons yet. <a href="{{ route('instructor.courses.modules.lessons.create', [$course, $module]) }}" style="color:#2563eb;text-decoration:underline;">Add one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

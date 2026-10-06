@extends('layouts.instructor')

@section('title', 'Edit Module')
@php
    $activeNav = 'modules';
    $pageTitle = 'Edit Module';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
    $module->load('attachments.mediaFile');
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Module — {{ $course->code }}
        </h2>
    </div>
@endsection

@section('content')
    <style>
        .breadcrumb-row { margin: 0 24px 16px; display: flex; align-items: center; gap: 8px; font-size: 0.84rem; color: #64748b; }
        .breadcrumb-row a { color: #2563eb; text-decoration: none; }
        .breadcrumb-row a:hover { text-decoration: underline; }
    </style>

    <div class="breadcrumb-row">
        <a href="{{ route('instructor.courses.index') }}">Courses</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="{{ route('instructor.courses.show', $course) }}">{{ $course->code }}</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="{{ route('instructor.courses.modules.index', $course) }}">Modules</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="{{ route('instructor.courses.modules.show', [$course, $module]) }}">{{ Str::limit($module->title, 30) }}</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <span>Edit</span>
    </div>

    <div class="form-card">
        <h3>Edit Module</h3>
        <form method="POST" action="{{ route('instructor.courses.modules.update', [$course, $module]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="form-grid">
                <div class="form-field full">
                    <label>Module Title <span class="req">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Introduction to Programming" value="{{ old('title', $module->title) }}">
                </div>

                <div class="form-field">
                    <label>Order</label>
                    <input type="number" name="order" min="1" placeholder="e.g. 1" value="{{ old('order', $module->order) }}">
                </div>

                <div class="form-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft" {{ old('status', $module->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="published" {{ old('status', $module->status) == 'published' ? 'selected' : '' }}>Published</option>
                        <option value="archived" {{ old('status', $module->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>

                <div class="form-field full">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Describe what students will learn in this module...">{{ old('description', $module->description) }}</textarea>
                </div>

                <div class="form-field full">
                    <label>Objectives / Learning Outcomes</label>
                    <textarea name="objectives" rows="3" placeholder="List the key learning objectives...">{{ old('objectives', $module->objectives) }}</textarea>
                </div>

                <div class="form-field full">
                    <label>External Video URL (Optional)</label>
                    <input type="url" name="external_video_url" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('external_video_url', $module->external_video_url) }}">
                    <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">YouTube, Vimeo, or other video platform URLs are supported</span>
                    @error('external_video_url')<span style="color: #ef4444; font-size: 0.8rem; display: block; margin-top: 4px;">{{ $message }}</span>@enderror
                </div>

                <div class="form-field full">
                    <label>Custom Thumbnail URL (Optional)</label>
                    <input type="url" name="video_thumbnail_url" placeholder="https://example.com/thumbnail.jpg" value="{{ old('video_thumbnail_url', $module->video_thumbnail_url) }}">
                    <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">Leave blank to auto-generate thumbnail from YouTube. For other platforms, provide a custom thumbnail image URL.</span>
                    @error('video_thumbnail_url')<span style="color: #ef4444; font-size: 0.8rem; display: block; margin-top: 4px;">{{ $message }}</span>@enderror
                </div>

                <div class="form-field full">
                    <label>Upload Additional Files (Optional)</label>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.zip,.txt">
                    <span style="font-size: 0.8rem; color: #64748b; margin-top: 4px; display: block;">Accepted formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, MP4, MP3, ZIP, TXT (Max 10MB per file)</span>
                    @if($module->attachments->count() > 0)
                        <div style="margin-top: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                            <strong style="font-size: 0.85rem; color: #1e293b;">Current Attachments:</strong>
                            <div style="margin-top: 8px; display: grid; gap: 8px;">
                                @foreach($module->attachments as $attachment)
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #fff; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-file" style="color: #64748b;"></i>
                                            <span style="font-size: 0.85rem; color: #1e293b;">{{ $attachment->title ?? $attachment->mediaFile->file_name }}</span>
                                            <span style="font-size: 0.75rem; color: #64748b;">({{ $attachment->mediaFile->extension ?? '—' }})</span>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="{{ $attachment->mediaFile->url }}" target="_blank" style="color: #3b82f6; text-decoration: none; font-size: 0.8rem;">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <form method="POST" action="{{ route('instructor.courses.modules.attachments.delete', [$course, $module, $attachment]) }}" onsubmit="return confirm('Delete this attachment?');" style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 0.8rem;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="form-submit" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="{{ route('instructor.courses.modules.show', [$course, $module]) }}" class="btn-modal-cancel" style="padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; text-decoration: none;">Cancel</a>
                <button type="submit" class="btn-submit">Save Changes</button>
            </div>
        </form>
    </div>
@endsection

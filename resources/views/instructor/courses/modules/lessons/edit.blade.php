@extends('layouts.instructor')

@section('title', 'Edit Lesson — ' . $lesson->title)
@php
    $activeNav = 'lessons';
    $pageTitle = 'Edit Lesson';
    $pageIcon = '<i class="fa-solid fa-pen-to-square"></i>';
    $lesson->load('materials');
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Lesson — {{ $lesson->title }}
        </h2>
        <div class="page-actions">
            <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Lesson
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="crud-card course-form-card">
        <div class="crud-header">
            <h3><i class="fa-solid fa-book-open"></i> Lesson Information</h3>
        </div>
        <form method="POST" action="{{ route('instructor.courses.modules.lessons.update', [$course, $module, $lesson]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-section" style="padding: 24px;">
                <div class="modal-grid">
                    <div class="modal-row">
                        <label>Lesson Title <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required class="form-input">
                        @error('title')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Lesson Type <span style="color:#dc2626;">*</span></label>
                        <select name="lesson_type" required class="form-input">
                            @foreach($lessonTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('lesson_type', $lesson->lesson_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('lesson_type')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $lesson->duration_minutes) }}" min="0" class="form-input">
                        @error('duration_minutes')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="modal-row">
                        <label>Status <span style="color:#dc2626;">*</span></label>
                        <select name="status" required class="form-input">
                            <option value="draft" {{ old('status', $lesson->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $lesson->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status', $lesson->status) == 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Video Reference / External URL</label>
                    <input type="url" name="external_url" value="{{ old('external_url', $lesson->external_url) }}" class="form-input" placeholder="https://youtube.com/watch?v=...">
                    <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;margin-top:4px;">
                        Add a video link (YouTube, Vimeo, etc.) or external resource for students. Use this for video lessons or supplementary materials.
                    </span>
                    @error('external_url')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-input" style="width: 100%; min-width: 100%;">{{ old('description', $lesson->description) }}</textarea>
                    @error('description')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Objectives</label>
                    <textarea name="objectives" rows="3" class="form-input" style="width: 100%; min-width: 100%;">{{ old('objectives', $lesson->objectives) }}</textarea>
                    @error('objectives')<span class="error-message">{{ $message }}</span>@enderror
                </div>

                <div class="modal-row" style="margin-top:14px;">
                    <label>Content</label>
                    <textarea name="content" rows="6" class="form-input" style="width: 100%; min-width: 100%;">{{ old('content', $lesson->content) }}</textarea>
                    @error('content')<span class="error-message">{{ $message }}</span>@enderror
                </div>
            </div>

            @php
                $crRules = is_array($lesson->completion_rules) ? $lesson->completion_rules : [];
                $crContent = (bool) ($crRules['require_content_view'] ?? false);
                $crMaterials = (bool) ($crRules['require_all_materials'] ?? false);
                $crMinutes = (int) ($crRules['min_minutes'] ?? 0);
            @endphp
            <div class="modal-section" style="padding: 24px; border-top: 1px solid var(--lms-border, rgba(148,174,222,.18));">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="fa-solid fa-clipboard-check" style="color:var(--lms-accent,#62c9f5);"></i>
                    <label style="font-size:.95rem;font-weight:800;margin:0;">Completion Rules</label>
                </div>
                <p style="margin:0 0 14px;color:var(--dash-muted,#9eafca);font-size:.78rem;">
                    Requirements the student must meet before they can mark this lesson complete. Leave all unchecked for no requirements.
                </p>

                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;cursor:pointer;">
                    <input type="checkbox" name="cr_require_content_view" value="1" {{ old('cr_require_content_view', $crContent) ? 'checked' : '' }} style="margin-top:2px;width:16px;height:16px;">
                    <span>
                        <strong style="font-size:.85rem;">Require opening the lesson content</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Student must open this lesson page before completing.</span>
                    </span>
                </label>

                <label style="display:flex;align-items:flex-start;gap:10px;padding:10px 0;cursor:pointer;border-top:1px solid var(--lms-border, rgba(148,174,222,.18));">
                    <input type="checkbox" name="cr_require_all_materials" value="1" {{ old('cr_require_all_materials', $crMaterials) ? 'checked' : '' }} style="margin-top:2px;width:16px;height:16px;">
                    <span>
                        <strong style="font-size:.85rem;">Require reviewing all materials</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Student must open every attached material at least once.</span>
                    </span>
                </label>

                <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-top:1px solid var(--lms-border, rgba(148,174,222,.18));">
                    <input type="number" name="cr_min_minutes" value="{{ old('cr_min_minutes', $crMinutes) }}" min="0" max="600" class="form-input" style="width:100px;" placeholder="0">
                    <span>
                        <strong style="font-size:.85rem;display:block;">Minimum time on lesson (minutes)</strong>
                        <span style="display:block;color:var(--dash-muted,#9eafca);font-size:.75rem;">Leave 0 for no time requirement.</span>
                    </span>
                </div>
            </div>

            <div class="modal-section" style="padding: 24px; border-top: 1px solid var(--lms-border, rgba(148,174,222,.18));">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <i class="fa-solid fa-paperclip" style="color:var(--lms-accent,#62c9f5);"></i>
                    <label style="font-size:.95rem;font-weight:800;margin:0;">Lesson Materials (Optional)</label>
                </div>
                <p style="margin:0 0 14px;color:var(--dash-muted,#9eafca);font-size:.78rem;">
                    Upload files to attach to this lesson. Students can download these materials.
                </p>

                @if($lesson->materials->count() > 0)
                    <div style="margin-bottom: 16px; padding: 12px; background: rgba(98,201,245,.08); border-radius: 8px; border: 1px solid rgba(148,174,222,.2);">
                        <strong style="font-size: 0.85rem; color: var(--dash-text, #eef4ff);">Current Materials:</strong>
                        <div style="margin-top: 8px; display: grid; gap: 8px;">
                            @foreach($lesson->materials as $material)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: rgba(0,0,0,.2); border-radius: 6px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-file" style="color: var(--dash-muted, #9eafca);"></i>
                                        <span style="font-size: 0.85rem; color: var(--dash-text, #eef4ff);">{{ $material->pivot->title ?? $material->file_name }}</span>
                                        <span style="font-size: 0.75rem; color: var(--dash-muted, #9eafca);">({{ $material->extension ?? '—' }})</span>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        {{-- $lesson->materials is a belongsToMany to MediaFile,
                                             so $material is already the MediaFile. Passing
                                             $material->pivot->mediaFile resolved to null and
                                             broke route('files.download'). --}}
                                        <a href="{{ route('files.download', $material->id) }}" target="_blank" style="color: #62c9f5; text-decoration: none; font-size: 0.8rem;">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                        <form method="POST" action="{{ route('instructor.courses.modules.lessons.materials.delete', [$course, $module, $lesson, $material->pivot->id ?? $material->id]) }}" onsubmit="return confirm('Delete this material?');" style="display: inline;">
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

                <div id="dropZone" style="border: 2px dashed var(--lms-border, rgba(148,174,222,.3)); border-radius: 12px; padding: 32px; text-align: center; background: rgba(98,201,245,.04); cursor: pointer; transition: all 0.3s ease;">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: var(--lms-accent, #62c9f5); margin-bottom: 12px;"></i>
                    <p style="margin: 0; color: var(--dash-text, #eef4ff); font-size: 0.9rem; font-weight: 600;">
                        Drag & drop files here or click to browse
                    </p>
                    <p style="margin: 8px 0 0; color: var(--dash-muted, #9eafca); font-size: 0.75rem;">
                        PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, MP4, MP3, ZIP, TXT (Max 10MB per file)
                    </p>
                    <input type="file" name="materials[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.zip,.txt" id="fileInput" style="display: none;">
                </div>

                <div id="fileList" style="margin-top: 16px; display: none;">
                    <strong style="font-size: 0.85rem; color: var(--dash-text, #eef4ff);">Selected Files:</strong>
                    <ul id="selectedFiles" style="margin: 8px 0 0 0; padding-left: 20px; font-size: 0.85rem; color: var(--dash-muted, #9eafca);"></ul>
                </div>
            </div>

            <div class="modal-footer" style="justify-content:flex-end; gap:10px; padding: 18px 24px;">
                <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn-modal-cancel">Cancel</a>
                <button type="submit" class="btn-modal-save">
                    <i class="fa-solid fa-save"></i>
                    Update Lesson
                </button>
            </div>
        </form>
    </div>

    <script>
        // Drag and drop file upload
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const selectedFiles = document.getElementById('selectedFiles');

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#62c9f5';
            dropZone.style.background = 'rgba(98,201,245,.12)';
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'rgba(148,174,222,.3)';
            dropZone.style.background = 'rgba(98,201,245,.04)';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'rgba(148,174,222,.3)';
            dropZone.style.background = 'rgba(98,201,245,.04)';
            
            const files = e.dataTransfer.files;
            fileInput.files = files;
            updateFileList();
        });

        fileInput.addEventListener('change', updateFileList);

        function updateFileList() {
            if (fileInput.files.length > 0) {
                fileList.style.display = 'block';
                selectedFiles.innerHTML = '';
                Array.from(fileInput.files).forEach(file => {
                    const li = document.createElement('li');
                    li.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    selectedFiles.appendChild(li);
                });
            } else {
                fileList.style.display = 'none';
            }
        }
    </script>
@endsection

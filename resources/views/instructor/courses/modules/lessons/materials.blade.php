@extends('layouts.instructor')

@section('title', 'Lesson Materials')
@php
    $activeNav = 'courses';
    $pageTitle = 'Lesson Materials';
    $pageIcon = '<i class="fa-solid fa-folder-open"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-folder-open"></i>
            {{ $lesson->title }} - Materials
        </h2>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('instructor.courses.modules.lessons.index', [$course, $module]) }}" 
               class="btn-modal-cancel" style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Back to Lessons
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Upload Area -->
    <div class="form-card">
        <h3><i class="fa-solid fa-cloud-upload-alt"></i> Upload Materials</h3>
        
        <!-- Drag and Drop Zone -->
        <div id="dropZone" 
             style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; cursor: pointer; transition: all 0.3s; background: #f8fafc;"
             ondragover="handleDragOver(event)" 
             ondragleave="handleDragLeave(event)" 
             ondrop="handleDrop(event)"
             onclick="document.getElementById('fileInput').click()">
            
            <div id="dropZoneContent">
                <i class="fa-solid fa-cloud-upload-alt" style="font-size: 3rem; color: #94a3b8; margin-bottom: 16px;"></i>
                <div style="font-size: 1.1rem; font-weight: 600; color: #475569; margin-bottom: 8px;">
                    Drag & Drop files here
                </div>
                <div style="font-size: 0.9rem; color: #64748b; margin-bottom: 16px;">
                    or click to browse
                </div>
                <div style="font-size: 0.8rem; color: #94a3b8;">
                    Supported formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, JPG, PNG, MP4, MP3, ZIP (Max 10MB per file)
                </div>
            </div>
            
            <div id="uploadingContent" style="display: none;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 3rem; color: #3b82f6; margin-bottom: 16px;"></i>
                <div style="font-size: 1.1rem; font-weight: 600; color: #475569;">
                    Uploading files...
                </div>
                <div id="uploadProgress" style="margin-top: 12px;">
                    <div style="background: #e2e8f0; border-radius: 4px; height: 8px; overflow: hidden;">
                        <div id="progressBar" style="background: #3b82f6; height: 100%; width: 0%; transition: width 0.3s;"></div>
                    </div>
                    <div id="progressText" style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">0%</div>
                </div>
            </div>
        </div>
        
        <input type="file" id="fileInput" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.zip,.txt" style="display: none;" onchange="handleFileSelect(event)">
        
        <!-- Material Details Form -->
        <div id="materialForm" style="display: none; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
            <h4 style="margin-bottom: 16px;"><i class="fa-solid fa-info-circle"></i> Material Details</h4>
            
            <div class="modal-row">
                <span>Title:</span>
                <input type="text" id="materialTitle" placeholder="Material title" 
                       style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            
            <div class="modal-row">
                <span>Description:</span>
                <textarea id="materialDescription" placeholder="Optional description" rows="3"
                          style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;"></textarea>
            </div>
            
            <div class="modal-row">
                <span>Position:</span>
                <input type="number" id="materialPosition" value="{{ $lesson->materials->count() + 1 }}" min="1"
                       style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            
            <div class="modal-row">
                <span>Required:</span>
                <label style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="materialRequired" style="width: 18px; height: 18px;">
                    <span>Students must complete this material</span>
                </label>
            </div>
            
            <div class="modal-row">
                <span>Access Until:</span>
                <input type="datetime-local" id="materialAccessUntil" 
                       style="flex: 1; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="addMaterial()" class="btn-add" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-plus"></i> Add Material
                </button>
                <button type="button" onclick="cancelUpload()" class="btn-modal-cancel" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Existing Materials -->
    <div class="form-card">
        <h3><i class="fa-solid fa-list"></i> Existing Materials ({{ $lesson->materials->count() }})</h3>
        
        @if($lesson->materials->isNotEmpty())
            <div style="margin-top: 16px;">
                @foreach($lesson->materials as $material)
                    <div class="material-item" 
                         style="display: flex; align-items: center; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; background: white;">
                        
                        <!-- File Icon -->
                        <div style="margin-right: 16px;">
                            @if($material->mediaFile)
                                @if(in_array($material->mediaFile->extension, ['jpg', 'jpeg', 'png', 'gif']))
                                    <i class="fa-solid fa-file-image" style="font-size: 2rem; color: #3b82f6;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['pdf']))
                                    <i class="fa-solid fa-file-pdf" style="font-size: 2rem; color: #ef4444;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['doc', 'docx']))
                                    <i class="fa-solid fa-file-word" style="font-size: 2rem; color: #2563eb;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['ppt', 'pptx']))
                                    <i class="fa-solid fa-file-powerpoint" style="font-size: 2rem; color: #ea580c;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['xls', 'xlsx']))
                                    <i class="fa-solid fa-file-excel" style="font-size: 2rem; color: #16a34a;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['mp4', 'mov', 'avi']))
                                    <i class="fa-solid fa-file-video" style="font-size: 2rem; color: #8b5cf6;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['mp3', 'wav']))
                                    <i class="fa-solid fa-file-audio" style="font-size: 2rem; color: #ec4899;"></i>
                                @elseif(in_array($material->mediaFile->extension, ['zip', 'rar']))
                                    <i class="fa-solid fa-file-archive" style="font-size: 2rem; color: #f59e0b;"></i>
                                @else
                                    <i class="fa-solid fa-file" style="font-size: 2rem; color: #64748b;"></i>
                                @endif
                            @else
                                <i class="fa-solid fa-file" style="font-size: 2rem; color: #64748b;"></i>
                            @endif
                        </div>
                        
                        <!-- Material Info -->
                        <div style="flex: 1;">
                            <div style="font-weight: 600; color: #1e293b;">
                                {{ $material->title ?? $material->mediaFile->file_name ?? 'Untitled' }}
                                @if($material->is_required)
                                    <span style="background: #fef3c7; color: #d97706; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; margin-left: 8px;">
                                        Required
                                    </span>
                                @endif
                            </div>
                            @if($material->description)
                                <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
                                    {{ $material->description }}
                                </div>
                            @endif
                            @if($material->mediaFile)
                                <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 4px;">
                                    {{ $material->mediaFile->file_name }} • {{ \App\Helpers\FileHelper::formatFileSize($material->mediaFile->file_size) }}
                                </div>
                            @endif
                            @if($material->access_until)
                                <div style="font-size: 0.8rem; color: #ef4444; margin-top: 4px;">
                                    <i class="fa-solid fa-clock"></i> Access until: {{ $material->access_until->format('M d, Y g:i A') }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Actions -->
                        <div style="display: flex; gap: 8px; margin-left: 16px;">
                            @if($material->mediaFile)
                                <a href="{{ route('files.download', $material->mediaFile) }}" 
                                   class="btn-modal-cancel" style="padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem;">
                                    <i class="fa-solid fa-download"></i>
                                </a>
                            @endif
                            <button type="button" onclick="editMaterial({{ $material->id }})" 
                                    class="btn-modal-cancel" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                            <button type="button" onclick="deleteMaterial({{ $material->id }})" 
                                    class="btn-delete" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;"
                                    onclick="return confirm('Are you sure you want to delete this material?');">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="padding: 40px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                <i class="fa-solid fa-folder-open" style="font-size: 3rem; margin-bottom: 16px;"></i>
                <div style="font-size: 1.1rem;">No materials uploaded yet</div>
                <div style="font-size: 0.9rem; margin-top: 8px;">Upload your first material using the form above</div>
            </div>
        @endif
    </div>

    <!-- Bulk Actions -->
    @if($lesson->materials->isNotEmpty())
        <div class="form-card">
            <h3><i class="fa-solid fa-tools"></i> Bulk Actions</h3>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" onclick="reorderMaterials()" class="btn-add" style="padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-sort"></i> Reorder All
                </button>
                <button type="button" onclick="setAllRequired()" class="btn-modal-cancel" style="padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-check-double"></i> Set All Required
                </button>
                <button type="button" onclick="setAllOptional()" class="btn-modal-cancel" style="padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                    <i class="fa-solid fa-square"></i> Set All Optional
                </button>
            </div>
        </div>
    @endif

    <script>
        let uploadedFiles = [];
        let currentMaterialId = null;

        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('dropZone').style.borderColor = '#3b82f6';
            document.getElementById('dropZone').style.background = '#eff6ff';
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('dropZone').style.borderColor = '#cbd5e1';
            document.getElementById('dropZone').style.background = '#f8fafc';
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('dropZone').style.borderColor = '#cbd5e1';
            document.getElementById('dropZone').style.background = '#f8fafc';
            
            const files = e.dataTransfer.files;
            handleFiles(files);
        }

        function handleFileSelect(e) {
            const files = e.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length === 0) return;
            
            uploadedFiles = Array.from(files);
            showMaterialForm();
            
            // Auto-fill title with first file name
            if (uploadedFiles.length === 1) {
                document.getElementById('materialTitle').value = uploadedFiles[0].name.replace(/\.[^/.]+$/, "");
            }
        }

        function showMaterialForm() {
            document.getElementById('dropZoneContent').style.display = 'none';
            document.getElementById('materialForm').style.display = 'block';
        }

        function cancelUpload() {
            uploadedFiles = [];
            document.getElementById('materialForm').style.display = 'none';
            document.getElementById('dropZoneContent').style.display = 'block';
            document.getElementById('fileInput').value = '';
            document.getElementById('materialTitle').value = '';
            document.getElementById('materialDescription').value = '';
            document.getElementById('materialAccessUntil').value = '';
        }

        async function addMaterial() {
            const title = document.getElementById('materialTitle').value;
            const description = document.getElementById('materialDescription').value;
            const position = document.getElementById('materialPosition').value;
            const required = document.getElementById('materialRequired').checked;
            const accessUntil = document.getElementById('materialAccessUntil').value;

            if (!title) {
                alert('Please enter a title for the material');
                return;
            }

            if (uploadedFiles.length === 0) {
                alert('Please select at least one file');
                return;
            }

            // Show uploading state
            document.getElementById('dropZoneContent').style.display = 'none';
            document.getElementById('uploadingContent').style.display = 'block';

            const formData = new FormData();
            uploadedFiles.forEach(file => {
                formData.append('files[]', file);
            });
            formData.append('folder', 'lesson_materials');
            formData.append('public', 'false');
            formData.append('uploadable_type', 'App\\Models\\Lesson');
            formData.append('uploadable_id', '{{ $lesson->id }}');

            try {
                const response = await fetch('/api/files/upload-multiple', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Create lesson materials
                    for (const fileData of result.data) {
                        await fetch('/instructor/lessons/{{ $lesson->id }}/materials', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                media_file_id: fileData.id,
                                title: title,
                                description: description,
                                position: position,
                                is_required: required,
                                access_until: accessUntil || null,
                            })
                        });
                    }

                    alert('Materials uploaded successfully!');
                    location.reload();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('Error uploading files: ' + error.message);
                cancelUpload();
            }
        }

        function editMaterial(materialId) {
            // Implement edit functionality
            alert('Edit functionality for material ' + materialId);
        }

        async function deleteMaterial(materialId) {
            if (!confirm('Are you sure you want to delete this material?')) return;

            try {
                const response = await fetch('/instructor/lessons/{{ $lesson->id }}/materials/' + materialId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                if (response.ok) {
                    alert('Material deleted successfully');
                    location.reload();
                } else {
                    throw new Error('Failed to delete material');
                }
            } catch (error) {
                alert('Error deleting material: ' + error.message);
            }
        }

        function reorderMaterials() {
            alert('Drag and drop reordering will be implemented with a sortable library');
        }

        async function setAllRequired() {
            if (!confirm('Set all materials as required?')) return;

            try {
                const response = await fetch('/instructor/lessons/{{ $lesson->id }}/materials/set-all-required', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                if (response.ok) {
                    alert('All materials set as required');
                    location.reload();
                }
            } catch (error) {
                alert('Error updating materials: ' + error.message);
            }
        }

        async function setAllOptional() {
            if (!confirm('Set all materials as optional?')) return;

            try {
                const response = await fetch('/instructor/lessons/{{ $lesson->id }}/materials/set-all-optional', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });

                if (response.ok) {
                    alert('All materials set as optional');
                    location.reload();
                }
            } catch (error) {
                alert('Error updating materials: ' + error.message);
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Update file icons based on FileHelper
        function getFileIcon(extension) {
            const iconMap = {
                'jpg': 'fa-file-image', 'jpeg': 'fa-file-image', 'png': 'fa-file-image', 'gif': 'fa-file-image',
                'pdf': 'fa-file-pdf',
                'doc': 'fa-file-word', 'docx': 'fa-file-word',
                'xls': 'fa-file-excel', 'xlsx': 'fa-file-excel',
                'ppt': 'fa-file-powerpoint', 'pptx': 'fa-file-powerpoint',
                'mp4': 'fa-file-video', 'avi': 'fa-file-video', 'mov': 'fa-file-video',
                'mp3': 'fa-file-audio', 'wav': 'fa-file-audio',
                'zip': 'fa-file-archive', 'rar': 'fa-file-archive'
            };
            return iconMap[extension.toLowerCase()] || 'fa-file';
        }
    </script>
@endsection
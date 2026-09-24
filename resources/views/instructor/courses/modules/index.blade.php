@extends('layouts.instructor')

@section('title', 'Modules — ' . $course->name)
@php
    $activeNav = 'courses';
    $pageTitle = 'Modules';
    $pageIcon = '<i class="fa-solid fa-layer-group"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group"></i>
            {{ $course->code }} — Modules
        </h2>
    </div>
@endsection

@section('content')
    <style>
        .breadcrumb-row { margin: 0 24px 16px; display: flex; align-items: center; gap: 8px; font-size: 0.84rem; color: #64748b; }
        .breadcrumb-row a { color: #2563eb; text-decoration: none; }
        .breadcrumb-row a:hover { text-decoration: underline; }
        .module-row { display: flex; align-items: center; gap: 10px; }
        .drag-handle { cursor: grab; color: #94a3b8; padding: 4px; }
        .drag-handle:hover { color: #2563eb; }
        .publish-btn { padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; border: none; cursor: pointer; transition: all 0.2s; }
        .publish-btn.published { background: #dcfce7; color: #16a34a; }
        .publish-btn.draft { background: #fee2e2; color: #dc2626; }
    </style>

    <div class="breadcrumb-row">
        <a href="{{ route('instructor.courses.index') }}">Courses</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="{{ route('instructor.courses.show', $course) }}">{{ $course->code }}</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <span>Modules</span>
    </div>

    <form method="POST" action="{{ route('instructor.courses.modules.reorder', $course) }}" id="reorderForm">
        @csrf
        <input type="hidden" name="order" id="orderInput">
    </form>

    <div class="crud-card">
        <div class="crud-header">
            <h3>Course Modules ({{ $modules->count() }})</h3>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="saveOrder()" class="btn-add" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
                    <i class="fa-solid fa-arrow-up-short-wide"></i>
                    Save Order
                </button>
                <a href="{{ route('instructor.courses.modules.create', $course) }}" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Create Module
                </a>
            </div>
        </div>

        <table class="crud-table" id="modulesTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th style="width: 60px;">Order</th>
                    <th>Title</th>
                    <th>Lessons</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="modulesTbody">
                @forelse($modules as $module)
                    <tr data-id="{{ $module->id }}">
                        <td><i class="fa-solid fa-grip-vertical drag-handle"></i></td>
                        <td class="order-num">{{ $loop->iteration }}</td>
                        <td>
                            <div class="module-row">
                                <i class="fa-solid fa-layer-group" style="color:#2563eb;"></i>
                                <div>
                                    <div style="font-weight:600;">{{ $module->title }}</div>
                                    @if($module->description)
                                        <div style="font-size:0.78rem;color:#64748b;margin-top:2px;">{{ Str::limit($module->description, 80) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $module->lessons->count() ?? 0 }}</td>
                        <td>
                            <form method="POST" action="{{ route('instructor.courses.modules.' . ($module->is_published ? 'unpublish' : 'publish'), [$course, $module]) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="publish-btn {{ $module->is_published ? 'published' : 'draft' }}">
                                    {{ $module->is_published ? 'Published' : 'Draft' }}
                                </button>
                            </form>
                        </td>
                        <td class="actions-cell">
                            <a href="{{ route('instructor.courses.modules.show', [$course, $module]) }}" class="btn-icon btn-view" title="View">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('instructor.courses.modules.edit', [$course, $module]) }}" class="btn-icon btn-edit" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('instructor.courses.modules.destroy', [$course, $module]) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this module?');">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:24px;color:#aaa;">
                            No modules yet. <a href="{{ route('instructor.courses.modules.create', $course) }}" style="color:#2563eb;text-decoration:underline;">Create the first module</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin: 24px;">
        <a href="{{ route('instructor.courses.show', $course) }}" class="btn-modal-cancel" style="display: inline-flex; align-items: center; justify-content: center; padding: 11px 32px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: background 0.2s; text-decoration: none;">
            ← Back to Course
        </a>
    </div>
@endsection

@push('scripts')
<script>
function saveOrder() {
    const ids = Array.from(document.querySelectorAll('#modulesTbody tr')).map(tr => tr.dataset.id);
    document.getElementById('orderInput').value = ids.join(',');
    document.getElementById('reorderForm').submit();
}

document.querySelectorAll('.drag-handle').forEach(handle => {
    handle.addEventListener('dragstart', function(e) {
        const tr = this.closest('tr');
        tr.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', tr.dataset.id);
    });
    handle.addEventListener('dragend', function() {
        document.querySelectorAll('#modulesTbody tr').forEach(tr => tr.classList.remove('dragging'));
        updateOrderNums();
    });
    handle.setAttribute('draggable', 'true');
});

document.querySelectorAll('#modulesTbody tr').forEach(tr => {
    tr.addEventListener('dragover', function(e) { e.preventDefault(); });
    tr.addEventListener('drop', function(e) {
        e.preventDefault();
        const draggingId = e.dataTransfer.getData('text/plain');
        const dragging = document.querySelector(`tr[data-id="${draggingId}"]`);
        if (dragging && dragging !== this) {
            const tbody = document.getElementById('modulesTbody');
            const rect = this.getBoundingClientRect();
            const midpoint = rect.top + rect.height / 2;
            if (e.clientY < midpoint) {
                tbody.insertBefore(dragging, this);
            } else {
                tbody.insertBefore(dragging, this.nextSibling);
            }
            updateOrderNums();
        }
    });
});

function updateOrderNums() {
    document.querySelectorAll('#modulesTbody tr').forEach((tr, idx) => {
        const cell = tr.querySelector('.order-num');
        if (cell) cell.textContent = idx + 1;
    });
}
</script>
@endpush

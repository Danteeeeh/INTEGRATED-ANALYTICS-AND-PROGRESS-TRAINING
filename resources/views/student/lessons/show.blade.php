@extends('layouts.student')
@section('title', $lesson->title)
@php $activeNav = 'lessons'; @endphp

@section('content')
<div class="learning-shell">
    <x-user-page-header
        title="{{ $lesson->title }}"
        subtitle="{{ $course->title }} · {{ $module->title }}"
        icon="fa-book-open"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $progress->status }}" />
            <span class="user-status">{{ round($progress->progress_percent ?? 0) }}% complete</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('student.courses.modules.show', [$course, $module]) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Module</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book-open"></i> Lesson Content</h3>
            @if($lesson->duration_minutes)
                <span class="user-email">{{ $lesson->duration_minutes }} min</span>
            @endif
        </div>
        <div class="user-panel-body">
            @if($lesson->description)
                <div style="margin-bottom:12px;color:var(--dash-muted)">{{ $lesson->description }}</div>
            @endif
            @if($lesson->content)
                <div style="white-space:pre-line">{{ $lesson->content }}</div>
            @else
                <p class="user-email">This lesson has no text content.</p>
            @endif
            @if($lesson->external_url)
                <div class="user-actions" style="justify-content:flex-start;margin-top:14px">
                    <a href="{{ $lesson->external_url }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><i class="fa-solid fa-up-right-from-square"></i> Open External Lesson</a>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-paperclip"></i> Materials</h3>
            <span class="user-status">{{ $lesson->lessonMaterials->count() }} materials</span>
        </div>
        <div class="user-panel-body">
            @if($lesson->lessonMaterials->count() > 0)
                <div class="user-actions" style="justify-content:flex-start;flex-wrap:wrap">
                    @foreach($lesson->lessonMaterials as $material)
                        <a href="{{ $material->mediaFile?->url ?? $material->mediaFile?->path ?? '#' }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm lesson-material-link" data-material-id="{{ $material->id }}">
                            <i class="fa-solid fa-file"></i> {{ $material->title ?? $material->mediaFile?->original_name ?? $material->mediaFile?->file_name ?? 'Material' }}
                            @if($material->is_required)<span class="user-status active">Required</span>@endif
                        </a>
                    @endforeach
                </div>
            @else
                <x-user-empty-state icon="fa-paperclip" title="No materials" description="No materials have been attached to this lesson." />
            @endif
        </div>
    </div>

    @if($hasRules && $progress->status !== 'completed')
        <div class="user-panel" id="completion-checklist-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-clipboard-check"></i> Completion Checklist</h3>
                <span class="user-status {{ $canComplete ? 'active' : '' }}" id="checklist-status">{{ ($canComplete ? 'Requirements met' : 'Requirements pending') }}</span>
            </div>
            <div class="user-panel-body" id="completion-checklist-body">
                <ul style="list-style:none;margin:0;padding:0;display:grid;gap:10px;">
                    @foreach($checklist as $item)
                        <li class="completion-rule-item" data-rule-key="{{ $item['key'] }}" style="display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid var(--lms-border, rgba(148,174,222,.18));border-radius:10px;background:rgba(77,143,240,.03);">
                            <span class="rule-check" style="flex:none;width:22px;height:22px;display:grid;place-items:center;border-radius:50%;font-size:.7rem;{{ $item['met'] ? 'background:rgba(16,185,129,.15);color:#34d399;' : 'background:rgba(148,163,184,.15);color:var(--dash-muted,#9eafca);' }}">
                                <i class="fa-solid {{ $item['met'] ? 'fa-check' : 'fa-circle' }}"></i>
                            </span>
                            <span style="min-width:0">
                                <strong style="display:block;font-size:.84rem;">{{ $item['label'] }}</strong>
                                <span style="color:var(--dash-muted,#9eafca);font-size:.76rem;" class="rule-detail">{{ $item['detail'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if($progress->status === 'completed')
        <div class="learning-next-action">
            <div>
                <strong>Completed — you can review anytime</strong>
                <span>This lesson is marked complete.</span>
            </div>
            <button type="button" class="btn btn-primary" disabled style="opacity:.7;">
                <i class="fa-solid fa-circle-check"></i> Completed
            </button>
        </div>
    @else
        <form action="{{ route('student.courses.modules.lessons.complete', [$course, $module, $lesson]) }}" method="POST" id="complete-lesson-form">
            @csrf
            <div class="learning-next-action">
                <div>
                    <strong>{{ ($canComplete ? 'Finished this lesson?' : 'Requirements not yet met') }}</strong>
                    <span id="complete-hint">
                        @if($hasRules)
                            {{ $canComplete ? 'Mark it complete to update your progress.' : 'Complete the checklist above to unlock completion.' }}
                        @else
                            Mark it complete to update your progress.
                        @endif
                    </span>
                </div>
                <button type="submit" class="btn btn-primary" id="complete-lesson-btn" {{ $canComplete ? '' : 'disabled' }} style="{{ $canComplete ? '' : 'opacity:.6;cursor:not-allowed;' }}">
                    <i class="fa-solid fa-check-circle"></i> Mark Complete
                </button>
            </div>
        </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const completeBtn = document.getElementById('complete-lesson-btn');
    const statusBadge = document.getElementById('checklist-status');
    const checklistBody = document.getElementById('completion-checklist-body');

    function refreshChecklist() {
        if (!checklistBody) return;
        fetch(window.location.pathname + '/checklist', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
            .then(function (data) {
                if (completeBtn) {
                    completeBtn.disabled = !data.canComplete;
                    completeBtn.style.opacity = data.canComplete ? '' : '.6';
                    completeBtn.style.cursor = data.canComplete ? '' : 'not-allowed';
                }
                if (statusBadge) {
                    statusBadge.textContent = data.canComplete ? 'Requirements met' : 'Requirements pending';
                    statusBadge.classList.toggle('active', data.canComplete);
                }
                document.querySelectorAll('.completion-rule-item').forEach(function (li) {
                    const key = li.dataset.ruleKey;
                    const item = (data.checklist || []).find(function (r) { return r.key === key; });
                    if (!item) return;
                    const check = li.querySelector('.rule-check');
                    const detail = li.querySelector('.rule-detail');
                    if (check) {
                        check.innerHTML = '<i class="fa-solid ' + (item.met ? 'fa-check' : 'fa-circle') + '"></i>';
                        check.style.background = item.met ? 'rgba(16,185,129,.15)' : 'rgba(148,163,184,.15)';
                        check.style.color = item.met ? '#34d399' : 'var(--dash-muted,#9eafca)';
                    }
                    if (detail) detail.textContent = item.detail;
                });
                const hint = document.getElementById('complete-hint');
                if (hint) hint.textContent = data.canComplete ? 'Mark it complete to update your progress.' : 'Complete the checklist above to unlock completion.';
            })
            .catch(function () { /* silent — the checklist is non-blocking */ });
    }

    document.querySelectorAll('.lesson-material-link').forEach(function (link) {
        link.addEventListener('click', function () {
            const materialId = link.dataset.materialId;
            if (!materialId) return;
            fetch(window.location.pathname + '/materials/' + materialId + '/accessed', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(res); })
                .then(function (data) {
                    if (data && data.checklist) refreshChecklist();
                })
                .catch(function () { /* non-blocking */ });
        });
    });
})();
</script>
@endpush
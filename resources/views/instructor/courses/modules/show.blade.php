@extends('layouts.instructor')

@section('title', $module->title)
@php $activeNav = 'courses'; @endphp

@section('content')
<div class="user-page module-detail-page">
    <x-user-page-header
        :title="$module->title"
        subtitle="Manage this module’s lessons, content, and publishing state."
        icon="fa-layer-group"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.modules.edit', [$course, $module]) }}" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Edit Module
            </a>
            <a href="{{ route('instructor.courses.modules.index', $course) }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Modules
            </a>
        </x-slot>
    </x-user-page-header>

    <div class="module-detail-layout">
        <aside class="module-detail-summary" aria-label="Module summary">
            <div class="module-summary-top">
                <span class="module-summary-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                <x-user-status-badge :status="$module->is_published ? 'published' : 'draft'" />
            </div>
            <span class="module-summary-kicker">Module #{{ $module->order ?? '—' }}</span>
            <h2>{{ $module->title }}</h2>
            <p>{{ Str::limit((string) $module->description, 180) }}</p>

            <dl class="module-summary-list">
                <div><dt><i class="fa-solid fa-book" aria-hidden="true"></i> Course</dt><dd>{{ $course->code }} — {{ $course->name ?? $course->title }}</dd></div>
                <div><dt><i class="fa-solid fa-list-ol" aria-hidden="true"></i> Position</dt><dd>#{{ $module->order ?? '—' }}</dd></div>
                <div><dt><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Lessons</dt><dd>{{ $module->lessons->count() }}</dd></div>
            </dl>

            <form method="POST" action="{{ route('instructor.courses.modules.' . ($module->is_published ? 'unpublish' : 'publish'), [$course, $module]) }}">
                @csrf
                <button type="submit" class="btn {{ $module->is_published ? 'btn-secondary' : 'btn-success' }}" style="width:100%;justify-content:center">
                    <i class="fa-solid {{ $module->is_published ? 'fa-eye-slash' : 'fa-paper-plane' }}" aria-hidden="true"></i>
                    {{ $module->is_published ? 'Unpublish Module' : 'Publish Module' }}
                </button>
            </form>
        </aside>

        <div class="module-detail-main">
            <section class="user-panel module-overview-panel" aria-labelledby="module-overview-title">
                <div class="user-panel-head">
                    <div>
                        <span class="user-kicker"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Overview</span>
                        <h3 id="module-overview-title">Module information</h3>
                    </div>
                </div>
                <div class="user-panel-body module-overview-body">
                    @if($module->description)
                        <div class="module-overview-block"><span class="module-overview-label"><i class="fa-solid fa-align-left" aria-hidden="true"></i> Description</span><p>{{ $module->description }}</p></div>
                    @endif
                    @if($module->objectives)
                        <div class="module-overview-block"><span class="module-overview-label"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Learning objectives</span><p style="white-space:pre-wrap">{{ $module->objectives }}</p></div>
                    @endif
                    @if(!$module->description && !$module->objectives)
                        <x-user-empty-state icon="fa-align-left" title="No module description yet" description="Add a description or learning objectives to guide your students." />
                    @endif
                </div>
            </section>

            <section class="user-panel module-lessons-panel" aria-labelledby="module-lessons-title">
                <div class="user-panel-head">
                    <div>
                        <span class="user-kicker"><i class="fa-solid fa-list" aria-hidden="true"></i> Content</span>
                        <h3 id="module-lessons-title">Lessons in this module ({{ $module->lessons->count() }})</h3>
                    </div>
                    <div class="user-actions">
                        <a href="{{ route('instructor.courses.modules.lessons.index', [$course, $module]) }}" class="btn btn-secondary btn-sm"><i class="fa-solid fa-list" aria-hidden="true"></i> Manage</a>
                        <a href="{{ route('instructor.courses.modules.lessons.create', [$course, $module]) }}" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Lesson</a>
                    </div>
                </div>

                <div class="user-panel-body">
                    @if(($module->lessons->count() ?? 0) > 0)
                        <div class="module-lesson-list">
                            @foreach($module->lessons as $lesson)
                                <article class="module-lesson-card">
                                    <span class="module-lesson-icon lesson-type-{{ $lesson->type ?? 'text' }}">
                                        @switch($lesson->type ?? 'text')
                                            @case('video')<i class="fa-solid fa-play" aria-hidden="true"></i>@break
                                            @case('quiz')<i class="fa-solid fa-circle-question" aria-hidden="true"></i>@break
                                            @case('file')<i class="fa-solid fa-file" aria-hidden="true"></i>@break
                                            @default<i class="fa-solid fa-book-open" aria-hidden="true"></i>@break
                                        @endswitch
                                    </span>
                                    <div class="module-lesson-body">
                                        <strong>{{ $lesson->title }}</strong>
                                        <span class="module-lesson-meta">
                                            <span><i class="fa-solid fa-list-ol" aria-hidden="true"></i> #{{ $lesson->order ?? $loop->iteration }}</span>
                                            @if($lesson->duration)<span><i class="fa-solid fa-clock" aria-hidden="true"></i> {{ $lesson->duration }} min</span>@endif
                                            <x-user-status-badge :status="$lesson->is_published ? 'published' : 'draft'" />
                                        </span>
                                    </div>
                                    <div class="user-actions">
                                        <a href="{{ route('instructor.courses.modules.lessons.show', [$course, $module, $lesson]) }}" class="btn btn-icon" title="View lesson"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                        <a href="{{ route('instructor.courses.modules.lessons.edit', [$course, $module, $lesson]) }}" class="btn btn-icon" title="Edit lesson"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
                                        <form method="POST" action="{{ route('instructor.courses.modules.lessons.destroy', [$course, $module, $lesson]) }}" onsubmit="return confirm('Delete this lesson?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-danger" title="Delete lesson"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <x-user-empty-state
                            icon="fa-book-open"
                            title="No lessons in this module yet"
                            description="Add the first lesson to start building this module’s content."
                        >
                            <x-slot name="action">
                                <a href="{{ route('instructor.courses.modules.lessons.create', [$course, $module]) }}" class="btn btn-primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Lesson</a>
                            </x-slot>
                        </x-user-empty-state>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.module-detail-page{--module-accent:#c4b5fd;--module-accent-strong:#7c3aed;--module-accent-soft:rgba(139,92,246,.14);gap:16px}.module-detail-page .user-hero{display:flex;align-items:flex-end;justify-content:space-between;box-sizing:border-box}.module-detail-page *{box-sizing:border-box}.module-detail-layout{display:grid;grid-template-columns:minmax(230px,.38fr) minmax(0,1fr);gap:16px;align-items:start}.module-detail-summary{position:sticky;top:18px;display:grid;gap:15px;padding:20px;border:1px solid rgba(196,181,253,.26);border-radius:16px;background:linear-gradient(160deg,rgba(124,58,237,.2),rgba(15,31,75,.85));box-shadow:0 16px 36px rgba(3,8,20,.18)}.module-summary-top{display:flex;align-items:center;justify-content:space-between;gap:10px}.module-summary-icon{display:grid;place-items:center;width:44px;height:44px;border:1px solid rgba(196,181,253,.32);border-radius:13px;color:var(--module-accent);background:rgba(139,92,246,.16)}.module-summary-kicker{color:var(--module-accent);font-size:.62rem;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.module-detail-summary h2{margin:3px 0 0;color:#fff;font-size:1.18rem;line-height:1.2}.module-detail-summary>p{margin:8px 0 0;color:rgba(238,244,255,.72);font-size:.72rem;line-height:1.5}.module-summary-list{display:grid;gap:12px;margin:0;padding:16px 0;border-top:1px solid rgba(219,234,254,.14);border-bottom:1px solid rgba(219,234,254,.14)}.module-summary-list div{display:grid;gap:4px}.module-summary-list dt{color:rgba(238,244,255,.55);font-size:.62rem;text-transform:uppercase;letter-spacing:.06em}.module-summary-list dt i{width:16px;color:var(--module-accent)}.module-summary-list dd{margin:0;color:#fff;font-size:.74rem;line-height:1.35}.module-overview-body{display:grid;gap:18px}.module-overview-block{display:grid;gap:7px}.module-overview-label{color:var(--module-accent-strong);font-size:.64rem;font-weight:850;letter-spacing:.09em;text-transform:uppercase}.module-overview-label i{margin-right:6px}.module-overview-block p{margin:0;color:var(--dash-muted);font-size:.78rem;line-height:1.65}.module-lesson-list{display:grid;gap:9px}.module-lesson-card{display:flex;align-items:center;gap:13px;min-width:0;padding:13px 14px;border:1px solid var(--dash-line);border-radius:12px;background:var(--dash-surface-raised);transition:border-color .16s,box-shadow .16s}.module-lesson-card:hover{border-color:rgba(196,181,253,.45);box-shadow:0 8px 20px rgba(3,8,20,.12)}.module-lesson-icon{display:grid;place-items:center;width:38px;height:38px;flex:none;border-radius:11px}.lesson-type-video{color:#93c5fd;background:rgba(59,130,246,.16)}.lesson-type-quiz{color:#c4b5fd;background:rgba(139,92,246,.16)}.lesson-type-file{color:#6ee7b7;background:rgba(16,185,129,.14)}.lesson-type-text{color:#fcd34d;background:rgba(251,191,36,.14)}.module-lesson-body{display:grid;gap:5px;min-width:0;flex:1}.module-lesson-body>strong{overflow:hidden;color:var(--dash-text);font-size:.8rem;text-overflow:ellipsis;white-space:nowrap}.module-lesson-meta{display:flex;align-items:center;flex-wrap:wrap;gap:12px;color:var(--dash-muted);font-size:.66rem}.module-lesson-meta>span{display:inline-flex;align-items:center;gap:5px}.module-lesson-meta .user-status{font-size:.6rem;padding:3px 8px}body.light-mode .module-detail-summary{background:linear-gradient(160deg,rgba(124,58,237,.94),rgba(20,16,45,.98))}.light-mode .module-detail-summary h2{color:#fff}@media(max-width:860px){.module-detail-page .user-hero{align-items:flex-start;flex-direction:column;padding:20px}.module-detail-layout{grid-template-columns:1fr}.module-detail-summary{position:static}}@media(max-width:620px){.module-lesson-card{align-items:flex-start;flex-wrap:wrap}.module-lesson-body{flex-basis:calc(100% - 52px)}.module-lesson-card .user-actions{margin-left:auto}.module-detail-summary{padding:17px}}@media(prefers-reduced-motion:reduce){.module-lesson-card{transition:none}}
</style>
@endpush

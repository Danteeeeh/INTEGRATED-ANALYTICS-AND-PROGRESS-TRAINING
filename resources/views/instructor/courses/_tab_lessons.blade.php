<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-book-open"></i> Lessons ({{ $lessons->count() }})</h3>
        <a href="{{ $modules->first() ? route('instructor.courses.modules.lessons.index', [$course, $modules->first()]) : route('instructor.courses.modules.index', $course) }}" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Add Lesson
        </a>
    </div>
    <div style="padding: 16px 24px;">
        @if($lessons->isNotEmpty())
            @foreach($lessons as $lesson)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(139,92,246,.14);color:#c4b5fd"><i class="fa-solid fa-book-open"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $lesson->title }}</div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-layer-group"></i> {{ $lesson->module?->title ?? 'Unassigned' }}
                            @if($lesson->duration_minutes)
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-clock"></i> {{ $lesson->duration_minutes }} min
                            @endif
                            <span style="margin: 0 8px;">·</span>
                            @if($lesson->is_required ?? false)
                                <span class="badge-published">Required</span>
                            @else
                                <span class="badge-draft" style="background:rgba(148,163,184,.14);color:#cbd5e1">Optional</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ $lesson->module_id ? route('instructor.courses.modules.lessons.show', [$course, $lesson->module_id, $lesson]) : '#' }}" class="btn-icon btn-view" title="View lesson">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endforeach
            <div style="margin-top: 10px; text-align: center;">
                <a href="{{ $modules->first() ? route('instructor.courses.modules.lessons.index', [$course, $modules->first()]) : route('instructor.courses.modules.index', $course) }}" style="color:#62c9f5;text-decoration:underline;font-weight:600;font-size:0.84rem;">
                    Manage All Lessons →
                </a>
            </div>
        @else
            <div class="tab-empty-state">
                <i class="fa-solid fa-book-open"></i>
                No lessons yet.
                <a href="{{ $modules->first() ? route('instructor.courses.modules.lessons.create', [$course, $modules->first()]) : route('instructor.courses.modules.index', $course) }}">Add a lesson</a>
            </div>
        @endif
    </div>
</div>

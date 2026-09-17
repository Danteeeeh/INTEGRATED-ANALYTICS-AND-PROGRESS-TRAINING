<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-file-pen"></i> Assignments ({{ $assignments->count() }})</h3>
        <a href="{{ $course->classes->first() ? route('instructor.courses.assignments.create', [$course, 'class_id' => $course->classes->first()->id]) : '#' }}" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Assignment
        </a>
    </div>
    <div style="padding: 16px 24px;">
        @if($assignments->isNotEmpty())
            @foreach($assignments as $assignment)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(251,191,36,.14);color:#fcd34d"><i class="fa-solid fa-file-pen"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $assignment->title }}</div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> {{ $assignment->class?->code ?? 'Unassigned' }}
                            @if($assignment->points)
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-star"></i> {{ $assignment->points }} pts
                            @endif
                            @if($assignment->due_date)
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-clock"></i> Due {{ $assignment->due_date?->format('M j, Y') }}
                            @endif
                            <span style="margin: 0 8px;">·</span>
                            @if($assignment->status === 'published')
                                <span class="badge-published">Published</span>
                            @elseif($assignment->status === 'closed')
                                <span class="badge-draft">Closed</span>
                            @else
                                <span class="badge-draft" style="background:rgba(148,163,184,.14);color:#cbd5e1">Draft</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.assignments.show', [$course, $assignment]) }}" class="btn-icon btn-view" title="View assignment">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endforeach
        @else
            <div class="tab-empty-state">
                <i class="fa-solid fa-file-pen"></i>
                No assignments yet.
                <a href="{{ $course->classes->first() ? route('instructor.courses.assignments.create', [$course, 'class_id' => $course->classes->first()->id]) : '#' }}">Create one</a>
            </div>
        @endif
    </div>
</div>

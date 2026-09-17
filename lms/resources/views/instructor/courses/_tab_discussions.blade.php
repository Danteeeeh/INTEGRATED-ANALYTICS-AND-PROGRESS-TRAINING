<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-comments"></i> Discussions ({{ $discussions->count() }})</h3>
        <a href="{{ route('instructor.courses.discussions.create', $course) }}" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Discussion
        </a>
    </div>
    <div style="padding: 16px 24px;">
        @if($discussions->isNotEmpty())
            @foreach($discussions as $discussion)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(34,211,238,.13);color:#67e8f9"><i class="fa-solid fa-comments"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $discussion->title }}</div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> {{ $discussion->class?->code ?? 'Course-wide' }}
                            <span style="margin: 0 8px;">·</span>
                            <i class="fa-solid fa-comment-dots"></i> {{ $discussion->posts_count ?? $discussion->posts?->count() ?? 0 }} posts
                            @if($discussion->is_pinned ?? false)
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.discussions.show', [$course, $discussion]) }}" class="btn-icon btn-view" title="View discussion">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endforeach
        @else
            <div class="tab-empty-state">
                <i class="fa-solid fa-comments"></i>
                No discussions yet.
                <a href="{{ route('instructor.courses.discussions.create', $course) }}">Start a discussion</a>
            </div>
        @endif
    </div>
</div>

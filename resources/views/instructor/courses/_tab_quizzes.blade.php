<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-circle-question"></i> Quizzes ({{ $quizzes->count() }})</h3>
        <a href="{{ $course->classes->first() ? route('instructor.courses.quizzes.create', [$course, 'class_id' => $course->classes->first()->id]) : '#' }}" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Quiz
        </a>
    </div>
    <div style="padding: 16px 24px;">
        @if($quizzes->isNotEmpty())
            @foreach($quizzes as $quiz)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(139,92,246,.14);color:#c4b5fd"><i class="fa-solid fa-circle-question"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $quiz->title }}</div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-school"></i> {{ $quiz->class?->code ?? 'Unassigned' }}
                            @if($quiz->time_limit_minutes)
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-stopwatch"></i> {{ $quiz->time_limit_minutes }} min
                            @endif
                            @if($quiz->passing_score_percent)
                                <span style="margin: 0 8px;">·</span>
                                <i class="fa-solid fa-bullseye"></i> Pass {{ $quiz->passing_score_percent }}%
                            @endif
                            <span style="margin: 0 8px;">·</span>
                            @if($quiz->status === 'published')
                                <span class="badge-published">Published</span>
                            @elseif($quiz->status === 'closed')
                                <span class="badge-draft">Closed</span>
                            @else
                                <span class="badge-draft" style="background:rgba(148,163,184,.14);color:#cbd5e1">Draft</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.quizzes.show', [$course, $quiz]) }}" class="btn-icon btn-view" title="View quiz">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endforeach
        @else
            <div class="tab-empty-state">
                <i class="fa-solid fa-circle-question"></i>
                No quizzes yet.
                <a href="{{ $course->classes->first() ? route('instructor.courses.quizzes.create', [$course, 'class_id' => $course->classes->first()->id]) : '#' }}">Create one</a>
            </div>
        @endif
    </div>
</div>

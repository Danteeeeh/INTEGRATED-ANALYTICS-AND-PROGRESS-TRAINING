<div class="crud-card">
    <div class="crud-header">
        <h3><i class="fa-solid fa-bullhorn"></i> Announcements ({{ $announcements->count() }})</h3>
        <a href="{{ route('instructor.courses.announcements.create', $course) }}" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            Create Announcement
        </a>
    </div>
    <div style="padding: 16px 24px;">
        @if($announcements->isNotEmpty())
            @foreach($announcements as $announcement)
                <div class="module-mini-card">
                    <div class="module-mini-icon" style="background:rgba(244,63,94,.13);color:#fda4af"><i class="fa-solid fa-bullhorn"></i></div>
                    <div class="module-mini-body">
                        <div class="module-mini-title">{{ $announcement->title }}</div>
                        <div class="module-mini-meta">
                            <i class="fa-solid fa-clock"></i> {{ $announcement->created_at?->diffForHumans() }}
                            @if($announcement->is_pinned ?? false)
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-published"><i class="fa-solid fa-thumbtack"></i> Pinned</span>
                            @endif
                            @if(($announcement->publish_at ?? null) && $announcement->publish_at > now())
                                <span style="margin: 0 8px;">·</span>
                                <span class="badge-draft">Scheduled</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('instructor.courses.announcements.show', [$course, $announcement]) }}" class="btn-icon btn-view" title="View announcement">
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endforeach
        @else
            <div class="tab-empty-state">
                <i class="fa-solid fa-bullhorn"></i>
                No announcements yet.
                <a href="{{ route('instructor.courses.announcements.create', $course) }}">Post one</a>
            </div>
        @endif
    </div>
</div>

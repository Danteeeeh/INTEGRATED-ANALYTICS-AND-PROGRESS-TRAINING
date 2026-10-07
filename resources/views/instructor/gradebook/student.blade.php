@extends('layouts.instructor')
@section('title', 'Student Grades')
@php($activeNav = 'gradebook')
@section('content')
<div class="dash-section">
    <div>
        <h3><i class="fa-solid fa-user-graduate"></i> {{ $student->name ?? $student->full_name }}</h3>
        <span class="dash-section-kicker">{{ $class->code }} · {{ $class->course?->title ?? 'Gradebook' }}</span>
    </div>
    <a class="btn btn-secondary" href="{{ route('instructor.classes.gradebook.index', $class) }}"><i class="fa-solid fa-arrow-left"></i> Back to gradebook</a>
</div>

<div class="dash-stats">
    <div class="dash-stat"><span class="dash-stat-label">Earned</span><span class="dash-stat-value">{{ number_format($summary['earned_points'], 1) }}</span></div>
    <div class="dash-stat"><span class="dash-stat-label">Possible</span><span class="dash-stat-value">{{ number_format($summary['max_points'], 1) }}</span></div>
    <div class="dash-stat">
        <span class="dash-stat-label">Grade</span>
        <span class="dash-stat-value">
            {{ ($summary['is_graded'] ?? false) ? number_format($summary['percent'], 1) . '%' : '—' }}
            @if($summary['is_graded'] ?? false)
                <small style="opacity:.7">({{ $summary['letter_grade'] }})</small>
            @endif
        </span>
    </div>
    <div class="dash-stat"><span class="dash-stat-label">Graded Items</span><span class="dash-stat-value">{{ $summary['graded_items'] }}</span></div>
</div>

@unless ($summary['is_graded'] ?? false)
    <div class="empty-state" style="margin-bottom:16px">
        <i class="fa-solid fa-circle-info"></i>
        No released, graded items for this student yet. Scores appear here once the grade item is graded and released.
    </div>
@endunless

<section class="dash-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:1rem;"><i class="fa-solid fa-pen-to-square"></i> Grade Items (Manual)</h3>
        <span class="dash-section-kicker">{{ $gradeItems->count() }} items</span>
    </div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Score</th>
                <th>%</th>
                <th>Feedback</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($gradeItems as $item)
                @php($grade = $item->grades->first())
                <tr>
                    <td>{{ $item->title }}</td>
                    <td>{{ ucfirst($item->item_type ?? 'other') }}</td>
                    <td>{{ $grade ? number_format($grade->points, 1) . ' / ' . number_format($item->max_points, 1) : '—' }}</td>
                    <td>
                        @if($grade && $item->is_released)
                            {{ number_format($grade->score_percent, 1) }}%
                            <span style="opacity:.6">({{ $grade->letter_grade ?? '—' }})</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($grade && $item->is_released)
                            <span>{{ \Illuminate\Support\Str::limit($grade->feedback ?: '—', 60) }}</span>
                        @else
                            <span>—</span>
                        @endif
                    </td>
                    <td>{{ $item->is_released ? 'Released' : 'Hidden' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="search-no-results">No grade items for this class.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

@if($quizAttempts->count() > 0)
<section class="dash-panel" style="margin-top:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:1rem;"><i class="fa-solid fa-question-circle"></i> Quiz Attempts</h3>
        <span class="dash-section-kicker">{{ $quizAttempts->count() }} attempts</span>
    </div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Quiz</th>
                <th>Attempt</th>
                <th>Score</th>
                <th>%</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quizAttempts as $attempt)
                <tr>
                    <td>{{ $attempt->quiz->title }}</td>
                    <td>#{{ $attempt->attempt_number }}</td>
                    <td>
                        @if($attempt->status === 'submitted' || $attempt->status === 'auto_submitted' || $attempt->status === 'graded')
                            {{ $attempt->score }}/{{ $attempt->quiz->questions()->sum('quiz_questions.points') ?? '—' }}
                        @else
                            In Progress
                        @endif
                    </td>
                    <td>
                        @if($attempt->score_percent !== null)
                            {{ number_format($attempt->score_percent, 1) }}%
                            <span style="opacity:.6">({{ $attempt->is_passed === true ? 'Pass' : ($attempt->is_passed === false ? 'Fail' : '—') }})</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="gb-status gb-status-{{ match($attempt->status) { 'submitted','auto_submitted','graded' => 'success', 'in_progress' => 'muted', default => 'warning' } }}">
                            {{ ucfirst(str_replace('_', ' ', $attempt->status)) }}
                        </span>
                    </td>
                    <td>{{ $attempt->created_at->format('M j, Y g:i A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif

@if($examAttempts->count() > 0)
<section class="dash-panel" style="margin-top:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h3 style="margin:0;font-size:1rem;"><i class="fa-solid fa-file-signature"></i> Exam Attempts</h3>
        <span class="dash-section-kicker">{{ $examAttempts->count() }} attempts</span>
    </div>
    <table class="dash-table">
        <thead>
            <tr>
                <th>Exam</th>
                <th>Attempt</th>
                <th>Score</th>
                <th>%</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($examAttempts as $attempt)
                <tr>
                    <td>{{ $attempt->exam->title }}</td>
                    <td>#{{ $attempt->attempt_number }}</td>
                    <td>
                        @if($attempt->status === 'submitted' || $attempt->status === 'auto_submitted' || $attempt->status === 'graded')
                            {{ $attempt->score }}/{{ $attempt->exam->questions()->sum('exam_questions.points') ?? '—' }}
                        @else
                            In Progress
                        @endif
                    </td>
                    <td>
                        @if($attempt->score_percent !== null)
                            {{ number_format($attempt->score_percent, 1) }}%
                            <span style="opacity:.6">({{ $attempt->is_passed === true ? 'Pass' : ($attempt->is_passed === false ? 'Fail' : '—') }})</span>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <span class="gb-status gb-status-{{ match($attempt->status) { 'submitted','auto_submitted','graded' => 'success', 'in_progress' => 'muted', default => 'warning' } }}">
                            {{ ucfirst(str_replace('_', ' ', $attempt->status)) }}
                        </span>
                    </td>
                    <td>{{ $attempt->created_at->format('M j, Y g:i A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endif
@endsection
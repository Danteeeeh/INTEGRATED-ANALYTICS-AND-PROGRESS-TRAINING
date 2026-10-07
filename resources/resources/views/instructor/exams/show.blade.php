@extends('layouts.instructor')

@section('title', $exam->title)
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $exam->title }}"
        subtitle="{{ \Illuminate\Support\Str::limit($exam->description ?? 'Exam overview, questions, and student attempts.', 120) }}"
        icon="fa-file-signature"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $exam->status }}" />
            <span class="user-status">{{ $exam->getTypeLabel() }}</span>
            <span class="user-status">{{ $exam->getQuestionCount() }} questions</span>
            @if($exam->isProctored())<span class="user-status active">Proctored</span>@endif
        </x-slot>
        <x-slot name="actions">
            @if($exam->status === 'draft')
                <form method="POST" action="{{ route('instructor.courses.exams.publish', [$course, $exam]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                </form>
            @elseif($exam->status === 'published')
                <form method="POST" action="{{ route('instructor.courses.exams.close', [$course, $exam]) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-lock"></i> Close Exam</button>
                </form>
            @endif
            <a href="{{ route('instructor.courses.exams.edit', [$course, $exam]) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('instructor.exams.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="user-stat-grid">
        <x-user-stat-card label="Questions" value="{{ $exam->getQuestionCount() }}" icon="fa-list-check" footer="{{ number_format($exam->getTotalPoints(), 0) }} points" />
        <x-user-stat-card label="Duration" value="{{ $exam->duration_minutes }} min" icon="fa-clock" footer="Time limit" />
        <x-user-stat-card label="Attempts" value="{{ $exam->attempt_limit ?: '∞' }}" icon="fa-rotate-right" footer="Per student" />
        <x-user-stat-card label="Passing" value="{{ (float) $exam->passing_score_percent }}%" icon="fa-circle-check" footer="{{ (int) $exam->grade_weight }}&percnt; grade weight" />
    </div>

    {{-- Schedule & rules --}}
    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-calendar-clock"></i> Schedule &amp; Rules</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Opens</label>
                    <div>{{ $exam->starts_at?->format('M j, Y g:i A') ?? 'Not scheduled' }}</div>
                </div>
                <div class="form-field">
                    <label>Closes</label>
                    <div>{{ $exam->ends_at?->format('M j, Y g:i A') ?? 'No deadline' }}</div>
                </div>
                <div class="form-field">
                    <label>Daily Window</label>
                    <div>
                        @if($exam->allowed_start_time || $exam->allowed_end_time)
                            {{ $exam->allowed_start_time ?? '00:00' }} &ndash; {{ $exam->allowed_end_time ?? '23:59' }}
                        @else
                            <span style="color:var(--dash-muted);">Any time while open</span>
                        @endif
                    </div>
                </div>
                <div class="form-field">
                    <label>Result Visibility</label>
                    <div>{{ ucfirst(str_replace('_', ' ', $exam->result_visibility)) }}</div>
                </div>
                <div class="form-field">
                    <label>Navigation</label>
                    <div>{{ $exam->allow_navigation ? 'Allowed' : 'Locked — one question per screen' }}</div>
                </div>
                <div class="form-field">
                    <label>Shuffle</label>
                    <div>
                        Questions: {{ $exam->shuffle_questions ? 'Yes' : 'No' }} ·
                        Choices: {{ $exam->shuffle_choices ? 'Yes' : 'No' }}
                    </div>
                </div>
            </div>

            @if($exam->instructions)
                <div style="margin-top:16px;">
                    <span class="dash-stat-label">Instructions</span>
                    <p style="color:var(--dash-muted);white-space:pre-wrap;margin:6px 0 0;font-size:.85rem;">{{ $exam->instructions }}</p>
                </div>
            @endif

            @if($exam->proctoring_instructions)
                <div style="margin-top:14px;padding:12px;border:1px solid rgba(239,68,68,.3);border-radius:9px;background:rgba(239,68,68,.08);">
                    <strong style="font-size:.82rem;color:#fca5a5;">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Proctoring
                    </strong>
                    <p style="color:var(--dash-muted);white-space:pre-wrap;margin:6px 0 0;font-size:.82rem;">{{ $exam->proctoring_instructions }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Questions --}}
    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Questions ({{ $exam->questions->count() }})</h3>
            <span class="user-status">{{ number_format($exam->getTotalPoints(), 0) }} total points</span>
        </div>
        <div class="user-panel-body">
            @forelse($exam->questions->sortBy('pivot.order') as $question)
                <div style="padding:12px;border:1px solid var(--dash-line);border-radius:10px;margin-bottom:10px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;">
                        <strong style="font-size:.86rem;">
                            {{ $question->question_text }}
                        </strong>
                        <span class="user-status" style="flex-shrink:0;">
                            {{ number_format($question->pivot->points ?? 1, 0) }} pts
                        </span>
                    </div>

                    <div style="margin-top:6px;font-size:.74rem;color:var(--dash-muted);text-transform:uppercase;letter-spacing:.06em;">
                        {{ str_replace('_', ' ', $question->question_type) }}
                    </div>

                    @if($question->choices->isNotEmpty())
                        <ul style="margin:8px 0 0;padding-left:18px;color:var(--dash-muted);font-size:.82rem;">
                            @foreach($question->choices->sortBy('position') as $choice)
                                <li style="{{ $choice->is_correct ? 'font-weight:700;color:#6ee7b7' : '' }}">
                                    {{ $choice->choice_text }}
                                    @if($choice->is_correct)<i class="fa-solid fa-check" aria-hidden="true"></i>@endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <x-user-empty-state
                    icon="fa-list-check"
                    title="No questions yet"
                    description="Attach questions from your question bank or add them manually."
                />
            @endforelse
        </div>
    </div>

    {{-- Attempts --}}
    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-users"></i> Student Attempts ({{ $exam->attempts->count() }})</h3>
        </div>
        <div class="user-panel-body">
            @if($exam->attempts->isEmpty())
                <x-user-empty-state icon="fa-users" title="No attempts yet" description="No student has started this exam." />
            @else
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Result</th>
                                <th>Started</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($exam->attempts->sortByDesc('started_at') as $attempt)
                                <tr>
                                    <td>
                                        <div class="user-cell-primary">{{ $attempt->student?->name ?? '—' }}</div>
                                        <div class="user-email">{{ $attempt->student?->email ?? '' }}</div>
                                    </td>
                                    <td><x-user-status-badge status="{{ $attempt->status }}" /></td>
                                    <td>
                                        @if($attempt->score_percent !== null)
                                            {{ number_format((float) $attempt->score_percent, 1) }}%
                                        @else
                                            <span style="color:var(--dash-muted);">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attempt->is_passed === true)
                                            <span class="user-status active">Passed</span>
                                        @elseif($attempt->is_passed === false)
                                            <span class="user-status danger">Failed</span>
                                        @else
                                            <span style="color:var(--dash-muted);">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $attempt->started_at?->format('M j, H:i') ?? '—' }}</td>
                                    <td>{{ $attempt->submitted_at?->format('M j, H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if($exam->extensions->isNotEmpty())
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Extensions ({{ $exam->extensions->count() }})</h3>
            </div>
            <div class="user-panel-body">
                <ul style="list-style:none;padding:0;margin:0;display:grid;gap:8px;">
                    @foreach($exam->extensions as $extension)
                        <li style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 12px;border:1px solid var(--dash-line);border-radius:9px;">
                            <span>
                                <strong>{{ $extension->student?->name ?? '—' }}</strong>
                                <span style="color:var(--dash-muted);font-size:.78rem;">
                                    extended to {{ $extension->new_deadline?->format('M j, Y g:i A') ?? '—' }}
                                </span>
                            </span>
                            <form method="POST" action="{{ route('instructor.courses.exams.extensions.revoke', [$course, $exam, $extension->student_id]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-danger" title="Revoke extension"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="user-actions" style="margin-top:16px;">
        <form method="POST" action="{{ route('instructor.courses.exams.destroy', [$course, $exam]) }}"
              onsubmit="return confirm('Delete this exam? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete Exam</button>
        </form>
    </div>
</div>
@endsection
@extends('layouts.admin')

@section('title', $exam->title)
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $exam->title }}"
        subtitle="{{ $exam->class?->code ?? 'No class' }} · {{ ucfirst((string) $exam->exam_type) }} · {{ $exam->duration_minutes }} minutes"
        icon="fa-file-signature"
    >
        <x-slot name="meta">
            <x-user-status-badge :status="$exam->status" :label="ucfirst((string) $exam->status)" />
            <span>{{ $exam->questions->count() }} questions</span>
            <span>{{ $exam->attempts->count() }} attempts</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
            <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="user-actions" style="margin-bottom:16px">
        @if($exam->status === 'published')
            <form method="POST" action="{{ route('admin.exams.close', $exam) }}" style="display:inline;">
                @csrf
                <button class="btn btn-secondary"><i class="fa-solid fa-lock"></i> Close exam</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.exams.publish', $exam) }}" style="display:inline;">
                @csrf
                <button class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish exam</button>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" style="display:inline;" onsubmit="return confirm('Delete this exam? Attempts may block deletion.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete exam</button>
        </form>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-circle-info"></i> Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Description</label>
                    <p>{{ $exam->description ?: '—' }}</p>
                </div>
                <div class="form-field">
                    <label>Instructions</label>
                    <p>{{ $exam->instructions ?: '—' }}</p>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <p>{{ $exam->class?->course?->title ?? ($exam->course_id ? 'Course #'.$exam->course_id : '—') }}</p>
                </div>
                <div class="form-field">
                    <label>Created by</label>
                    <p>{{ $exam->creator?->name ?? '—' }}</p>
                </div>
                <div class="form-field">
                    <label>Window</label>
                    <p>
                        {{ $exam->starts_at?->format('M d, Y g:i A') ?? 'No start' }}
                        &rarr;
                        {{ $exam->ends_at?->format('M d, Y g:i A') ?? 'No end' }}
                    </p>
                </div>
                <div class="form-field">
                    <label>Passing / weight</label>
                    <p>{{ $exam->passing_score_percent ?? '—' }}% · {{ $exam->grade_weight ?? '—' }}%</p>
                </div>
                <div class="form-field">
                    <label>Attempts</label>
                    <p>{{ $exam->attempt_limit ?? 1 }} allowed</p>
                </div>
                <div class="form-field">
                    <label>Result visibility</label>
                    <p>{{ str_replace('_', ' ', (string) $exam->result_visibility) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Questions ({{ $exam->questions->count() }})</h3>
            <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-secondary btn-sm">Manage questions</a>
        </div>
        <div class="user-panel-body">
            @forelse($exam->questions as $question)
                <div class="user-card" style="padding:12px;margin-bottom:8px">
                    <strong>{{ \Illuminate\Support\Str::limit($question->question?->question_text ?? '—', 140) }}</strong>
                    <div class="user-email">{{ $question->question?->typeLabel() ?? '—' }} · {{ $question->points }} pt</div>
                </div>
            @empty
                <div class="empty-state"><p>No questions attached yet.</p></div>
            @endforelse
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-users"></i> Attempts ({{ $exam->attempts->count() }})</h3></div>
        <div class="user-panel-body">
            @if($exam->attempts->isEmpty())
                <div class="empty-state"><p>No student has attempted this exam.</p></div>
            @else
                <div class="user-table-wrap">
                    <table class="user-table">
                        <thead><tr><th>Student</th><th>Status</th><th>Score</th><th>Started</th></tr></thead>
                        <tbody>
                            @foreach($exam->attempts as $attempt)
                                <tr>
                                    <td>{{ $attempt->student?->name ?? '—' }}</td>
                                    <td><x-user-status-badge :status="$attempt->status" :label="ucfirst((string) $attempt->status)" /></td>
                                    <td>{{ $attempt->score_percent !== null ? number_format((float) $attempt->score_percent, 2).'%' : '—' }}</td>
                                    <td>{{ $attempt->started_at?->diffForHumans() ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
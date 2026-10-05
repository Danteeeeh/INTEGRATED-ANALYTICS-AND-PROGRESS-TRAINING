@extends('layouts.instructor')
@section('title', $quiz->title)
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $quiz->title }}"
        subtitle="Quiz for {{ $course->title }}"
        icon="fa-question-circle"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $quiz->status }}" />
            <span class="user-status">{{ $quiz->questions->count() }} questions</span>
            <span class="user-status">{{ $quiz->attempts->count() }} attempts</span>
        </x-slot>
        <x-slot name="actions">
            @if($quiz->status === 'published')
                <form method="POST" action="{{ route('instructor.courses.quizzes.close', [$course, $quiz]) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-lock"></i> Close</button>
                </form>
            @else
                <form method="POST" action="{{ route('instructor.courses.quizzes.publish', [$course, $quiz]) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Publish</button>
                </form>
            @endif
            <a href="{{ route('instructor.courses.quizzes.edit', [$course, $quiz]) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('instructor.courses.quizzes.attempts', [$course, $quiz]) }}" class="btn btn-secondary"><i class="fa-solid fa-users"></i> Attempts</a>
            <a href="{{ route('instructor.courses.quizzes.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Quiz Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $quiz->class?->code ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Time Limit</label>
                    <div>{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' min' : '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Attempt Limit</label>
                    <div>{{ $quiz->attempt_limit ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Passing Score</label>
                    <div>{{ $quiz->passing_score_percent ? $quiz->passing_score_percent.'%' : '—' }}</div>
                </div>
                @if($quiz->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $quiz->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-file-import"></i> Import Questions</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.quizzes.import', [$course, $quiz]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-field full">
                        <label for="import_file">Upload File (CSV or TXT)</label>
                        <input type="file" id="import_file" name="import_file" accept=".csv,.txt" required>
                        <small class="form-hint">Supported formats: CSV with headers (question_text, question_type, choice_1, choice_2, choice_3, choice_4, correct_answer, points, difficulty) or plain text with numbered questions and choices marked with asterisk (*) for correct answers.</small>
                    </div>
                    <div class="form-field">
                        <label for="question_bank_id">Add to Question Bank (Optional)</label>
                        <select id="question_bank_id" name="question_bank_id">
                            <option value="">No Question Bank</option>
                            @foreach(\App\Models\QuestionBank::active()->get() as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-upload"></i> Import Questions</button>
                </div>
            </form>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-clock"></i> Deadline Extensions</h3></div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.courses.quizzes.extensions.grant', [$course, $quiz]) }}" method="POST">
                @csrf
                <div class="form-grid">
                    <div class="form-field">
                        <label for="student_id">Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a student...</option>
                            @foreach($course->enrollments()->where('status', 'active')->get() as $enrollment)
                                <option value="{{ $enrollment->student_id }}">{{ $enrollment->student->name }} ({{ $enrollment->student->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="extended_until">Extended Until</label>
                        <input type="datetime-local" id="extended_until" name="extended_until" required>
                    </div>
                    <div class="form-field full">
                        <label for="reason">Reason (Optional)</label>
                        <input type="text" id="reason" name="reason" placeholder="e.g., Medical emergency, family emergency, etc.">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Grant Extension</button>
                </div>
            </form>

            @if($quiz->extensions && $quiz->extensions->count() > 0)
                <div style="margin-top: 20px;">
                    <h4>Active Extensions</h4>
                    <div class="user-table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Extended Until</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quiz->extensions as $extension)
                                    <tr>
                                        <td>{{ $extension->student->name }}</td>
                                        <td>{{ $extension->extended_until->format('M j, Y g:i A') }}</td>
                                        <td>{{ $extension->reason ?? '—' }}</td>
                                        <td>
                                            <form action="{{ route('instructor.courses.quizzes.extensions.revoke', [$course, $quiz, $extension->student_id]) }}" method="POST" onsubmit="return confirm('Revoke this extension?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-times"></i> Revoke</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-list-ol"></i> Questions</h3><span class="user-status">{{ $quiz->questions->count() }} questions</span></div>
        <div class="user-panel-body">
            @if($quiz->questions->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($quiz->questions as $index => $question)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ Str::limit($question->question_text ?? $question->text ?? 'Question', 70) }}</td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $question->question_type ?? 'choice')) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-list-ol" title="No questions" description="This quiz has no questions yet." />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('instructor.courses.quizzes.destroy', [$course, $quiz]) }}" method="POST" onsubmit="return confirm('Delete this quiz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

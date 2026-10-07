@extends('layouts.instructor')

@section('title', 'Edit Exam')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Exam"
        subtitle="Update the schedule, timing rules, and result visibility."
        icon="fa-file-signature"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $exam->status }}" />
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View</a>
            <a href="{{ route('instructor.exams.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin:6px 0 0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Exam Details</h3>
        </div>
        <div class="user-panel-body">
            <form method="POST" action="{{ route('instructor.courses.exams.update', [$course, $exam]) }}">
                @include('instructor.exams._form', [
                    'exam' => $exam,
                    'courses' => $courses,
                    'classes' => $classes,
                    'method' => 'PUT',
                ])

                <div class="form-actions user-actions">
                    <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('instructor.courses.exams.destroy', [$course, $exam]) }}"
          style="margin-top:16px;" onsubmit="return confirm('Delete this exam?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete Exam</button>
    </form>
</div>

{{-- The edit page used to stop at the details form, leaving no way to add a
     question or import one without first leaving for the exam page.
     The layout only yields "content", so this stays inside that section. --}}
@php
    $canEditQuestions = $exam->status === \App\Models\Exam::STATUS_DRAFT;
    $examQuestions = $exam->questions()->with('choices')->get();
@endphp

<div class="user-panel" style="margin-top:16px;">
    <div class="user-panel-head">
        <h3><i class="fa-solid fa-list-check"></i> Questions ({{ $examQuestions->count() }})</h3>
        <span class="user-status">{{ number_format($exam->getTotalPoints(), 0) }} total points</span>
    </div>

    <div class="user-panel-body">
        @unless ($canEditQuestions)
            <div class="empty-state" style="margin-bottom:16px">
                <i class="fa-solid fa-circle-info"></i>
                This exam is {{ $exam->status }}, so its questions are locked.
                <a href="{{ route('instructor.courses.exams.questions.index', [$course, $exam]) }}">View the full question manager</a>.
            </div>
        @endunless

        @forelse($examQuestions->sortBy('pivot.order') as $question)
            <div style="padding:12px;border:1px solid var(--dash-line);border-radius:10px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                    <strong style="font-size:.86rem;">{{ $question->question_text }}</strong>

                    <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                        @if ($canEditQuestions)
                            <form method="POST"
                                  action="{{ route('instructor.courses.exams.questions.update', [$course, $exam, $question]) }}"
                                  style="display:flex;align-items:center;gap:4px;">
                                @csrf
                                @method('PUT')
                                <input type="number" name="points" value="{{ $question->pivot->points ?? 1 }}"
                                       min="0.5" max="1000" step="0.5" title="Points"
                                       style="width:72px;padding:5px 7px;border-radius:7px;border:1px solid var(--dash-line);background:var(--dash-input,#0f172a);color:inherit;font-size:.8rem;">
                                <button type="submit" class="btn btn-icon" title="Save points"><i class="fa-solid fa-check"></i></button>
                            </form>
                        @endif

                        <span class="user-status">{{ number_format($question->pivot->points ?? 1, 0) }} pts</span>

                        @if ($canEditQuestions)
                            <form method="POST"
                                  action="{{ route('instructor.courses.exams.questions.destroy', [$course, $exam, $question]) }}"
                                  onsubmit="return confirm('Remove this question from the exam? The copy in your bank is kept.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-danger" title="Remove from this exam"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        @endif
                    </div>
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
                description="Write one below or import a CSV of questions."
            />
        @endforelse
    </div>
</div>

@if ($canEditQuestions)
    {{-- ── Import ──────────────────────────────────────────── --}}
    <div class="user-panel" style="margin-top:16px;">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-file-import"></i> Import questions</h3>
            <span class="user-status">CSV or TXT</span>
        </div>

        <div class="user-panel-body">
            <form method="POST"
                  action="{{ route('instructor.courses.exams.questions.import', [$course, $exam]) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="form-field" style="margin-bottom:12px;">
                    <label>Question file <span class="required">*</span></label>
                    <input type="file" name="import_file" accept=".csv,.txt" required>
                    <span class="field-error">{{ $errors->first('import_file') }}</span>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-upload"></i> Import
                </button>
            </form>

            <details style="margin-top:14px;">
                <summary style="cursor:pointer;font-size:.82rem;color:var(--dash-muted);">
                    Expected CSV format
                </summary>
                <div style="margin-top:10px;font-size:.8rem;color:var(--dash-muted);line-height:1.6;">
                    <p style="margin:0 0 6px;">First row is a header. Columns used:</p>
                    <ul style="margin:0 0 10px;padding-left:18px;">
                        <li><code>question_text</code> (or <code>question</code>) — required</li>
                        <li><code>question_type</code> — multiple_choice, multiple_answer, true_false, identification, short_answer, essay</li>
                        <li><code>choice_1</code> … <code>choice_5</code> — the options</li>
                        <li><code>correct_answer</code> — which letter is right, e.g. <code>b</code></li>
                        <li><code>points</code>, <code>difficulty</code>, <code>explanation</code>, <code>tags</code> — optional</li>
                    </ul>
                    <p style="margin:0 0 6px;">Example:</p>
                    <pre style="margin:0;padding:10px;background:var(--dash-input,#0f172a);border-radius:8px;overflow:auto;">question_text,question_type,choice_1,choice_2,choice_3,choice_4,correct_answer,points
What is 2+2?,multiple_choice,3,4,5,6,b,1
Capital of France?,multiple_choice,Paris,Rome,Madrid,Bonn,a,2</pre>
                    <p style="margin-top:10px;">
                        TXT format: number each question, letter each choice, and mark the answer with <code>*</code>.
                    </p>
                    <pre style="margin:6px 0 0;padding:10px;background:var(--dash-input,#0f172a);border-radius:8px;overflow:auto;">1. What is 2+2?
a. 3
b. 4 *
c. 5
d. 6</pre>
                </div>
            </details>
        </div>
    </div>

    {{-- ── Write one ────────────────────────────────────────── --}}
    <div class="user-panel" style="margin-top:16px;">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen-to-square"></i> Write a question</h3>
            <span class="user-status">Saved to your bank and added here</span>
        </div>

        <div class="user-panel-body">
            <form method="POST" action="{{ route('instructor.courses.exams.questions.store', [$course, $exam]) }}">
                @csrf

                <div class="dash-stats" style="margin-bottom:16px;">
                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="question_type" id="edit-question-type" required>
                            @foreach([
                                \App\Models\Question::TYPE_MULTIPLE_CHOICE => 'Multiple choice',
                                \App\Models\Question::TYPE_MULTIPLE_ANSWER => 'Multiple answer',
                                \App\Models\Question::TYPE_TRUE_FALSE => 'True / False',
                                \App\Models\Question::TYPE_IDENTIFICATION => 'Identification',
                                \App\Models\Question::TYPE_SHORT_ANSWER => 'Short answer',
                                \App\Models\Question::TYPE_ESSAY => 'Essay',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('question_type', \App\Models\Question::TYPE_MULTIPLE_CHOICE) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('question_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Points <span class="required">*</span></label>
                        <input type="number" name="points" value="{{ old('points', 1) }}" min="0.5" max="1000" step="0.5" required>
                        <span class="field-error">{{ $errors->first('points') }}</span>
                    </div>
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label>Question <span class="required">*</span></label>
                    <textarea name="question_text" rows="3" required>{{ old('question_text') }}</textarea>
                    <span class="field-error">{{ $errors->first('question_text') }}</span>
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        @foreach([
                            \App\Models\Question::DIFFICULTY_EASY => 'Easy',
                            \App\Models\Question::DIFFICULTY_MEDIUM => 'Medium',
                            \App\Models\Question::DIFFICULTY_HARD => 'Hard',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('difficulty', \App\Models\Question::DIFFICULTY_MEDIUM) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="edit-choices-block" style="margin-bottom:16px;">
                    <label style="display:block;font-size:.85rem;margin-bottom:8px;">
                        Choices <span class="required" id="edit-choices-required">*</span>
                        <span style="font-weight:400;color:var(--dash-muted);font-size:.76rem;">
                            Tick the correct answer — at least one is required.
                        </span>
                    </label>

                    @php $rows = old('choices', [['choice_text' => ''], ['choice_text' => ''], ['choice_text' => ''], ['choice_text' => '']]) @endphp

                    @foreach ($rows as $i => $choice)
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                            <input type="checkbox" name="choices[{{ $i }}][is_correct]" value="1"
                                   @checked(old("choices.{$i}.is_correct"))
                                   style="width:16px;height:16px;" title="Correct answer">
                            <input type="text" name="choices[{{ $i }}][choice_text]"
                                   value="{{ is_array($choice) ? ($choice['choice_text'] ?? '') : $choice }}"
                                   placeholder="Choice {{ $i + 1 }}"
                                   style="flex:1;padding:8px 11px;border-radius:8px;border:1px solid var(--dash-line);background:var(--dash-input,#0f172a);color:inherit;font-size:.85rem;">
                        </div>
                    @endforeach

                    <span class="field-error">{{ $errors->first('choices') }}</span>
                </div>

                <div class="form-field" style="margin-bottom:16px;">
                    <label>Explanation <span style="font-weight:400;color:var(--dash-muted);font-size:.76rem;">shown after the exam</span></label>
                    <textarea name="explanation" rows="2">{{ old('explanation') }}</textarea>
                    <span class="field-error">{{ $errors->first('explanation') }}</span>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add question
                </button>
            </form>
        </div>
    </div>
@endif

<script>
    (function () {
        const needsChoices = @json(['multiple_choice', 'multiple_answer', 'true_false']);
        const typeSelect = document.getElementById('edit-question-type');

        if (!typeSelect) return;

        const block = document.getElementById('edit-choices-block');
        const requiredMark = document.getElementById('edit-choices-required');

        function sync() {
            const show = needsChoices.includes(typeSelect.value);
            block.hidden = !show;
            requiredMark.hidden = !show;

            block.querySelectorAll('input').forEach(function (input) {
                input.disabled = !show;
            });
        }

        typeSelect.addEventListener('change', sync);
        sync();
    })();
</script>
@endsection
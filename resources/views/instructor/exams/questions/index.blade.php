@extends('layouts.instructor')

@section('title', 'Add Questions — ' . $exam->title)

@section('content')
    <div class="dash-section">
        <div>
            <h3><i class="fa-solid fa-list-check"></i> Add questions to “{{ $exam->title }}”</h3>
            <span class="dash-section-kicker">
                {{ $exam->class->code }} · {{ $exam->getTypeLabel() }} · {{ $exam->questions()->count() }} already on this exam
            </span>
        </div>
        <a class="btn btn-secondary" href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}">
            <i class="fa-solid fa-arrow-left"></i> Back to exam
        </a>
    </div>

    @if ($exam->status !== \App\Models\Exam::STATUS_DRAFT)
        <div class="empty-state" style="margin-bottom:16px">
            <i class="fa-solid fa-circle-info"></i>
            This exam is {{ $exam->status }}, so its questions are locked. Reopen it as a draft to make changes.
        </div>
    @endif

    @if ($questions->isEmpty())
        <div class="empty-state">
            <i class="fa-solid fa-circle-info"></i>
            You have no questions in your bank yet. Create one below, or add them from the Test Bank.
        </div>
    @else
        <form method="POST" action="{{ route('instructor.courses.exams.questions.attach', [$course, $exam]) }}"
              id="attach-form">
            @csrf

            <div class="user-panel">
                <div class="user-panel-head">
                    <h3><i class="fa-solid fa-database"></i> Your questions ({{ $questions->count() }})</h3>
                    <span class="user-status" id="selected-count">0 selected</span>
                </div>

                <div class="user-panel-body">
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                        <input type="search" id="question-search" placeholder="Search question text or bank…"
                               style="flex:1 1 260px;padding:9px 12px;border-radius:8px;border:1px solid var(--dash-line);background:var(--dash-input,#0f172a);color:var(--dash-text,#eef4ff);font-size:.85rem;">
                        <button type="button" class="btn btn-secondary" id="select-all">Select all shown</button>
                        <button type="button" class="btn btn-secondary" id="select-none">Clear</button>
                    </div>

                    @foreach ($questions as $question)
                        @php $isAttached = in_array($question->id, $attached, true) @endphp
                        <label class="question-row"
                               data-search="{{ \Illuminate\Support\Str::lower($question->question_text.' '.$question->bank?->title) }}"
                               style="display:flex;gap:12px;align-items:flex-start;padding:12px;border:1px solid var(--dash-line);border-radius:10px;margin-bottom:8px;cursor:{{ $isAttached ? 'not-allowed' : 'pointer' }};opacity:{{ $isAttached ? '.55' : '1' }};">
                            <input type="checkbox"
                                   name="question_ids[]"
                                   value="{{ $question->id }}"
                                   class="question-check"
                                   @disabled($isAttached)
                                   style="margin-top:3px;width:16px;height:16px;flex-shrink:0;">

                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;justify-content:space-between;gap:10px;">
                                    <strong style="font-size:.85rem;">{{ $question->question_text }}</strong>

                                    {{-- The exam's own weighting, defaulting to the bank's. --}}
                                    <label style="display:flex;align-items:center;gap:4px;flex-shrink:0;font-size:.74rem;color:var(--dash-muted);">
                                        pts
                                        <input type="number"
                                               name="points[{{ $question->id }}]"
                                               value="{{ old('points.'.$question->id, $question->default_points ?? 1) }}"
                                               min="0.5" max="1000" step="0.5"
                                               class="points-input"
                                               style="width:74px;padding:4px 6px;border-radius:6px;border:1px solid var(--dash-line);background:var(--dash-input,#0f172a);color:inherit;font-size:.8rem;">
                                    </label>
                                </div>

                                <div style="margin-top:5px;font-size:.72rem;color:var(--dash-muted);display:flex;gap:10px;flex-wrap:wrap;">
                                    <span>{{ str_replace('_', ' ', $question->question_type) }}</span>
                                    <span>{{ $question->bank?->title ?? 'No bank' }}</span>
                                    <span>{{ ucfirst($question->difficulty ?? 'medium') }}</span>
                                    @if ($isAttached)
                                        <span style="color:#6ee7b7;">Already on this exam</span>
                                    @endif
                                </div>

                                @if ($question->choices->isNotEmpty())
                                    <ul style="margin:7px 0 0;padding-left:18px;color:var(--dash-muted);font-size:.8rem;">
                                        @foreach ($question->choices->sortBy('position') as $choice)
                                            <li style="{{ $choice->is_correct ? 'font-weight:700;color:#6ee7b7' : '' }}">
                                                {{ $choice->choice_text }}
                                                @if ($choice->is_correct)<i class="fa-solid fa-check" aria-hidden="true"></i>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </label>
                    @endforeach

                    <div style="margin-top:12px;">
                        <button type="submit" class="btn btn-primary" id="attach-submit" disabled>
                            <i class="fa-solid fa-paperclip"></i> Add selected to exam
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    {{-- ── Write a question inline ───────────────────────────────── --}}
    <div class="user-panel" style="margin-top:24px;">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen-to-square"></i> Write a question</h3>
            <span class="user-status">Saved to your bank, and added here</span>
        </div>

        <div class="user-panel-body">
            <form method="POST" action="{{ route('instructor.courses.exams.questions.store', [$course, $exam]) }}">
                @csrf

                <div class="dash-stats" style="margin-bottom:16px;">
                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="question_type" id="question-type" required>
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

                {{-- Choices are only meaningful for the auto-graded types. --}}
                <div id="choices-block" style="margin-bottom:16px;">
                    <label style="display:block;font-size:.85rem;margin-bottom:8px;">
                        Choices <span class="required" id="choices-required">*</span>
                        <span style="font-weight:400;color:var(--dash-muted);font-size:.76rem;">
                            Tick the correct answer — at least one is required.
                        </span>
                    </label>

                    @php $choiceRows = old('choices', [['choice_text' => ''], ['choice_text' => ''], ['choice_text' => ''], ['choice_text' => '']]) @endphp

                    @foreach ($choiceRows as $i => $choice)
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
                    <i class="fa-solid fa-plus"></i> Add question to this exam
                </button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const needsChoices = @json([
                'multiple_choice', 'multiple_answer', 'true_false'
            ]);

            const typeSelect = document.getElementById('question-type');
            const choicesBlock = document.getElementById('choices-block');
            const choicesRequired = document.getElementById('choices-required');

            function syncChoicesVisibility() {
                const show = needsChoices.includes(typeSelect.value);
                choicesBlock.hidden = !show;
                choicesRequired.hidden = !show;

                choicesBlock.querySelectorAll('input').forEach(function (input) {
                    input.disabled = !show;
                    input.required = false;
                });
            }

            typeSelect.addEventListener('change', syncChoicesVisibility);
            syncChoicesVisibility();

            // Picker: keep the counter and the submit button honest.
            const form = document.getElementById('attach-form');
            if (!form) return;

            const checks = Array.from(form.querySelectorAll('.question-check:not([disabled])'));
            const counter = document.getElementById('selected-count');
            const submit = document.getElementById('attach-submit');
            const search = document.getElementById('question-search');

            function selected() {
                return checks.filter(function (c) { return c.checked; }).length;
            }

            function sync() {
                const n = selected();
                counter.textContent = n + ' selected';
                submit.disabled = n === 0;
            }

            checks.forEach(function (c) { c.addEventListener('change', sync); });
            sync();

            document.getElementById('select-all').addEventListener('click', function () {
                form.querySelectorAll('.question-row').forEach(function (row) {
                    if (row.style.display === 'none') return;
                    const check = row.querySelector('.question-check');
                    if (check && !check.disabled) check.checked = true;
                });
                sync();
            });

            document.getElementById('select-none').addEventListener('click', function () {
                checks.forEach(function (c) { c.checked = false; });
                sync();
            });

            if (search) {
                search.addEventListener('input', function () {
                    const needle = search.value.trim().toLowerCase();
                    form.querySelectorAll('.question-row').forEach(function (row) {
                        row.style.display = !needle || row.dataset.search.includes(needle) ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endsection
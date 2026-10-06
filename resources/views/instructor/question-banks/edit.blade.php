@extends('layouts.instructor')

@section('title', 'Edit Question Bank')
@php $activeNav = 'question_banks'; @endphp

@php
    // Question types that need a list of choices to be answerable.
    $choiceTypes = ['multiple_choice', 'multiple_answer', 'true_false'];
    $singleAnswerTypes = ['multiple_choice', 'true_false'];

    // Free-text types whose rows in the same block are *accepted answers*
    // rather than options a student picks (§2). The grader compares the reply
    // against them, honouring questions.is_case_sensitive.
    $textKeyTypes = ['identification', 'short_answer'];

    // Everything that renders the block, so identification no longer ships
    // without a way to say what counts as right.
    $keyTypes = array_merge($choiceTypes, $textKeyTypes);

    $typeLabels = [
        'multiple_choice' => 'Multiple Choice',
        'multiple_answer' => 'Multiple Answer',
        'true_false' => 'True / False',
        'identification' => 'Identification',
        'short_answer' => 'Short Answer',
        'essay' => 'Essay',
    ];
@endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Question Bank"
        subtitle="Update bank details, and add, edit or remove questions."
        icon="fa-database"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $questionBank->status }}" />
            @if($questionBank->is_shared)<span class="user-status active">Shared</span>@endif
            <span class="user-status">{{ $questionBank->questions->count() }} questions</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.question_banks.show', $questionBank) }}" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View</a>
            <a href="{{ route('instructor.question_banks.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin:6px 0 0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ═══ Bank details ═══ --}}
    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Bank Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('instructor.question_banks.update', $questionBank) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $questionBank->title) }}" required>
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Code</label>
                        <input type="text" name="code" value="{{ old('code', $questionBank->code) }}" maxlength="50">
                        <span class="field-error">{{ $errors->first('code') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Category</label>
                        <input type="text" name="category" value="{{ old('category', $questionBank->category) }}" maxlength="100">
                        <span class="field-error">{{ $errors->first('category') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">— General —</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $questionBank->course_id) == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('course_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">— General —</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $questionBank->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $questionBank->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_shared" value="1" @checked(old('is_shared', $questionBank->is_shared))>
                            Share this bank with all instructors
                        </label>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3">{{ old('description', $questionBank->description) }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Bank Details</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ Questions ═══ --}}
    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Questions ({{ $questionBank->questions->count() }})</h3>
            <span class="user-status">Edit text, choices and settings below</span>
        </div>
        <div class="user-panel-body">
            @forelse($questionBank->questions as $index => $question)
                <div class="user-card" id="question-{{ $question->id }}"
                     style="padding:16px;border:1px solid var(--bcp-border,#e2e8f0);border-radius:10px;margin-bottom:16px;">
                    <form action="{{ route('instructor.question_banks.questions.update', [$questionBank, $question]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-grid">
                            <div class="form-field">
                                <label>Question {{ $index + 1 }} — Type <span class="required">*</span></label>
                                <select name="question_type" class="js-q-type">
                                    @foreach($typeLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($question->question_type === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Difficulty <span class="required">*</span></label>
                                <select name="difficulty">
                                    @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                        <option value="{{ $value }}" @selected($question->difficulty === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Points <span class="required">*</span></label>
                                <input type="number" name="default_points" step="0.5" min="0" value="{{ (float) $question->default_points }}" required>
                            </div>

                            <div class="form-field">
                                <label>Status</label>
                                <select name="status">
                                    @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                        <option value="{{ $value }}" @selected($question->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Category</label>
                                <select name="category_id">
                                    <option value="">Uncategorised</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected($question->category_id === $category->id)>{{ $category->name }}{{ $category->course?->code ? ' — '.$category->course->code : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Case Sensitive</label>
                                <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;min-height:38px;">
                                    <input type="checkbox" name="is_case_sensitive" value="1" class="js-case-sensitive"
                                           @checked($question->is_case_sensitive)>
                                    <span class="js-case-hint">Match “CPU” and “cpu” differently</span>
                                </label>
                                <span class="js-case-note" style="display:none;font-size:.78rem;color:#64748b;">
                                    Applies when this question is auto-marked.
                                </span>
                            </div>

                            <div class="form-field full">
                                <label>Question Text <span class="required">*</span></label>
                                <textarea name="question_text" rows="3" required>{{ $question->question_text }}</textarea>
                            </div>

                            <div class="form-field full">
                                <label class="js-key-label">Choices</label>
                                <div class="js-choices">
                                    @foreach($question->choices->sortBy('position') as $choice)
                                        <div class="js-choice-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                                            <input type="text" name="choice[{{ $choice->id }}][text]"
                                                   value="{{ $choice->choice_text }}" placeholder="Choice text"
                                                   style="flex:1;">
                                            <label class="checkbox-label js-correct-wrap" style="margin:0;white-space:nowrap;">
                                                <input type="checkbox" class="js-correct"
                                                       name="choice[{{ $choice->id }}][correct]" value="1"
                                                       @checked($choice->is_correct)>
                                                Correct
                                            </label>
                                        </div>
                                    @endforeach

                                    @for($i = 0; $i < 2; $i++)
                                        <div class="js-choice-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                                            <input type="text" name="new[{{ $i }}][text]" placeholder="New choice" style="flex:1;">
                                            <label class="checkbox-label js-correct-wrap" style="margin:0;white-space:nowrap;">
                                                <input type="checkbox" class="js-correct" name="new[{{ $i }}][correct]" value="1">
                                                Correct
                                            </label>
                                        </div>
                                    @endfor

                                    <button type="button" class="btn btn-secondary js-add-choice" style="margin-top:4px;">
                                        <i class="fa-solid fa-plus"></i> Add Choice
                                    </button>
                                </div>
                                <span class="js-key-hint" style="display:block;margin-top:6px;font-size:.8rem;color:#64748b;">Clear a choice's text and save to remove it.</span>
                            </div>

                            <div class="form-field full">
                                <label>Explanation</label>
                                <textarea name="explanation" rows="2">{{ $question->explanation }}</textarea>
                            </div>

                            <div class="form-field full">
                                <label>Tags</label>
                                <input type="text" name="tags" value="{{ is_array($question->tags) ? implode(', ', $question->tags) : '' }}"
                                       placeholder="comma separated, e.g. chapter1, genetics">
                            </div>
                        </div>

                        <div class="form-actions user-actions">
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Question</button>
                        </div>
                    </form>

                    <form action="{{ route('instructor.question_banks.questions.destroy', [$questionBank, $question]) }}" method="POST"
                          style="margin-top:10px;" onsubmit="return confirm('Delete this question?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete Question</button>
                    </form>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-list-check"></i>
                    <p>No questions yet. Add your first one using the form below.</p>
                </div>
            @endforelse

            {{-- ─── Add question ─── --}}
            <div class="user-card" id="add-question"
                 style="padding:16px;border:1px dashed var(--bcp-border,#cbd5e1);border-radius:10px;">
                <h4 style="margin:0 0 12px;color:#1e293b;"><i class="fa-solid fa-plus"></i> Add Question</h4>

                <form action="{{ route('instructor.question_banks.questions.store', $questionBank) }}" method="POST">
                    @csrf

                    <div class="form-grid">
                        <div class="form-field">
                            <label>Type <span class="required">*</span></label>
                            <select name="question_type" class="js-q-type">
                                @foreach($typeLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'multiple_choice')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Difficulty <span class="required">*</span></label>
                            <select name="difficulty">
                                @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'medium')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Points <span class="required">*</span></label>
                            <input type="number" name="default_points" step="0.5" min="0" value="1" required>
                        </div>

                        <div class="form-field">
                            <label>Status</label>
                            <select name="status">
                                @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                                    <option value="{{ $value }}" @selected($value === 'active')>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Uncategorised</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->name }}{{ $category->course?->code ? ' — '.$category->course->code : '' }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-field">
                            <label>Case Sensitive</label>
                            <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;min-height:38px;">
                                <input type="checkbox" name="is_case_sensitive" value="1" class="js-case-sensitive"
                                       @checked(old('is_case_sensitive'))>
                                <span class="js-case-hint">Match “CPU” and “cpu” differently</span>
                            </label>
                            <span class="js-case-note" style="display:none;font-size:.78rem;color:#64748b;">
                                Applies when this question is auto-marked.
                            </span>
                        </div>

                        <div class="form-field full">
                            <label>Question Text <span class="required">*</span></label>
                            <textarea name="question_text" rows="3" required placeholder="Type your question..."></textarea>
                        </div>

                        <div class="form-field full">
                            <label class="js-key-label">Choices</label>
                            <div class="js-choices">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="js-choice-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                                        <input type="text" name="new[{{ $i }}][text]" placeholder="Choice {{ $i + 1 }}" style="flex:1;">
                                        <label class="checkbox-label js-correct-wrap" style="margin:0;white-space:nowrap;">
                                            <input type="checkbox" class="js-correct" name="new[{{ $i }}][correct]" value="1">
                                            Correct
                                        </label>
                                    </div>
                                @endfor

                                <button type="button" class="btn btn-secondary js-add-choice" style="margin-top:4px;">
                                    <i class="fa-solid fa-plus"></i> Add Choice
                                </button>
                            </div>
                            <span class="js-key-hint" style="display:block;margin-top:6px;font-size:.8rem;color:#64748b;">Leave blank for essay, short answer and identification.</span>
                        </div>

                        <div class="form-field full">
                            <label>Explanation</label>
                            <textarea name="explanation" rows="2" placeholder="Optional explanation shown after answering"></textarea>
                        </div>

                        <div class="form-field full">
                            <label>Tags</label>
                            <input type="text" name="tags" placeholder="comma separated, e.g. chapter1, genetics">
                        </div>
                    </div>

                    <div class="form-actions user-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var CHOICE_TYPES = @json($choiceTypes);
        var SINGLE_ANSWER_TYPES = @json($singleAnswerTypes);
        var KEY_TYPES = @json($keyTypes);
        var TEXT_KEY_TYPES = @json($textKeyTypes);

        var KEY_LABELS = {
            choice: 'Choices',
            answer: 'Accepted Answers'
        };

        var KEY_HINTS = {
            choice: 'Clear a choice\'s text and save to remove it.',
            blank: 'Leave blank for essay, short answer and identification.',
            answer: 'Each accepted answer is one row — check it to accept it. Leave all unchecked to grade by hand.'
        };

        // The block is the option list for choice types, the answer key for
        // free-text types (§2), and absent for essays.
        function syncKeyVisibility(form) {
            var type = form.querySelector('.js-q-type');
            var block = form.querySelector('.js-choices');
            var label = form.querySelector('.js-key-label');
            var hint = form.querySelector('.js-key-hint');
            var caseWrap = form.querySelector('.js-case-sensitive');
            var caseNote = form.querySelector('.js-case-note');

            if (!type || !block) return;

            var value = type.value;
            var isChoice = CHOICE_TYPES.indexOf(value) !== -1;
            var isTextKey = TEXT_KEY_TYPES.indexOf(value) !== -1;

            block.style.display = KEY_TYPES.indexOf(value) !== -1 ? '' : 'none';

            if (label) {
                label.textContent = isTextKey ? KEY_LABELS.answer : KEY_LABELS.choice;
            }

            if (hint) {
                // The blank hint belongs to the add form (no stored rows yet);
                // the edit form always has rows to talk about.
                if (!isChoice && !isTextKey) {
                    hint.textContent = KEY_HINTS.blank;
                } else {
                    hint.textContent = isTextKey ? KEY_HINTS.answer : KEY_HINTS.choice;
                }
            }

            // Case sensitivity only changes the outcome where a reply is matched
            // against stored wording; hiding it elsewhere avoids a setting that
            // silently does nothing.
            if (caseWrap) {
                caseWrap.style.display = isTextKey ? '' : 'none';
            }
            if (caseNote) {
                caseNote.style.display = isTextKey ? '' : 'none';
            }
        }

        document.querySelectorAll('.user-panel-body form').forEach(function (form) {
            if (!form.querySelector('.js-q-type')) return;

            var typeSelect = form.querySelector('.js-q-type');
            syncKeyVisibility(form);

            typeSelect.addEventListener('change', function () {
                syncKeyVisibility(form);
            });

            // Add another choice row to whichever question form was clicked.
            form.querySelectorAll('.js-add-choice').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var block = btn.parentElement;
                    var template = block.querySelector('.js-choice-row');
                    var clone = template.cloneNode(true);

                    // Renumber the new row so it posts as a distinct entry.
                    var index = block.querySelectorAll('.js-choice-row').length;

                    clone.querySelectorAll('input[type="text"]').forEach(function (input) {
                        input.value = '';
                        input.setAttribute('name', 'new[' + index + '][text]');
                    });

                    var correct = clone.querySelector('.js-correct');
                    if (correct) {
                        correct.checked = false;
                        correct.name = 'new[' + index + '][correct]';
                    }

                    block.insertBefore(clone, btn);
                    syncKeyVisibility(form);
                });
            });

            // Single-answer types behave like radio buttons. The checkboxes keep
            // their own names — each carries its own choice id — and exclusivity
            // is enforced here, so a selection can never be posted against the
            // wrong choice.
            form.addEventListener('change', function (event) {
                if (!event.target.classList.contains('js-correct')) return;
                if (SINGLE_ANSWER_TYPES.indexOf(typeSelect.value) === -1) return;
                if (!event.target.checked) return;

                form.querySelectorAll('.js-correct').forEach(function (box) {
                    if (box !== event.target) box.checked = false;
                });
            });
        });
    })();
</script>
@endpush
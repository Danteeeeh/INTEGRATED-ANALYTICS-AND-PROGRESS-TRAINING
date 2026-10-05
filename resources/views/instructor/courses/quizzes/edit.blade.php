@extends('layouts.instructor')

@section('title', 'Edit Quiz')
@php
    $activeNav = 'quizzes';
    $pageTitle = 'Edit Quiz';
    $pageIcon = '<i class="fa-solid fa-pen"></i>';
    $formAction = route('instructor.courses.quizzes.update', [$course, $quiz]);
    $backUrl = route('instructor.courses.quizzes.show', [$course, $quiz]);
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-pen"></i>
            Edit Quiz
        </h2>
        <div class="page-actions">
            <a href="{{ $backUrl }}" class="btn-modal-cancel" style="padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Quiz
            </a>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .cc-hero {
            position: relative; overflow: hidden;
            display: flex; align-items: center; gap: 16px;
            padding: 20px 26px; margin-bottom: 22px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 16px;
            background:
                radial-gradient(circle at 90% 10%, rgba(139,92,246,.3), transparent 42%),
                linear-gradient(135deg, rgba(109,40,217,.8), rgba(10,16,32,.96));
            box-shadow: var(--bcp-shadow, 0 16px 36px rgba(3,8,20,.3));
        }
        .cc-icon {
            width: 50px; height: 50px; flex: none;
            display: grid; place-items: center;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            color: #fff; font-size: 21px; border-radius: 13px;
            box-shadow: 0 10px 22px rgba(139,92,246,.35);
        }
        .cc-body { min-width: 0; }
        .cc-kicker {
            display: block; color: var(--bcp-cyan-400, #62c9f5);
            font-size: .64rem; font-weight: 800; letter-spacing: .15em; text-transform: uppercase; margin-bottom: 4px;
        }
        .cc-body h3 { margin: 0; color: #fff; font-size: 1.05rem; font-weight: 800; }
        .cc-body p { margin: 4px 0 0; color: #c8d9f8; font-size: .78rem; }
        .cc-badge {
            margin-left: auto; flex: none;
            padding: 6px 13px; border-radius: 999px;
            background: rgba(139,92,246,.14); border: 1px solid rgba(139,92,246,.3);
            color: #c4b5fd; font-size: .72rem; font-weight: 750;
            display: inline-flex; align-items: center; gap: 6px;
        }

        .cc-card {
            max-width: 960px; margin: 0 auto;
            background: var(--bcp-card, var(--dash-surface, #151c2c));
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            border-radius: 16px; overflow: hidden;
            box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3));
        }
        .cc-card-head {
            display: flex; align-items: center; gap: 10px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: linear-gradient(90deg, rgba(139,92,246,.12), transparent);
        }
        .cc-card-head h3 {
            margin: 0; color: var(--bcp-ink, #eef4ff);
            font-size: .85rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
            display: flex; align-items: center; gap: 9px;
        }
        .cc-card-head h3 i { color: #a78bfa; font-size: .82rem; }

        .cc-section { padding: 22px 24px; }
        .cc-section + .cc-section { border-top: 1px solid rgba(153,174,214,.1); }
        .cc-section-title {
            display: flex; align-items: center; gap: 8px;
            margin: 0 0 16px; color: var(--bcp-ink, #eef4ff);
            font-size: .78rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase;
        }
        .cc-section-title i { color: #a78bfa; }

        .cc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .cc-field { display: flex; flex-direction: column; gap: 6px; }
        .cc-field.full { grid-column: 1 / -1; }
        .cc-field label { color: var(--bcp-muted, #98a7c4); font-size: .72rem; font-weight: 750; }
        .cc-field label .req { color: #fda4af; }
        .cc-field input, .cc-field select, .cc-field textarea {
            width: 100%; min-height: 40px; padding: 9px 12px;
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px;
            background: #101625; color: var(--bcp-ink, #eef4ff);
            font-size: .88rem; transition: border-color .18s, box-shadow .18s;
        }
        .cc-field input:focus, .cc-field select:focus, .cc-field textarea:focus {
            outline: 0; border-color: #a78bfa; box-shadow: 0 0 0 3px rgba(139,92,246,.18);
        }
        .cc-field textarea { resize: vertical; min-height: 86px; }
        .cc-field input::placeholder, .cc-field textarea::placeholder { color: #7f91b0; }
        .cc-hint { color: var(--bcp-muted, #98a7c4); font-size: .68rem; margin-top: 4px; }
        .cc-char { display: block; text-align: right; color: var(--bcp-muted, #98a7c4); font-size: .66rem; font-weight: 600; margin-top: 4px; }

        .cc-footer {
            display: flex; justify-content: flex-end; gap: 10px;
            padding: 18px 24px;
            border-top: 1px solid var(--bcp-line, rgba(153,174,214,.18));
            background: rgba(77,143,240,.04);
        }
        .cc-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 24px; border-radius: 9px;
            font-size: .84rem; font-weight: 700; text-decoration: none; cursor: pointer;
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .cc-btn:hover { transform: translateY(-1px); }
        .cc-btn-cancel {
            color: var(--bcp-text, #eef4ff);
            background: var(--dash-surface-raised, #1b2437);
            border: 1px solid var(--bcp-line, rgba(153,174,214,.18));
        }
        .cc-btn-cancel:hover { color: var(--bcp-cyan-400, #62c9f5); background: rgba(77,143,240,.15); }
        .cc-btn-save {
            color: #fff;
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            border: 1px solid #7c3aed;
            box-shadow: 0 8px 20px rgba(139,92,246,.3);
        }
        .cc-btn-save:hover { background: linear-gradient(135deg, #a78bfa, #8b5cf6); }

        @media (max-width: 680px) {
            .cc-grid { grid-template-columns: 1fr; }
            .cc-hero { padding: 16px 18px; }
            .cc-badge { display: none; }
            .cc-footer { flex-direction: column-reverse; }
            .cc-btn { justify-content: center; }
        }
    </style>

    {{-- ═══ HERO ═══ --}}
    <section class="cc-hero">
        <div class="cc-icon"><i class="fa-solid fa-pen"></i></div>
        <div class="cc-body">
            <span class="cc-kicker">Course assessment</span>
            <h3>Edit Quiz — {{ $quiz->title }}</h3>
            <p>{{ $course->code }} · Modify quiz settings and questions</p>
        </div>
        <span class="cc-badge"><i class="fa-solid fa-circle-question"></i> {{ $quiz->questions->count() }} questions</span>
    </section>

    {{-- ═══ FORM ═══ --}}
    <div class="cc-card">
        <div class="cc-card-head">
            <h3><i class="fa-solid fa-pen"></i> Quiz Settings</h3>
        </div>
        <form method="POST" action="{{ $formAction }}" id="editForm" data-dirty-warn="true">
            @csrf
            @method('PUT')
            <div class="cc-section">
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Quiz Title <span class="req">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required placeholder="e.g. Chapter 1 Quiz" maxlength="255" data-char-count="titleCount">
                        <span class="cc-char" id="titleCount">0 / 255</span>
                        @error('title')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Class <span class="req">*</span></label>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" {{ (string) old('class_id', $quiz->class_id) === (string) $class->id ? 'selected' : '' }}>{{ $class->code }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Status <span class="req">*</span></label>
                        <select name="status" required id="statusField">
                            <option value="draft" {{ old('status', $quiz->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $quiz->status) == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="closed" {{ old('status', $quiz->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                        @error('status')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-sliders"></i> Attempt &amp; Scoring</div>
                <div class="cc-grid">
                    <div class="cc-field">
                        <label>Time Limit (minutes)</label>
                        <input type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}" min="1" class="form-input">
                        <span class="cc-hint">Ilang minuto pwedeng sagutan</span>
                        @error('time_limit_minutes')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Attempt Limit</label>
                        <input type="number" name="attempt_limit" value="{{ old('attempt_limit', $quiz->attempt_limit) }}" min="1" class="form-input">
                        <span class="cc-hint">Ilang beses pwedeng subukan</span>
                        @error('attempt_limit')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Passing Score (%)</label>
                        <input type="number" name="passing_score_percent" value="{{ old('passing_score_percent', $quiz->passing_score_percent) }}" min="0" max="100" class="form-input">
                        <span class="cc-hint">Minimum na marka para pumasa</span>
                        @error('passing_score_percent')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field">
                        <label>Result Visibility <span class="req" id="resultVisibilityReq">*</span></label>
                        <select name="result_visibility" id="resultVisibilityField">
                            @foreach($resultVisibilityOptions as $value => $label)
                                <option value="{{ $value }}" {{ old('result_visibility', $quiz->result_visibility) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="cc-hint">Kapag makikita ng estudyante ang resulta</span>
                        @error('result_visibility')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-align-left"></i> Details</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Quiz description..." data-char-count="descCount">{{ old('description', $quiz->description) }}</textarea>
                        <span class="cc-char" id="descCount">0 characters</span>
                        @error('description')<span class="error-message">{{ $message }}</span>@enderror
                    </div>

                    <div class="cc-field full">
                        <label>Instructions</label>
                        <textarea name="instructions" rows="3" placeholder="Instructions para sa mga estudyante..." data-char-count="instCount">{{ old('instructions', $quiz->instructions) }}</textarea>
                        <span class="cc-char" id="instCount">0 characters</span>
                        @error('instructions')<span class="error-message">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-list-check"></i> Questions</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <div id="questionsContainer">
                            <div class="cc-hint" style="margin-bottom: 12px;">Add questions to your quiz. You can add multiple choice questions.</div>
                            <button type="button" class="cc-btn cc-btn-save" onclick="addQuestion()" style="font-size: 0.78rem; padding: 9px 18px;">
                                <i class="fa-solid fa-plus"></i> Add Question
                            </button>
                            <div id="questionsList" style="margin-top: 20px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cc-section">
                <div class="cc-section-title"><i class="fa-solid fa-file-import"></i> Import Questions (Optional)</div>
                <div class="cc-grid">
                    <div class="cc-field full">
                        <label>Upload Questions File (CSV or TXT)</label>
                        <input type="file" name="import_file" accept=".csv,.txt" class="form-input">
                        <span class="cc-hint">Upload a CSV or TXT file with questions. Format: numbered questions with choices marked with * for correct answers.</span>
                    </div>
                    <div class="cc-field">
                        <label>Add to Question Bank (Optional)</label>
                        <select name="question_bank_id" class="form-input">
                            <option value="">No Question Bank</option>
                            @foreach(\App\Models\QuestionBank::where('created_by', auth()->id())->orWhere('course_id', $course->id)->active()->get() as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->title }}</option>
                            @endforeach
                        </select>
                        <span class="cc-hint">Questions will also be saved to this question bank for reuse</span>
                    </div>
                </div>
            </div>

            <div class="cc-footer">
                <a href="{{ $backUrl }}" class="cc-btn cc-btn-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Cancel
                </a>
                <button type="submit" class="cc-btn cc-btn-save">
                    <i class="fa-solid fa-save"></i>
                    Update Quiz
                </button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('[data-char-count]').forEach(el => {
            const counter = document.getElementById(el.dataset.charCount);
            const update = () => {
                if (!counter) return;
                const max = el.maxLength;
                counter.textContent = max ? `${el.value.length.toLocaleString()} / ${max}` : el.value.length.toLocaleString() + ' characters';
            };
            el.addEventListener('input', update);
            update();
        });
        const form = document.getElementById('editForm');
        let dirty = false;
        form.addEventListener('input', () => { dirty = true; });
        form.addEventListener('change', () => { dirty = true; });
        form.addEventListener('submit', () => { dirty = false; });
        window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

        // Conditional required fields based on status
        const statusField = document.getElementById('statusField');
        const resultVisibilityField = document.getElementById('resultVisibilityField');
        const resultVisibilityReq = document.getElementById('resultVisibilityReq');

        function updateRequiredFields() {
            const isDraft = statusField.value === 'draft';

            if (isDraft) {
                resultVisibilityField.removeAttribute('required');
                resultVisibilityReq.style.display = 'none';
            } else {
                resultVisibilityField.setAttribute('required', 'required');
                resultVisibilityReq.style.display = 'inline';
            }
        }

        if (statusField) {
            statusField.addEventListener('change', updateRequiredFields);
            updateRequiredFields();
        }

        // Questions management
        let questionCount = 0;

        // Load existing questions
        @php
            $existingQuestions = [];
            foreach($quiz->questions as $q) {
                $choices = [];
                $correctIndex = 1;
                foreach($q->choices as $index => $c) {
                    $choices[] = $c->text;
                    if ($c->is_correct) {
                        $correctIndex = $index + 1;
                    }
                }
                $existingQuestions[] = [
                    'text' => $q->question_text,
                    'points' => $q->pivot->points ?? 1,
                    'choices' => $choices,
                    'correct_choice' => $correctIndex
                ];
            }
        @endphp

        const existingQuestions = @json($existingQuestions);

        // Initialize with existing questions
        function loadExistingQuestions() {
            existingQuestions.forEach(q => {
                addQuestion(q.text, q.points, q.choices, q.correct_choice);
            });
        }

        function addQuestion(text = '', points = 1, choices = ['', '', '', ''], correctChoice = 1) {
            questionCount++;
            const questionsList = document.getElementById('questionsList');
            const questionDiv = document.createElement('div');
            questionDiv.className = 'question-item';
            questionDiv.style.cssText = 'background: rgba(139,92,246,.08); border: 1px solid rgba(139,92,246,.2); border-radius: 12px; padding: 20px; margin-bottom: 16px;';
            questionDiv.id = 'question-' + questionCount;

            let choicesHtml = '';
            for (let i = 0; i < 4; i++) {
                const choiceValue = choices[i] || '';
                const isChecked = (i + 1) === correctChoice ? 'checked' : '';
                choicesHtml += `
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <input type="radio" name="questions[${questionCount}][correct_choice]" value="${i + 1}" ${isChecked} style="accent-color: #8b5cf6; cursor: pointer;">
                        <input type="text" name="questions[${questionCount}][choices][${i + 1}]" placeholder="Choice ${i + 1}" required value="${choiceValue}" style="flex: 1; min-height: 38px; padding: 8px 12px; border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 8px; background: #101625; color: var(--bcp-ink, #eef4ff); font-size: 0.85rem;">
                    </div>
                `;
            }

            questionDiv.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                    <span style="color: #a78bfa; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">Question ${questionCount}</span>
                    <button type="button" onclick="removeQuestion(${questionCount})" style="background: rgba(239,68,68,.15); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.75rem; font-weight: 600;">
                        <i class="fa-solid fa-trash"></i> Remove
                    </button>
                </div>
                <div class="cc-field" style="margin-bottom: 14px;">
                    <label>Question Text <span class="req">*</span></label>
                    <textarea name="questions[${questionCount}][text]" rows="2" required placeholder="Enter your question here..." style="width: 100%; min-height: 70px; padding: 9px 12px; border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px; background: #101625; color: var(--bcp-ink, #eef4ff); font-size: 0.88rem;">${text}</textarea>
                </div>
                <div class="cc-field" style="margin-bottom: 14px;">
                    <label>Points</label>
                    <input type="number" name="questions[${questionCount}][points]" value="${points}" min="1" style="width: 100%; min-height: 40px; padding: 9px 12px; border: 1px solid var(--bcp-line, rgba(153,174,214,.18)); border-radius: 9px; background: #101625; color: var(--bcp-ink, #eef4ff); font-size: 0.88rem;">
                </div>
                <div style="margin-top: 12px;">
                    <label style="color: var(--bcp-muted, #98a7c4); font-size: 0.72rem; font-weight: 750; margin-bottom: 8px; display: block;">Choices (mark the correct answer)</label>
                    <div id="choices-${questionCount}">
                        ${choicesHtml}
                    </div>
                </div>
            `;

            questionsList.appendChild(questionDiv);
        }

        function removeQuestion(id) {
            const questionDiv = document.getElementById('question-' + id);
            if (questionDiv) {
                questionDiv.remove();
            }
        }

        // Load existing questions on page load
        loadExistingQuestions();
    </script>
@endsection

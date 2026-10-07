{{--
    Shared exam form fields for create + edit.

    Expects:
      $exam        (App\Models\Exam)  — always a new/empty model on create
      $courses     (Collection)
      $classes     (Collection)
      $action      (string)   form action URL
      $method      (string)   'POST' or 'PUT'
--}}
@csrf
@if(($method ?? 'POST') === 'PUT')
    @method('PUT')
@endif

<div class="form-grid">
    <div class="form-field full">
        <label>Exam Title <span class="required">*</span></label>
        <input type="text" name="title" value="{{ old('title', $exam->title) }}" required placeholder="e.g. Midterm Examination">
        <span class="field-error">{{ $errors->first('title') }}</span>
    </div>

    <div class="form-field">
        <label>Class <span class="required">*</span></label>
        <select name="class_id" required id="examClassId">
            <option value="">Select a class</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" 
                    data-course-id="{{ $class->course_id }}"
                    @selected(old('class_id', $exam->class_id) == $class->id)>
                    {{ $class->code }} — {{ $class->course?->title }}
                </option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('class_id') }}</span>
    </div>

    <div class="form-field">
        <label>Course</label>
        <select name="course_id" id="examCourseId" disabled>
            <option value="">— Auto from class —</option>
            @foreach($courses as $course)
                <option value="{{ $course->id }}" @selected(old('course_id', $exam->course_id) == $course->id)>
                    {{ $course->code }} — {{ $course->title }}
                </option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('course_id') }}</span>
    </div>

    {{-- Required by the controller. The instructor form never had this
         field, so exam_type was absent from the payload and ExamService
         raised "Undefined array key \"exam_type\"". --}}
    {{-- The three semestral assessments. Exams created before this field
         existed hold a legacy "module" value; it is offered here so editing
         one does not fail validation on its own unchanged field. --}}
    @php($legacyType = isset($exam) && ! array_key_exists($exam->exam_type ?? '', \App\Models\Exam::typeOptions()) ? $exam->exam_type : null)
    <div class="form-field">
        <label>Type <span class="required">*</span></label>
        <select name="exam_type" required>
            @if ($legacyType)
                <option value="{{ $legacyType }}" selected>{{ ucfirst($legacyType) }} (legacy)</option>
            @endif
            @foreach(\App\Models\Exam::typeOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('exam_type', $exam->exam_type ?? \App\Models\Exam::TYPE_PRELIM) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('exam_type') }}</span>
    </div>

    <div class="form-field">
        <label>Duration (minutes) <span class="required">*</span></label>
        <input type="number" name="duration_minutes" min="1" value="{{ old('duration_minutes', $exam->duration_minutes ?: 60) }}" required>
        <span class="field-error">{{ $errors->first('duration_minutes') }}</span>
    </div>

    <div class="form-field">
        <label>Attempt Limit</label>
        <input type="number" name="attempt_limit" min="1" value="{{ old('attempt_limit', $exam->attempt_limit ?: 1) }}">
        <small style="color:var(--dash-muted);">1 = one-shot only</small>
        <span class="field-error">{{ $errors->first('attempt_limit') }}</span>
    </div>

    <div class="form-field">
        <label>Passing Score (%)</label>
        <input type="number" name="passing_score_percent" min="0" max="100" value="{{ old('passing_score_percent', $exam->passing_score_percent ?: 60) }}">
        <span class="field-error">{{ $errors->first('passing_score_percent') }}</span>
    </div>

    <div class="form-field">
        <label>Grade Weight (%)</label>
        <input type="number" name="grade_weight" min="0" max="100" value="{{ old('grade_weight', $exam->grade_weight ?: 30) }}">
        <span class="field-error">{{ $errors->first('grade_weight') }}</span>
    </div>

    <div class="form-field">
        <label>Status <span class="required">*</span></label>
        <select name="status" required>
            @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $exam->status ?: 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('status') }}</span>
    </div>

    {{-- Schedule --}}
    <div class="form-field">
        <label>Opens At</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $exam->starts_at?->format('Y-m-d\TH:i')) }}">
        <span class="field-error">{{ $errors->first('starts_at') }}</span>
    </div>

    <div class="form-field">
        <label>Closes At</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $exam->ends_at?->format('Y-m-d\TH:i')) }}">
        <span class="field-error">{{ $errors->first('ends_at') }}</span>
    </div>

    <div class="form-field">
        <label>Daily Open Time</label>
        <input type="time" name="allowed_start_time" value="{{ old('allowed_start_time', $exam->allowed_start_time) }}">
        <small style="color:var(--dash-muted);">Optional time-of-day gate</small>
        <span class="field-error">{{ $errors->first('allowed_start_time') }}</span>
    </div>

    <div class="form-field">
        <label>Daily Close Time</label>
        <input type="time" name="allowed_end_time" value="{{ old('allowed_end_time', $exam->allowed_end_time) }}">
        <small style="color:var(--dash-muted);">After this, the exam locks</small>
        <span class="field-error">{{ $errors->first('allowed_end_time') }}</span>
    </div>

    {{-- Behaviour --}}
    <div class="form-field">
        <label>Result Visibility <span class="required">*</span></label>
        <select name="result_visibility" required>
            @foreach([
                'immediately' => 'Immediately after submitting',
                'after_grading' => 'After grading',
                'after_all_submissions' => 'After all students submit',
                'after_date' => 'On a release date',
                'never' => 'Never show results',
            ] as $value => $label)
                <option value="{{ $value }}" @selected(old('result_visibility', $exam->result_visibility ?: 'after_grading') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('result_visibility') }}</span>
    </div>

    <div class="form-field full">
        <label>Results Release Date</label>
        <input type="date" name="results_release_date" value="{{ old('results_release_date', $exam->results_release_date?->format('Y-m-d')) }}">
        <span class="field-error">{{ $errors->first('results_release_date') }}</span>
    </div>

    {{-- Proctoring --}}
    <div class="form-field">
        <label>Proctoring Method</label>
        <select name="proctoring_method">
            <option value="">— None —</option>
            @foreach(['in_person' => 'In person', 'online' => 'Online', 'ai_proctor' => 'AI proctored', 'hybrid' => 'Hybrid'] as $value => $label)
                <option value="{{ $value }}" @selected(old('proctoring_method', $exam->proctoring_method) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <span class="field-error">{{ $errors->first('proctoring_method') }}</span>
    </div>

    <div class="form-field">
        <label>Proctoring Instructions</label>
        <textarea name="proctoring_instructions" rows="2">{{ old('proctoring_instructions', $exam->proctoring_instructions) }}</textarea>
        <span class="field-error">{{ $errors->first('proctoring_instructions') }}</span>
    </div>

    {{-- Toggles --}}
    <div class="form-field full">
        <label class="checkbox-label"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $exam->shuffle_questions ?? true))> Shuffle question order</label>
        <label class="checkbox-label"><input type="checkbox" name="shuffle_choices" value="1" @checked(old('shuffle_choices', $exam->shuffle_choices ?? true))> Shuffle answer choices</label>
        <label class="checkbox-label"><input type="checkbox" name="allow_navigation" value="1" @checked(old('allow_navigation', $exam->allow_navigation))> Allow students to navigate back and forth</label>
        <label class="checkbox-label"><input type="checkbox" name="auto_submit_on_timeout" value="1" @checked(old('auto_submit_on_timeout', $exam->auto_submit_on_timeout ?? true))> Auto-submit when time runs out</label>
        <label class="checkbox-label"><input type="checkbox" name="allow_review" value="1" @checked(old('allow_review', $exam->allow_review))> Allow students to review their answers</label>
        <label class="checkbox-label"><input type="checkbox" name="show_correct_answers" value="1" @checked(old('show_correct_answers', $exam->show_correct_answers))> Show correct answers after grading</label>
        <label class="checkbox-label"><input type="checkbox" name="show_score" value="1" @checked(old('show_score', $exam->show_score))> Show the numeric score</label>
        <label class="checkbox-label"><input type="checkbox" name="requires_proctoring" value="1" @checked(old('requires_proctoring', $exam->requires_proctoring))> Requires proctoring</label>
        <label class="checkbox-label"><input type="checkbox" name="record_session" value="1" @checked(old('record_session', $exam->record_session))> Record the session</label>
        <label class="checkbox-label"><input type="checkbox" name="detect_tab_switch" value="1" @checked(old('detect_tab_switch', $exam->detect_tab_switch))> Detect tab switching</label>
        <label class="checkbox-label"><input type="checkbox" name="detect_copy_paste" value="1" @checked(old('detect_copy_paste', $exam->detect_copy_paste))> Detect copy/paste</label>
    </div>

    {{-- Text --}}
    <div class="form-field full">
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Short summary shown to students">{{ old('description', $exam->description) }}</textarea>
        <span class="field-error">{{ $errors->first('description') }}</span>
    </div>

    <div class="form-field full">
        <label>Instructions</label>
        <textarea name="instructions" rows="4" placeholder="What students should read before starting">{{ old('instructions', $exam->instructions) }}</textarea>
        <span class="field-error">{{ $errors->first('instructions') }}</span>
    </div>

    <div class="form-field">
        <label>Video URL</label>
        <input type="url" name="video_url" value="{{ old('video_url', $exam->video_url) }}" placeholder="https://...">
        <span class="field-error">{{ $errors->first('video_url') }}</span>
    </div>

    <div class="form-field">
        <label>Video Duration (minutes)</label>
        <input type="number" name="video_duration_minutes" min="1" value="{{ old('video_duration_minutes', $exam->video_duration_minutes) }}">
        <span class="field-error">{{ $errors->first('video_duration_minutes') }}</span>
    </div>
</div>

<script>
    // Auto-populate course from class selection
    const classSelect = document.getElementById('examClassId');
    const courseSelect = document.getElementById('examCourseId');

    if (classSelect && courseSelect) {
        classSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset) {
                // The option should have data-course-id attribute
                const courseId = selectedOption.dataset.courseId;
                if (courseId) {
                    courseSelect.value = courseId;
                }
            }
        });
    }
</script>
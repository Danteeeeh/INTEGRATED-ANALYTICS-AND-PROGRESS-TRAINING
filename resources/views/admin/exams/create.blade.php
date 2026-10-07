@extends('layouts.admin')

@section('title', 'New Exam')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Create Exam"
        subtitle="Set the rules now; attach questions from the Test Bank afterwards."
        icon="fa-file-signature"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            <ul style="margin:6px 0 0 18px;padding:0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.exams.store') }}">
        @csrf

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-circle-info"></i> Exam details</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title') }}" required>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            <option value="">Choose a class…</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) old('class_id') === $class->id)>
                                    {{ $class->code }}@if($class->course) — {{ $class->course->title }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Course</label>
                        <select name="course_id">
                            <option value="">Infer from class</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((int) old('course_id') === $course->id)>{{ $course->code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="exam_type" required>
                            @foreach(\App\Models\Exam::typeOptions() as $value => $label)
                                <option value="{{ $value }}" @selected(old('exam_type', \App\Models\Exam::TYPE_PRELIM) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Duration (minutes) <span class="required">*</span></label>
                        <input type="number" name="duration_minutes" min="1" value="{{ old('duration_minutes', 60) }}" required>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="2">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-field full">
                        <label>Instructions shown to students</label>
                        <textarea name="instructions" rows="3" placeholder="e.g. Answer all questions. One sitting only.">{{ old('instructions') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-sliders"></i> Scoring &amp; attempts</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Attempt limit</label>
                        <input type="number" name="attempt_limit" min="1" value="{{ old('attempt_limit', 1) }}">
                    </div>

                    <div class="form-field">
                        <label>Passing score (%)</label>
                        <input type="number" name="passing_score_percent" min="0" max="100" value="{{ old('passing_score_percent', 60) }}">
                    </div>

                    <div class="form-field">
                        <label>Grade weight (%)</label>
                        <input type="number" name="grade_weight" min="0" max="100" value="{{ old('grade_weight', 30) }}">
                    </div>

                    <div class="form-field">
                        <label>Result visibility <span class="required">*</span></label>
                        <select name="result_visibility" required>
                            @foreach([
                                'after_grading' => 'After grading',
                                'immediately' => 'Immediately',
                                'after_all_submissions' => 'After all submissions',
                                'after_date' => 'After a set date',
                                'never' => 'Never show results',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('result_visibility', 'after_grading') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Status <span class="required">*</span></label>
                        <select name="status" required>
                            @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="show_score" value="1" @checked(old('show_score'))>
                            <span>Show the score to the student</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="show_correct_answers" value="1" @checked(old('show_correct_answers'))>
                            <span>Show the correct answers after grading</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="allow_review" value="1" @checked(old('allow_review'))>
                            <span>Let students review their answers</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-shuffle"></i> Delivery</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Opens at</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}">
                    </div>

                    <div class="form-field">
                        <label>Closes at</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}">
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', true))>
                            <span>Shuffle questions per student</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="shuffle_choices" value="1" @checked(old('shuffle_choices', true))>
                            <span>Shuffle the choices</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="allow_navigation" value="1" @checked(old('allow_navigation'))>
                            <span>Allow students to move between questions</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="auto_submit_on_timeout" value="1" @checked(old('auto_submit_on_timeout', true))>
                            <span>Auto-submit when time runs out</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-shield-halved"></i> Proctoring</h3></div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="requires_proctoring" value="1" @checked(old('requires_proctoring'))>
                            <span>This exam is proctored</span>
                        </label>
                    </div>

                    <div class="form-field">
                        <label>Method</label>
                        <select name="proctoring_method">
                            <option value="">Not set</option>
                            @foreach(['in_person' => 'In person', 'online' => 'Online', 'ai_proctor' => 'AI proctor', 'hybrid' => 'Hybrid'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('proctoring_method') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-field full">
                        <label>Proctoring instructions</label>
                        <textarea name="proctoring_instructions" rows="2">{{ old('proctoring_instructions') }}</textarea>
                    </div>

                    <div class="form-field full">
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="record_session" value="1" @checked(old('record_session'))>
                            <span>Record the session</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="detect_tab_switch" value="1" @checked(old('detect_tab_switch'))>
                            <span>Detect tab switching</span>
                        </label>
                        <label class="checkbox-label" style="display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" name="detect_copy_paste" value="1" @checked(old('detect_copy_paste'))>
                            <span>Detect copy &amp; paste</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Create Exam</button>
            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
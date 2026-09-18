@extends('layouts.admin')

@section('title', 'Edit Quiz')
@php $activeNav = 'quizzes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Quiz"
        subtitle="Update quiz settings, availability, and result rules."
        icon="fa-question-circle"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Quiz Settings</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.quizzes.update', $quiz) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Title <span class="required">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required>
                        <span class="field-error">{{ $errors->first('title') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $quiz->class_id) == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('class_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $quiz->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Time Limit (minutes)</label>
                        <input type="number" name="time_limit_minutes" min="1" value="{{ old('time_limit_minutes', $quiz->time_limit_minutes) }}">
                        <span class="field-error">{{ $errors->first('time_limit_minutes') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Attempt Limit</label>
                        <input type="number" name="attempt_limit" min="1" value="{{ old('attempt_limit', $quiz->attempt_limit) }}">
                        <span class="field-error">{{ $errors->first('attempt_limit') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Passing Score (%)</label>
                        <input type="number" name="passing_score_percent" min="0" max="100" value="{{ old('passing_score_percent', $quiz->passing_score_percent) }}">
                        <span class="field-error">{{ $errors->first('passing_score_percent') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Available From</label>
                        <input type="datetime-local" name="availability_from" value="{{ old('availability_from', $quiz->availability_from?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('availability_from') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Available Until</label>
                        <input type="datetime-local" name="availability_until" value="{{ old('availability_until', $quiz->availability_until?->format('Y-m-d\TH:i')) }}">
                        <span class="field-error">{{ $errors->first('availability_until') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Result Visibility</label>
                        <select name="result_visibility">
                            @foreach(['always' => 'Always', 'after_grading' => 'After grading', 'never' => 'Never'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('result_visibility', $quiz->result_visibility) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('result_visibility') }}</span>
                    </div>

                    <div class="form-field full">
                        <div class="checkbox-grid">
                            <label class="checkbox-label">
                                <input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $quiz->shuffle_questions))>
                                Shuffle questions
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="shuffle_choices" value="1" @checked(old('shuffle_choices', $quiz->shuffle_choices))>
                                Shuffle choices
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="allow_navigation" value="1" @checked(old('allow_navigation', $quiz->allow_navigation))>
                                Allow navigation
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="auto_submit_on_timeout" value="1" @checked(old('auto_submit_on_timeout', $quiz->auto_submit_on_timeout))>
                                Auto-submit on timeout
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="review_allowed" value="1" @checked(old('review_allowed', $quiz->review_allowed))>
                                Allow review
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="show_correct_answers" value="1" @checked(old('show_correct_answers', $quiz->show_correct_answers))>
                                Show correct answers
                            </label>
                        </div>
                    </div>

                    <div class="form-field full">
                        <label>Instructions</label>
                        <textarea name="instructions" rows="4">{{ old('instructions', $quiz->instructions) }}</textarea>
                        <span class="field-error">{{ $errors->first('instructions') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.quizzes.show', $quiz) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

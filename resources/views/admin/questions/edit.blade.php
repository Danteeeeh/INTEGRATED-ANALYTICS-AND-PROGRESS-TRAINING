@extends('layouts.admin')

@section('title', 'Edit Question')
@php $activeNav = 'questions'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Question"
        subtitle="Update question details and settings."
        icon="fa-circle-question"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.questions.show', $question) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Question Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.questions.update', $question) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field">
                        <label>Question Bank <span class="required">*</span></label>
                        <select name="question_bank_id" required>
                            <option value="">— Select bank —</option>
                            @foreach($questionBanks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('question_bank_id', $question->question_bank_id) == $bank->id)>{{ $bank->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('question_bank_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="question_type" required>
                            @foreach(['multiple_choice' => 'Multiple Choice', 'multiple_answer' => 'Multiple Answer', 'true_false' => 'True/False', 'identification' => 'Identification', 'short_answer' => 'Short Answer', 'essay' => 'Essay'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('question_type', $question->question_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('question_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Difficulty <span class="required">*</span></label>
                        <select name="difficulty" required>
                            @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('difficulty', $question->difficulty) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('difficulty') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Default Points</label>
                        <input type="number" name="default_points" min="0" step="0.5" value="{{ old('default_points', $question->default_points) }}">
                        <span class="field-error">{{ $errors->first('default_points') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <input type="text" name="status" value="{{ old('status', $question->status) }}" maxlength="50">
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Question Text <span class="required">*</span></label>
                        <textarea name="question_text" rows="4" required placeholder="Enter the question">{{ old('question_text', $question->question_text) }}</textarea>
                        <span class="field-error">{{ $errors->first('question_text') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Explanation</label>
                        <textarea name="explanation" rows="3" placeholder="Optional explanation">{{ old('explanation', $question->explanation) }}</textarea>
                        <span class="field-error">{{ $errors->first('explanation') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Tags (comma-separated)</label>
                        <input type="text" name="tags" value="{{ is_array($question->tags) ? implode(', ', $question->tags) : $question->tags }}">
                        <span class="field-error">{{ $errors->first('tags') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.questions.show', $question) }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Update Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

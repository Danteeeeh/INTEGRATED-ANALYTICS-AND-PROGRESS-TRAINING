@extends('layouts.admin')

@section('title', 'New Question')
@php $activeNav = 'questions'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Question"
        subtitle="Create a question with type, difficulty, and bank context."
        icon="fa-circle-question"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Question Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.questions.store') }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Question Bank <span class="required">*</span></label>
                        <select name="question_bank_id" required>
                            <option value="">— Select bank —</option>
                            @foreach($questionBanks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('question_bank_id', $preselectedBankId) == $bank->id)>{{ $bank->title }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('question_bank_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Type <span class="required">*</span></label>
                        <select name="question_type" required>
                            @foreach(['multiple_choice' => 'Multiple Choice', 'multiple_answer' => 'Multiple Answer', 'true_false' => 'True/False', 'identification' => 'Identification', 'short_answer' => 'Short Answer', 'essay' => 'Essay'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('question_type', 'multiple_choice') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('question_type') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Difficulty <span class="required">*</span></label>
                        <select name="difficulty" required>
                            @foreach(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('difficulty', 'medium') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('difficulty') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Default Points</label>
                        <input type="number" name="default_points" min="0" step="0.5" value="{{ old('default_points', 1) }}">
                        <span class="field-error">{{ $errors->first('default_points') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <input type="text" name="status" value="{{ old('status', 'active') }}" maxlength="50">
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Question Text <span class="required">*</span></label>
                        <textarea name="question_text" rows="4" required placeholder="Enter the question">{{ old('question_text') }}</textarea>
                        <span class="field-error">{{ $errors->first('question_text') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Explanation (shown after answering)</label>
                        <textarea name="explanation" rows="3" placeholder="Optional explanation">{{ old('explanation') }}</textarea>
                        <span class="field-error">{{ $errors->first('explanation') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Tags (comma-separated)</label>
                        <input type="text" name="tags" value="{{ is_array(old('tags')) ? implode(', ', old('tags')) : old('tags') }}" placeholder="e.g. biology, midterm">
                        <span class="field-error">{{ $errors->first('tags') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

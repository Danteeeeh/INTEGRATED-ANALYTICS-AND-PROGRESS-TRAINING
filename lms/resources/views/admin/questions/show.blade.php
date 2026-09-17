@extends('layouts.admin')

@section('title', 'Question')
@php $activeNav = 'questions'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Question"
        subtitle="Question details and answer choices."
        icon="fa-circle-question"
    >
        <x-slot name="meta">
            <span class="user-status">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</span>
            <x-user-status-badge status="{{ $question->difficulty }}" />
            <span class="user-status">{{ $question->bank?->title }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-circle-question"></i> Question</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field full">
                    <label>Question Text</label>
                    <div style="white-space:pre-line">{{ $question->question_text }}</div>
                </div>
                <div class="form-field">
                    <label>Type</label>
                    <div>{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</div>
                </div>
                <div class="form-field">
                    <label>Difficulty</label>
                    <div><x-user-status-badge status="{{ $question->difficulty }}" /></div>
                </div>
                <div class="form-field">
                    <label>Points</label>
                    <div>{{ $question->default_points ?? 1 }}</div>
                </div>
                <div class="form-field">
                    <label>Bank</label>
                    <div>{{ $question->bank?->title ?? '—' }}</div>
                </div>
                @if($question->explanation)
                    <div class="form-field full">
                        <label>Explanation</label>
                        <div>{{ $question->explanation }}</div>
                    </div>
                @endif
                @if($question->tags)
                    <div class="form-field full">
                        <label>Tags</label>
                        <div>
                            @foreach((array) $question->tags as $tag)
                                <span class="user-status">{{ $tag }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list"></i> Choices</h3>
            <span class="user-status">{{ $question->choices->count() }} choices</span>
        </div>
        <div class="user-panel-body">
            @if($question->choices->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Choice</th>
                                <th>Correct</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($question->choices as $index => $choice)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $choice->choice_text ?? $choice->text ?? 'Choice' }}</td>
                                    <td>
                                        @if($choice->is_correct)
                                            <span class="user-status active"><i class="fa-solid fa-check"></i> Correct</span>
                                        @else
                                            <span class="user-status inactive">Incorrect</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-list"
                    title="No choices"
                    description="Choices are added through the question editor."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" onsubmit="return confirm('Delete this question?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Questions')
@php $activeNav = 'questions'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Questions"
        subtitle="Build and manage questions across question banks."
        icon="fa-circle-question"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.questions.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Question</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.questions.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search questions..." aria-label="Search questions">
            <select class="form-control" name="question_bank_id" aria-label="Filter question bank">
                <option value="">All Banks</option>
                @foreach($questionBanks as $bank)
                    <option value="{{ $bank->id }}" @selected(request('question_bank_id') == $bank->id)>{{ $bank->title }}</option>
                @endforeach
            </select>
            <select class="form-control" name="question_type" aria-label="Filter type">
                <option value="">All Types</option>
                @foreach(['multiple_choice' => 'Multiple Choice', 'multiple_answer' => 'Multiple Answer', 'true_false' => 'True/False', 'identification' => 'Identification', 'short_answer' => 'Short Answer', 'essay' => 'Essay'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.questions.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($questions->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Question</th>
                                <th>Bank</th>
                                <th>Type</th>
                                <th>Difficulty</th>
                                <th>Choices</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions as $question)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ Str::limit($question->question_text, 70) }}</div>
                                        <div class="user-email">{{ Str::limit($question->explanation ?? '', 40) }}</div>
                                    </td>
                                    <td>{{ $question->bank?->title ?? '—' }}</td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $question->question_type)) }}</span></td>
                                    <td><x-user-status-badge status="{{ $question->difficulty }}" /></td>
                                    <td>{{ $question->choices->count() }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.questions.show', $question) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.questions.edit', $question) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('Delete this question?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-circle-question"
                    title="No questions found"
                    description="Create a question to start building your bank."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.questions.create') }}"><i class="fa-solid fa-plus"></i> New Question</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($questions->hasPages())
                <div class="pagination">{{ $questions->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

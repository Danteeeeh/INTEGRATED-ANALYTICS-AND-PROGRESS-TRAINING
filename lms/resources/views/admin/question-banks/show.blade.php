@extends('layouts.admin')

@section('title', $questionBank->title)
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $questionBank->title }}"
        subtitle="Question bank contents and question counts."
        icon="fa-database"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $questionBank->status }}" />
            @if($questionBank->is_shared)<span class="user-status active">Shared</span>@endif
            <span class="user-status">{{ $questionBank->questions->count() }} questions</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.question_banks.edit', $questionBank) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.question_banks.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Bank Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Code</label>
                    <div>{{ $questionBank->code ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Category</label>
                    <div>{{ $questionBank->category ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $questionBank->course?->title ?? 'General' }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $questionBank->class?->code ?? 'General' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $questionBank->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $questionBank->creator?->name ?? '—' }}</div>
                </div>
                @if($questionBank->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $questionBank->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-ol"></i> Questions</h3>
            <span class="user-status">{{ $questionBank->questions->count() }} questions</span>
        </div>
        <div class="user-panel-body">
            @if($questionBank->questions->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th>Choices</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questionBank->questions as $index => $question)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ Str::limit($question->question_text ?? $question->text ?? 'Question', 80) }}</td>
                                    <td><span class="user-status">{{ ucfirst(str_replace('_', ' ', $question->question_type ?? 'choice')) }}</span></td>
                                    <td>{{ $question->choices->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-list-ol"
                    title="No questions in this bank"
                    description="Add questions to this bank from the Questions section."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.question_banks.destroy', $questionBank) }}" method="POST" onsubmit="return confirm('Delete this question bank?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

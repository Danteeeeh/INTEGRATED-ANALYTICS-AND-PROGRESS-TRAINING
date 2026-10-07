@extends('layouts.instructor')

@section('title', $questionBank->title)
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $questionBank->title }}"
        subtitle="{{ \Illuminate\Support\Str::limit($questionBank->description ?? 'Question bank contents.', 120) }}"
        icon="fa-database"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $questionBank->status }}" />
            @if($questionBank->is_shared)<span class="user-status active">Shared</span>@endif
            <span class="user-status">{{ $questionBank->questions->count() }} questions</span>
        </x-slot>
        <x-slot name="actions">
            @if($questionBank->created_by === auth()->id())
                <a href="{{ route('instructor.question_banks.edit', $questionBank) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit &amp; Add Questions</a>
            @endif
            <a href="{{ route('instructor.question_banks.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="user-stat-grid">
        <x-user-stat-card label="Questions" value="{{ $questionBank->questions->count() }}" icon="fa-list-check" />
        <x-user-stat-card label="Course" value="{{ $questionBank->course?->code ?? 'General' }}" icon="fa-book" />
        <x-user-stat-card label="Category" value="{{ $questionBank->category ?? '—' }}" icon="fa-tag" />
        <x-user-stat-card label="Created By" value="{{ $questionBank->creator?->name ?? '—' }}" icon="fa-user" />
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-list-check"></i> Questions</h3>
            <span class="user-status">{{ $questionBank->questions->count() }} items</span>
        </div>
        <div class="user-panel-body">
            @forelse($questionBank->questions as $question)
                <div class="user-card" style="padding:16px;border:1px solid var(--bcp-border,#e2e8f0);border-radius:10px;margin-bottom:12px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                        <div style="min-width:0;">
                            <div style="font-weight:700;color:#1e293b;">
                                {{ \Illuminate\Support\Str::limit($question->question_text, 180) }}
                            </div>
                            <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                                <span class="user-status">{{ str_replace('_', ' ', $question->question_type) }}</span>
                                <span class="user-status {{ $question->difficulty === 'hard' ? 'danger' : ($question->difficulty === 'medium' ? 'warning' : 'active') }}">
                                    {{ ucfirst($question->difficulty) }}
                                </span>
                                <span class="user-status">{{ (float) $question->default_points }} pts</span>
                                <x-user-status-badge status="{{ $question->status }}" />
                            </div>

                            @if($question->choices->isNotEmpty())
                                <ul style="margin:10px 0 0;padding-left:18px;color:#475569;">
                                    @foreach($question->choices->sortBy('position') as $choice)
                                        <li style="{{ $choice->is_correct ? 'font-weight:700;color:#16a34a' : '' }}">
                                            {{ $choice->choice_text }}
                                            @if($choice->is_correct)<i class="fa-solid fa-check" title="Correct"></i>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if($question->explanation)
                                <div style="margin-top:8px;font-size:.85rem;color:#64748b;">
                                    <strong>Explanation:</strong> {{ $question->explanation }}
                                </div>
                            @endif
                        </div>

                        @if($questionBank->created_by === auth()->id())
                            <div class="user-actions" style="flex-shrink:0;">
                                <a href="{{ route('instructor.question_banks.edit', $questionBank) }}#question-{{ $question->id }}"
                                   class="btn btn-icon" title="Edit question"><i class="fa-solid fa-pen"></i></a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa-solid fa-list-check"></i>
                    <p>This bank has no questions yet.</p>
                    @if($questionBank->created_by === auth()->id())
                        <a href="{{ route('instructor.question_banks.edit', $questionBank) }}" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Add the first question
                        </a>
                    @endif
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
@php
    $routePrefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';
    $layout = auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.instructor';
@endphp
@extends($layout)

@section('title', 'Test Bank — Preview')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="Preview this question exactly as it reads before you place it in an assessment."
        icon="fa-eye"
    >
        <x-slot name="actions">
            <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Questions</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'questions'])

    <div class="user-panel">
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px">
            <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#e0f2fe;color:#0369a1">{{ $question->typeLabel() }}</span>
            <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#f1f5f9;color:#475569">{{ $question->difficultyLabel() }}</span>
            <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#f1f5f9;color:#475569">
                {{ rtrim(rtrim(number_format((float) $question->default_points, 2), '0'), '.') }} point(s)
            </span>
            <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#f1f5f9;color:#475569">{{ $question->statusLabel() }}</span>
            @if($question->category)
                <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#ede9fe;color:#6d28d9"><i class="fa-solid fa-tag"></i> {{ $question->category->name }}</span>
            @endif
            @if($question->is_case_sensitive && in_array($question->question_type, ['identification', 'short_answer'], true))
                <span style="font-size:.78rem;font-weight:700;padding:4px 11px;border-radius:99px;background:#fef3c7;color:#b45309">Case sensitive</span>
            @endif
        </div>

        <div style="font-size:1.15rem;line-height:1.75;color:#0f172a;background:#f8fafc;border-left:4px solid #2563eb;border-radius:10px;padding:18px 20px;margin-bottom:22px">
            {!! $question->question_text !!}
        </div>

        @if($question->choices->isEmpty())
            <p style="color:#64748b;font-size:.9rem">
                @if(in_array($question->question_type, ['essay', 'short_answer'], true))
                    Open response — graded by the instructor.
                @else
                    No answer key configured yet.
                @endif
            </p>
        @else
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach($question->choices as $index => $choice)
                    @php($key = chr(65 + $index))
                    @php($isCorrect = (bool) $choice->is_correct)
                    <div style="display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border-radius:10px;border:1px solid {{ ($isCorrect && $isCorrectVisible) ? '#86efac' : '#e2e8f0' }};background:{{ ($isCorrect && $isCorrectVisible) ? '#f0fdf4' : '#fff' }}">
                        <span style="font-weight:700;color:#475569;min-width:18px">{{ $key }}.</span>
                        <span style="flex:1;color:#1e293b;line-height:1.6">{!! $choice->choice_text !!}</span>
                        @if($isCorrect && $isCorrectVisible)
                            <span style="font-size:.72rem;font-weight:700;color:#15803d;white-space:nowrap"><i class="fa-solid fa-circle-check"></i> Correct</span>
                        @endif
                    </div>
                @endforeach
            </div>

            @if(! $isCorrectVisible)
                <p style="font-size:.78rem;color:#94a3b8;margin-top:12px">
                    <i class="fa-solid fa-lock"></i> The answer key is hidden from this account.
                </p>
            @endif
        @endif

        @if($question->explanation && $isCorrectVisible)
            <div style="margin-top:20px;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px">
                <strong style="font-size:.82rem;color:#92400e"><i class="fa-solid fa-lightbulb"></i> Explanation</strong>
                <p style="margin:6px 0 0;color:#78350f;line-height:1.6">{!! $question->explanation !!}</p>
            </div>
        @endif

        <div style="margin-top:22px;padding-top:16px;border-top:1px solid #f1f5f9;display:flex;flex-wrap:wrap;gap:24px;font-size:.82rem;color:#64748b">
            <span><strong>Bank:</strong> {{ $question->bank?->title ?? '—' }}</span>
            <span><strong>Course:</strong> {{ $question->bank?->course?->code ?? '—' }}</span>
            <span><strong>Created by:</strong> {{ $question->creator?->name ?? '—' }}</span>
            <span><strong>Used in:</strong> {{ $question->quizzes()->count() }} quiz / {{ $question->exams()->count() }} exam</span>
        </div>

        <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
            @if($question->bank && ($question->bank->created_by === auth()->id() || auth()->user()->isAdmin()))
                <a href="{{ route($routePrefix.'question_banks.edit', $question->bank) }}#question-{{ $question->id }}" class="btn btn-primary">
                    <i class="fa-solid fa-pen"></i> Edit in Bank
                </a>
            @endif
            <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
</div>
@endsection

@php
    $routePrefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';
    $layout = auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.instructor';
@endphp
@extends($layout)

@section('title', 'Test Bank — Question Statistics')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="How often each question is used, and how well students do on it."
        icon="fa-chart-column"
    >
        <x-slot name="actions">
            <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary"><i class="fa-solid fa-list-check"></i> All Questions</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'statistics'])

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route($routePrefix.'question_banks.statistics.index') }}">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search question text..." aria-label="Search questions">

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="category_id" aria-label="Filter category">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>

            <select class="form-control" name="question_type" aria-label="Filter type">
                <option value="">All Types</option>
                @foreach([
                    'multiple_choice' => 'Multiple Choice',
                    'multiple_answer' => 'Multiple Answer',
                    'true_false' => 'True / False',
                    'identification' => 'Identification',
                    'short_answer' => 'Short Answer',
                    'essay' => 'Essay',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(request('question_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="difficulty" aria-label="Filter difficulty">
                <option value="">All Difficulties</option>
                @foreach(['easy' => 'Easy', 'medium' => 'Moderate', 'hard' => 'Difficult'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('difficulty') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="sort" aria-label="Sort by">
                <option value="usage" @selected(request('sort', 'usage') === 'usage')>Most used</option>
                <option value="answered" @selected(request('sort') === 'answered')>Most answered</option>
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['q', 'course_id', 'category_id', 'question_type', 'difficulty', 'sort']))
                <a href="{{ route($routePrefix.'question_banks.statistics.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        {{-- §11 — these figures suggest; they never change the question itself. --}}
        <p style="font-size:.8rem;color:#94a3b8;margin:0 0 14px">
            <i class="fa-solid fa-circle-info"></i>
            Recommendations are advisory only. Nothing here edits, retires or re-weights a question.
        </p>

        @if($questions->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-chart-column"></i>
                <p>No questions to report on yet.</p>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Category</th>
                            <th>Difficulty</th>
                            <th>Used In</th>
                            <th>Responses</th>
                            <th>Correct</th>
                            <th>Recommendation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questions as $question)
                            @php($usage = $question->usage)
                            @php($insight = $question->insight)
                            <tr>
                                <td>
                                    <a href="{{ route($routePrefix.'test_bank.questions.preview', $question) }}" class="user-cell-primary">
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 80) }}
                                    </a>
                                    <div class="user-email">{{ $question->bank?->title ?? '—' }} · {{ $question->typeLabel() }}</div>
                                </td>
                                <td>{{ $question->category?->name ?? '—' }}</td>
                                <td>{{ $question->difficultyLabel() }}</td>
                                <td>
                                    {{ $usage['quizzes'] }} quiz
                                    <div class="user-email">{{ $usage['exams'] }} exam</div>
                                </td>
                                <td>{{ $usage['answered'] }}</td>
                                <td>
                                    @if($usage['mastery'] === null)
                                        <span class="user-email">—</span>
                                    @else
                                        <strong>{{ $usage['mastery'] }}&percnt;</strong>
                                        <div class="user-email">{{ $usage['correct'] }} of {{ $usage['answered'] }}</div>
                                    @endif
                                </td>
                                <td style="max-width:340px">
                                    @php($toneStyles = [
                                        'muted'   => 'background:#f1f5f9;color:#64748b;',
                                        'info'    => 'background:#e0f2fe;color:#0369a1;',
                                        'warning' => 'background:#fef3c7;color:#b45309;',
                                        'danger'  => 'background:#fee2e2;color:#b91c1c;',
                                        'success' => 'background:#dcfce7;color:#15803d;',
                                    ])
                                    <span style="display:inline-block;font-size:.72rem;font-weight:700;padding:3px 9px;border-radius:99px;white-space:nowrap;{{ $toneStyles[$insight['tone']] ?? $toneStyles['muted'] }}">
                                        {{ $insight['label'] }}
                                    </span>
                                    <div style="font-size:.8rem;color:#475569;margin-top:6px;line-height:1.5">
                                        {{ $insight['message'] }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="user-pagination">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

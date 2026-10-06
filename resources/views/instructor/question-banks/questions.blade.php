@php
    $routePrefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';
    $layout = auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.instructor';
@endphp
@extends($layout)

@section('title', 'Test Bank — Questions')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="Every question you may use — your own banks plus anything shared with you."
        icon="fa-list-check"
    >
        <x-slot name="actions">
            <a href="{{ route($routePrefix.'question_banks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Bank</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'questions'])

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route($routePrefix.'test_bank.index') }}">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search question text..." aria-label="Search questions">

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="bank_id" aria-label="Filter bank">
                <option value="">All Banks</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}" @selected(request('bank_id') == $bank->id)>{{ $bank->title }}</option>
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

            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">Live Statuses</option>
                @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Inactive'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="scope" aria-label="Filter ownership">
                <option value="">Owned by Anyone</option>
                <option value="mine" @selected(request('scope') === 'mine')>Mine Only</option>
                <option value="shared" @selected(request('scope') === 'shared')>Shared With Me</option>
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['q', 'course_id', 'bank_id', 'category_id', 'question_type', 'difficulty', 'status', 'scope']))
                <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        @if($questions->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-list-check"></i>
                <p>No questions match these filters.</p>
                <a href="{{ route($routePrefix.'question_banks.index') }}" class="btn btn-primary"><i class="fa-solid fa-database"></i> Open a bank to add questions</a>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Bank</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Difficulty</th>
                            <th>Status</th>
                            <th>Used In</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questions as $question)
                            <tr>
                                <td>
                                    <a href="{{ route($routePrefix.'test_bank.questions.preview', $question) }}" class="user-cell-primary">
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 90) }}
                                    </a>
                                    <div class="user-email">{{ $question->creator?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    {{ $question->bank?->title ?? '—' }}
                                    @if($question->bank?->is_shared)
                                        <div class="user-email"><em>Shared</em></div>
                                    @elseif($question->bank?->created_by === auth()->id())
                                        <div class="user-email"><em>You</em></div>
                                    @endif
                                </td>
                                <td>{{ $question->category?->name ?? '—' }}</td>
                                <td>{{ $question->typeLabel() }}</td>
                                <td>{{ $question->difficultyLabel() }}</td>
                                <td><x-user-status-badge :status="$question->status" :label="$question->statusLabel()" /></td>
                                <td>
                                    {{ $question->quizzes_count }} quiz / {{ $question->exams_count }} exam
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route($routePrefix.'test_bank.questions.preview', $question) }}" class="btn btn-icon" title="Preview"><i class="fa-solid fa-eye"></i></a>
                                        @if($question->bank && ($question->bank->created_by === auth()->id() || auth()->user()->isAdmin()))
                                            <a href="{{ route($routePrefix.'question_banks.edit', $question->bank) }}#question-{{ $question->id }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        @endif
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

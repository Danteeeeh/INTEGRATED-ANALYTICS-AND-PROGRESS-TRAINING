@extends('layouts.instructor')

@section('title', 'Test Bank')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="Reusable question banks for your quizzes, assignments and exams."
        icon="fa-database"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.question_banks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Bank</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'banks'])

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('instructor.question_banks.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search question banks..." aria-label="Search question banks">

            <select class="form-control" name="scope" aria-label="Filter scope">
                <option value="">All Banks</option>
                <option value="mine" @selected(request('scope') === 'mine')>My Banks</option>
                <option value="shared" @selected(request('scope') === 'shared')>Shared With Me</option>
            </select>

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>{{ $class->code }} — {{ $class->course?->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Statuses</option>
                @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['search', 'scope', 'course_id', 'class_id', 'status']))
                <a href="{{ route('instructor.question_banks.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
        @endif

        @if($questionBanks->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-database"></i>
                <p>No question banks yet.</p>
                <a href="{{ route('instructor.question_banks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create your first bank</a>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Bank</th>
                            <th>Course / Class</th>
                            <th>Category</th>
                            <th>Questions</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($questionBanks as $bank)
                            <tr>
                                <td>
                                    <a href="{{ route('instructor.question_banks.show', $bank) }}" class="user-cell-primary">{{ $bank->title }}</a>
                                    <div class="user-email">{{ $bank->code ?? '—' }}</div>
                                </td>
                                <td>
                                    {{ $bank->course?->code ?? '—' }}
                                    <div class="user-email">{{ $bank->class?->code ?? 'General' }}</div>
                                </td>
                                <td>{{ $bank->category ?? '—' }}</td>
                                <td>{{ $bank->questions_count ?? $bank->questions->count() }}</td>
                                <td>
                                    <x-user-status-badge status="{{ $bank->status }}" />
                                    @if($bank->is_shared)
                                        <span class="user-status active">Shared</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $bank->creator?->name ?? '—' }}
                                    @if($bank->created_by === auth()->id())
                                        <div class="user-email"><em>You</em></div>
                                    @endif
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('instructor.question_banks.show', $bank) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                        @if($bank->created_by === auth()->id())
                                            <a href="{{ route('instructor.question_banks.edit', $bank) }}" class="btn btn-icon" title="Edit bank &amp; questions"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('instructor.question_banks.destroy', $bank) }}" onsubmit="return confirm('Delete this question bank?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="user-pagination">
                {{ $questionBanks->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
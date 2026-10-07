@extends('layouts.admin')

@section('title', 'Question Banks')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Question Banks"
        subtitle="Organize reusable question sets for quizzes and assessments."
        icon="fa-database"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.question_banks.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Bank</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.question_banks.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search question banks..." aria-label="Search question banks">
            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.question_banks.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($questionBanks->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bank</th>
                                <th>Category</th>
                                <th>Course / Class</th>
                                <th>Questions</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questionBanks as $bank)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $bank->title }}</div>
                                        <div class="user-email">{{ $bank->code ? $bank->code.' — ' : '' }}{{ Str::limit($bank->description ?? '', 40) }}</div>
                                    </td>
                                    <td>{{ $bank->category ?? '—' }}</td>
                                    <td>
                                        @if($bank->course)
                                            {{ $bank->course->code }}
                                        @elseif($bank->class)
                                            {{ $bank->class->code }}
                                        @else
                                            General
                                        @endif
                                    </td>
                                    <td>{{ $bank->questions->count() }}</td>
                                    <td><x-user-status-badge status="{{ $bank->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.question_banks.show', $bank) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.question_banks.edit', $bank) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.question_banks.destroy', $bank) }}" onsubmit="return confirm('Delete this question bank?')">
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
                    icon="fa-database"
                    title="No question banks found"
                    description="Create a question bank to reuse questions across quizzes."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.question_banks.create') }}"><i class="fa-solid fa-plus"></i> New Bank</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($questionBanks->hasPages())
                <div class="pagination">{{ $questionBanks->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

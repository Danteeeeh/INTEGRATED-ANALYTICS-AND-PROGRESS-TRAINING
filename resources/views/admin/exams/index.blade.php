@extends('layouts.admin')

@section('title', 'Exams')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Exams"
        subtitle="Every exam across all courses and classes."
        icon="fa-file-signature"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.exams.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Exam</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
    @endif

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.exams.index') }}">
            <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search exams..." aria-label="Search exams">

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->code }}</option>
                @endforeach
            </select>

            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->code }}</option>
                @endforeach
            </select>

            <select class="form-control" name="exam_type" aria-label="Filter exam type">
                <option value="">All Types</option>
                @foreach(\App\Models\Exam::typeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('exam_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Statuses</option>
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.exams.index') }}">Reset</a>
        </form>

        @if($exams->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-file-signature"></i>
                <p>No exams match these filters.</p>
                <a href="{{ route('admin.exams.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create the first exam</a>
            </div>
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Questions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $exam)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.exams.show', $exam) }}" class="user-cell-primary">{{ $exam->title }}</a>
                                    <div class="user-email">{{ $exam->creator?->name ?? '—' }}</div>
                                </td>
                                <td>{{ $exam->class?->code ?? '—' }}</td>
                                <td>{{ ucfirst((string) $exam->exam_type) }}</td>
                                <td>{{ $exam->duration_minutes ? $exam->duration_minutes.' min' : '—' }}</td>
                                <td><x-user-status-badge :status="$exam->status" :label="ucfirst((string) $exam->status)" /></td>
                                <td>{{ $exam->questions()->count() }}</td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a href="{{ route('admin.exams.edit', $exam) }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>

                                        @if($exam->status === 'published')
                                            <form method="POST" action="{{ route('admin.exams.close', $exam) }}" style="display:inline;">
                                                @csrf
                                                @method('POST')
                                                <button class="btn btn-icon" title="Close exam"><i class="fa-solid fa-lock"></i></button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.exams.publish', $exam) }}" style="display:inline;">
                                                @csrf
                                                @method('POST')
                                                <button class="btn btn-icon" title="Publish exam"><i class="fa-solid fa-paper-plane"></i></button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" style="display:inline;" onsubmit="return confirm('Delete this exam? Attempts may block deletion.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="user-pagination">
                {{ $exams->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
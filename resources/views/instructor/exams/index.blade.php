@extends('layouts.instructor')

@section('title', 'Exams')
@php $activeNav = 'exams'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Exams"
        subtitle="Create, schedule, and monitor exams across your classes."
        icon="fa-file-signature"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.index', ['feature' => 'exams']) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Pick course</a>
            <a href="{{ route('instructor.exams.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Exam</a>
        </x-slot>
    </x-user-page-header>

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('instructor.exams.index') }}">
            <input class="form-control" type="search" name="search" value="{{ request('search') }}" placeholder="Search exams..." aria-label="Search exams">

            <select class="form-control" name="class_id" aria-label="Filter class">
                <option value="">All Classes</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(request('class_id') == $class->id)>
                        {{ $class->code }} — {{ $class->course?->code }}
                    </option>
                @endforeach
            </select>

            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Statuses</option>
                @foreach(['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select class="form-control" name="exam_type" aria-label="Filter type">
                <option value="">All Types</option>
                @foreach(\App\Models\Exam::typeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(request('exam_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['search', 'class_id', 'status', 'exam_type']))
                <a href="{{ route('instructor.exams.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        @if($exams->isEmpty())
            <x-user-empty-state
                icon="fa-file-signature"
                title="No exams yet"
                description="Create your first exam to start assessing students."
            />
        @else
            <div class="user-table-wrap">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Schedule</th>
                            <th>Questions</th>
                            <th>Attempts</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($exams as $exam)
                            @php $course = $exam->course ?: $exam->class?->course; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="user-cell-primary">{{ $exam->title }}</a>
                                    <div class="user-email">{{ \Illuminate\Support\Str::limit($exam->description ?? '—', 50) }}</div>
                                </td>
                                <td>
                                    {{ $exam->class?->code ?? '—' }}
                                    <div class="user-email">{{ $course->code ?? '' }}</div>
                                </td>
                                <td>{{ $exam->getTypeLabel() }}</td>
                                <td>
                                    @if($exam->starts_at)
                                        <div>{{ $exam->starts_at->format('M j, Y H:i') }}</div>
                                    @else
                                        <span class="user-email">No start date</span>
                                    @endif
                                    @if($exam->ends_at)
                                        <div class="user-email">Ends {{ $exam->ends_at->format('M j, Y H:i') }}</div>
                                    @endif
                                </td>
                                <td>{{ $exam->getQuestionCount() }}</td>
                                <td>{{ $exam->attempts->count() }}</td>
                                <td><x-user-status-badge status="{{ $exam->status }}" /></td>
                                <td>
                                    <div class="user-actions">
                                        <a href="{{ route('instructor.courses.exams.show', [$course, $exam]) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a href="{{ route('instructor.courses.exams.edit', [$course, $exam]) }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        @if($exam->status === 'draft')
                                            <form method="POST" action="{{ route('instructor.courses.exams.publish', [$course, $exam]) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-icon" title="Publish"><i class="fa-solid fa-paper-plane"></i></button>
                                            </form>
                                        @elseif($exam->status === 'published')
                                            <form method="POST" action="{{ route('instructor.courses.exams.close', [$course, $exam]) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-icon" title="Close"><i class="fa-solid fa-lock"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="user-pagination">{{ $exams->links() }}</div>
        @endif
    </div>
</div>
@endsection
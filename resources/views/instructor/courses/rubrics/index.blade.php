@extends('layouts.instructor')
@section('title', 'Rubrics')
@php $activeNav = 'rubrics'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Rubrics"
        subtitle="{{ $course->title }} — grading rubrics"
        icon="fa-table-list"
    >
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.rubrics.create', $course) }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Rubric</a>
            <a href="{{ route('instructor.courses.show', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-table-list"></i> Rubrics</h3></div>
        <div class="user-panel-body">
            @if($rubrics->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rubric</th>
                                <th>Class</th>
                                <th>Criteria</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rubrics as $rubric)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $rubric->title }}</div>
                                        <div class="user-email">{{ Str::limit($rubric->description ?? '', 50) }}</div>
                                    </td>
                                    <td>{{ $rubric->class?->code ?? '—' }}</td>
                                    <td>{{ $rubric->criteria->count() }}</td>
                                    <td><x-user-status-badge status="{{ $rubric->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a href="{{ route('instructor.courses.rubrics.show', [$course, $rubric]) }}" class="btn btn-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a href="{{ route('instructor.courses.rubrics.edit', [$course, $rubric]) }}" class="btn btn-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('instructor.courses.rubrics.destroy', [$course, $rubric]) }}" onsubmit="return confirm('Delete this rubric?')">
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
                <x-user-empty-state icon="fa-table-list" title="No rubrics yet" description="Create a rubric to standardize grading." />
            @endif

            @if($rubrics->hasPages())
                <div class="pagination">{{ $rubrics->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

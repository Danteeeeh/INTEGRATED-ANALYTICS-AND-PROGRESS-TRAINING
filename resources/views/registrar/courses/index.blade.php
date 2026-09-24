@extends('layouts.registrar')
@section('title', 'Courses')
@php $activeNav = 'courses'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Courses"
        subtitle="Browse the course catalog and associated classes."
        icon="fa-book"
    >
        <x-slot name="actions">
            <a class="btn btn-primary" href="{{ route('registrar.courses.create') }}"><i class="fa-solid fa-plus"></i> Add Course</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            @if(session('status'))
                <div class="message success" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
            @endif

            @if($courses->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Code</th>
                                <th>Classes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courses as $course)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $course->title }}</div>
                                        <div class="user-email">{{ Str::limit($course->description ?? '', 50) }}</div>
                                    </td>
                                    <td><strong>{{ $course->code }}</strong></td>
                                    <td><span class="user-status">{{ $course->classes_count ?? 0 }}</span></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('registrar.courses.show', $course) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon btn-secondary" href="{{ route('registrar.courses.edit', $course) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('registrar.courses.destroy', $course) }}" onsubmit="return confirm('Delete this course?')">
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
                <x-user-empty-state icon="fa-book" title="No courses found" description="There are no courses to display." />
            @endif

            @if($courses->hasPages())
                <div class="pagination">{{ $courses->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

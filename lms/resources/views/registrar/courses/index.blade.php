@extends('layouts.registrar')
@section('title', 'Courses')
@php $activeNav = 'courses'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Courses"
        subtitle="Browse the course catalog and associated classes."
        icon="fa-book"
    />

    <div class="user-panel">
        <div class="user-panel-body">
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

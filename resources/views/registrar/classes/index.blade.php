@extends('layouts.registrar')
@section('title', 'Classes')
@php $activeNav = 'classes'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Classes"
        subtitle="Browse all classes and their enrollment counts."
        icon="fa-school"
    />

    <div class="user-panel">
        <div class="user-panel-body">
            @if($classes->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course</th>
                                <th>Instructor</th>
                                <th>Enrolled</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                <tr>
                                    <td><strong>{{ $class->code }}</strong></td>
                                    <td>{{ $class->course?->title ?? '—' }}</td>
                                    <td>{{ $class->instructor?->name ?? '—' }}</td>
                                    <td>
                                        <span class="user-status">{{ $class->enrollments->count() }}/{{ $class->max_students ?? '∞' }}</span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('registrar.classes.show', $class) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-school" title="No classes found" description="There are no classes to display." />
            @endif

            @if($classes->hasPages())
                <div class="pagination">{{ $classes->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

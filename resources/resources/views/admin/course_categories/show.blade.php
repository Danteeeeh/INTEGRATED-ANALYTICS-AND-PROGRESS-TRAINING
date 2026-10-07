@extends('layouts.admin')

@section('title', $courseCategory->name)
@php $activeNav = 'course_categories'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $courseCategory->name }}"
        subtitle="Course category details and sub-categories."
        icon="fa-layer-group"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $courseCategory->status }}" />
            <span class="user-status">{{ $courseCategory->courses->count() }} courses</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.course_categories.edit', $courseCategory) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.course_categories.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Category Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Code</label>
                    <div><strong>{{ $courseCategory->code }}</strong></div>
                </div>
                <div class="form-field">
                    <label>Parent</label>
                    <div>{{ $courseCategory->parent?->name ?? 'Top level' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $courseCategory->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $courseCategory->creator?->name ?? '—' }}</div>
                </div>
                @if($courseCategory->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $courseCategory->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-sitemap"></i> Sub-categories</h3>
            <span class="user-status">{{ $courseCategory->children->count() }} children</span>
        </div>
        <div class="user-panel-body">
            @if($courseCategory->children->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseCategory->children as $child)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.course_categories.show', $child) }}">{{ $child->name }}</a>
                                    </td>
                                    <td>{{ $child->code }}</td>
                                    <td><x-user-status-badge status="{{ $child->status }}" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-sitemap"
                    title="No sub-categories"
                    description="This category has no child categories."
                />
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book"></i> Courses in this category</h3>
            <span class="user-status">{{ $courseCategory->courses->count() }} courses</span>
        </div>
        <div class="user-panel-body">
            @if($courseCategory->courses->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseCategory->courses as $course)
                                <tr>
                                    <td><strong>{{ $course->code }}</strong></td>
                                    <td>{{ $course->title }}</td>
                                    <td><x-user-status-badge status="{{ $course->status }}" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-book"
                    title="No courses"
                    description="No courses are assigned to this category yet."
                />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('admin.course_categories.destroy', $courseCategory) }}" method="POST" onsubmit="return confirm('Delete this category?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

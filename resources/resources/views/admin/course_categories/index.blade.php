@extends('layouts.admin')

@section('title', 'Course Categories')
@php $activeNav = 'course_categories'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Course Categories"
        subtitle="Organize courses into a hierarchy of categories."
        icon="fa-layer-group"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.course_categories.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Category</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.course_categories.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search categories..." aria-label="Search categories">
            <select class="form-control" name="status" aria-label="Filter status">
                <option value="">All Status</option>
                @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.course_categories.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($courseCategories->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Code</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($courseCategories as $category)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $category->name }}</div>
                                        <div class="user-email">{{ Str::limit($category->description ?? '', 50) }}</div>
                                    </td>
                                    <td><strong>{{ $category->code }}</strong></td>
                                    <td>{{ $category->parent?->name ?? '—' }}</td>
                                    <td><x-user-status-badge status="{{ $category->status }}" /></td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.course_categories.show', $category) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.course_categories.edit', $category) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            <form method="POST" action="{{ route('admin.course_categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
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
                    icon="fa-layer-group"
                    title="No categories found"
                    description="Create a category to start organizing courses."
                >
                    <x-slot name="action">
                        <a class="btn btn-primary" href="{{ route('admin.course_categories.create') }}"><i class="fa-solid fa-plus"></i> New Category</a>
                    </x-slot>
                </x-user-empty-state>
            @endif

            @if($courseCategories->hasPages())
                <div class="pagination">{{ $courseCategories->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

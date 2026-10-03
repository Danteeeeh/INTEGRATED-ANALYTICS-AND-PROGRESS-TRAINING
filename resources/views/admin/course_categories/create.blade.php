@extends('layouts.admin')

@section('title', 'New Course Category')
@php $activeNav = 'course_categories'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Course Category"
        subtitle="Create a category or sub-category for organizing courses."
        icon="fa-layer-group"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.course_categories.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Category Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.course_categories.store') }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field full">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Information Technology">
                        <span class="field-error">{{ $errors->first('name') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Code <span class="required">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g. IT" maxlength="50">
                        <span class="field-error">{{ $errors->first('code') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Parent Category</label>
                        <select name="parent_id">
                            <option value="">— None (top level) —</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('parent_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            @foreach(['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('status') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Category description">{{ old('description') }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.course_categories.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Add Program')
@php($activeNav = 'programs')
@section('content')
<div class="user-page">
    <x-user-page-header title="Add Program" subtitle="Create a new academic program." icon="fa-book-open">
        <x-slot name="actions"><a class="btn btn-secondary" href="{{ route('admin.programs.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a></x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.programs.store') }}" class="user-form" style="max-width:640px">
                @csrf
                <div class="form-field" style="margin-bottom:16px">
                    <label for="department_id">Department</label>
                    <select id="department_id" class="form-control" name="department_id">
                        <option value="">— Select department —</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }} ({{ $department->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="name">Program name <span class="required">*</span></label>
                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name') }}" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="code">Code <span class="required">*</span></label>
                    <input id="code" class="form-control" type="text" name="code" value="{{ old('code') }}" placeholder="e.g. BSIT" required>
                    @error('code')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="description">Description</label>
                    <textarea id="description" class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save program</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

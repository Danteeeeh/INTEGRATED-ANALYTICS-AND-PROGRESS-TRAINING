@extends('layouts.admin')
@section('title', 'Edit Department')
@php($activeNav = 'departments')
@section('content')
<div class="user-page">
    <x-user-page-header title="Edit Department" subtitle="Update department details." icon="fa-building-columns">
        <x-slot name="actions"><a class="btn btn-secondary" href="{{ route('admin.departments.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a></x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.departments.update', $department) }}" class="user-form" style="max-width:640px">
                @csrf
                @method('PUT')
                <div class="form-field" style="margin-bottom:16px">
                    <label for="name">Department name <span class="required">*</span></label>
                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name', $department->name) }}" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="code">Code <span class="required">*</span></label>
                    <input id="code" class="form-control" type="text" name="code" value="{{ old('code', $department->code) }}" required>
                    @error('code')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="description">Description</label>
                    <textarea id="description" class="form-control" name="description" rows="3">{{ old('description', $department->description) }}</textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Update department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

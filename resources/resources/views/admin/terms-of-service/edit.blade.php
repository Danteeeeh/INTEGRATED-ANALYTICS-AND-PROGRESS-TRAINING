@extends('layouts.admin')

@section('title', 'Edit Terms of Service')

@section('content')
<div class="content-header">
    <h1>Edit Terms of Service</h1>
    <a href="{{ route('admin.terms_of_service.show', $termsOfService) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.terms_of_service.update', $termsOfService) }}">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="title">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $termsOfService->title) }}" required>
                @error('title')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="version">Version <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="version" name="version" value="{{ old('version', $termsOfService->version) }}" required>
                @error('version')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="effective_date">Effective Date</label>
                <input type="date" class="form-control" id="effective_date" name="effective_date" value="{{ old('effective_date', $termsOfService->effective_date ? $termsOfService->effective_date->format('Y-m-d') : '') }}">
                @error('effective_date')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="content">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content" name="content" rows="15" required>{{ old('content', $termsOfService->content) }}</textarea>
                @error('content')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $termsOfService->is_active ? 'checked' : '' }}>
                    <label class="custom-control-label" for="is_active">Active</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Terms of Service
            </button>
        </form>
    </div>
</div>
@endsection

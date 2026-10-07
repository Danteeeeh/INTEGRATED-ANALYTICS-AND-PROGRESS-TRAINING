@extends('layouts.admin')

@section('title', 'Create Terms of Service')

@section('content')
<div class="content-header">
    <h1>Create Terms of Service</h1>
    <a href="{{ route('admin.terms_of_service.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.terms_of_service.store') }}">
            @csrf
            <div class="form-group">
                <label for="title">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                @error('title')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="version">Version <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="version" name="version" value="{{ old('version', '1.0') }}" required>
                @error('version')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="effective_date">Effective Date</label>
                <input type="date" class="form-control" id="effective_date" name="effective_date" value="{{ old('effective_date') }}">
                @error('effective_date')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label for="content">Content <span class="text-danger">*</span></label>
                <textarea class="form-control" id="content" name="content" rows="15" required>{{ old('content') }}</textarea>
                @error('content')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1">
                    <label class="custom-control-label" for="is_active">Activate immediately</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Create Terms of Service
            </button>
        </form>
    </div>
</div>
@endsection

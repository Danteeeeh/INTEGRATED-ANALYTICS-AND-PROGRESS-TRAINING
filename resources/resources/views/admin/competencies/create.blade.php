@extends('layouts.admin')

@section('title', 'New Competency')
@php $activeNav = 'competencies'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="New Competency"
        subtitle="Define a competency within a framework."
        icon="fa-compass"
    >
        <x-slot name="actions">
            <a href="{{ route('admin.competencies.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-pen"></i> Competency Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="{{ route('admin.competencies.store') }}" method="POST">
                @csrf

                <div class="form-grid">
                    <div class="form-field">
                        <label>Framework <span class="required">*</span></label>
                        <select name="framework_id" required>
                            <option value="">— Select framework —</option>
                            @foreach($frameworks as $framework)
                                <option value="{{ $framework->id }}" @selected(old('framework_id', $preselectedFrameworkId) == $framework->id)>{{ $framework->name }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('framework_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Parent Competency</label>
                        <select name="parent_id">
                            <option value="">— None —</option>
                            @foreach($competencies as $competency)
                                <option value="{{ $competency->id }}" @selected(old('parent_id', $preselectedParentId) == $competency->id)>{{ $competency->code }} — {{ $competency->name }}</option>
                            @endforeach
                        </select>
                        <span class="field-error">{{ $errors->first('parent_id') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Code <span class="required">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g. COMP-01" maxlength="50">
                        <span class="field-error">{{ $errors->first('code') }}</span>
                    </div>

                    <div class="form-field">
                        <label>Position</label>
                        <input type="number" name="position" min="0" value="{{ old('position') }}">
                        <span class="field-error">{{ $errors->first('position') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Name <span class="required">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="Competency name">
                        <span class="field-error">{{ $errors->first('name') }}</span>
                    </div>

                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Competency description">{{ old('description') }}</textarea>
                        <span class="field-error">{{ $errors->first('description') }}</span>
                    </div>
                </div>

                <div class="form-actions user-actions">
                    <a href="{{ route('admin.competencies.index') }}" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Create Competency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

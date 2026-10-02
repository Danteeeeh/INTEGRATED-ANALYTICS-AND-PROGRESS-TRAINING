@extends('layouts.admin')
@section('title', 'Edit Section')
@php($activeNav = 'sections')
@section('content')
<div class="user-page">
    <x-user-page-header title="Edit Section" subtitle="Update section details." icon="fa-users-rectangle">
        <x-slot name="actions"><a class="btn btn-secondary" href="{{ route('admin.sections.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a></x-slot>
    </x-user-page-header>
    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.sections.update', $section) }}" class="user-form" style="max-width:640px">
                @csrf
                @method('PUT')
                <div class="form-field" style="margin-bottom:16px">
                    <label for="program_id">Program</label>
                    <select id="program_id" class="form-control" name="program_id">
                        <option value="">— Select program —</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('program_id', $section->program_id) == $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="academic_period_id">Academic period</label>
                    <select id="academic_period_id" class="form-control" name="academic_period_id">
                        <option value="">— Select period —</option>
                        @foreach($academicPeriods as $period)
                            <option value="{{ $period->id }}" @selected(old('academic_period_id', $section->academic_period_id) == $period->id)>{{ $period->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="name">Section name <span class="required">*</span></label>
                    <input id="name" class="form-control" type="text" name="name" value="{{ old('name', $section->name) }}" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="code">Code <span class="required">*</span></label>
                    <input id="code" class="form-control" type="text" name="code" value="{{ old('code', $section->code) }}" required>
                    @error('code')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="form-field" style="margin-bottom:16px">
                    <label for="max_students">Max students</label>
                    <input id="max_students" class="form-control" type="number" name="max_students" min="1" max="1000" value="{{ old('max_students', $section->max_students) }}">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Update section</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

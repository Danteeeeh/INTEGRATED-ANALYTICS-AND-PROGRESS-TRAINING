@extends('layouts.registrar')
@section('title', 'Edit Course')
@php $activeNav = 'courses'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Course"
        subtitle="Update course details."
        icon="fa-book"
    >
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('registrar.courses.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('registrar.courses.update', $course) }}" class="user-form" style="max-width:720px">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
                @endif

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="margin-bottom:16px">
                        <label for="code">Course code <span class="required">*</span></label>
                        <input id="code" class="form-control" type="text" name="code" value="{{ old('code', $course->code) }}" required>
                        @error('code')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div style="margin-bottom:16px">
                        <label for="title">Course title <span class="required">*</span></label>
                        <input id="title" class="form-control" type="text" name="title" value="{{ old('title', $course->title) }}" required>
                        @error('title')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="margin-bottom:16px">
                        <label for="academic_period_id">Academic period</label>
                        <select id="academic_period_id" class="form-control" name="academic_period_id">
                            <option value="">— Select period —</option>
                            @foreach($academicPeriods ?? [] as $period)
                                <option value="{{ $period->id }}" @selected(old('academic_period_id', $course->academic_period_id) == $period->id)>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="margin-bottom:16px">
                        <label for="department_id">Department</label>
                        <select id="department_id" class="form-control" name="department_id">
                            <option value="">— Select department —</option>
                            @foreach($departments ?? [] as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id', $course->department_id) == $department->id)>{{ $department->name }} ({{ $department->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="margin-bottom:16px">
                        <label for="program_id">Program</label>
                        <select id="program_id" class="form-control" name="program_id">
                            <option value="">— Select program —</option>
                            @foreach($programs ?? [] as $program)
                                <option value="{{ $program->id }}" @selected(old('program_id', $course->program_id) == $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:16px">
                    <label for="description">Description</label>
                    <textarea id="description" class="form-control" name="description" rows="3">{{ old('description', $course->description) }}</textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="margin-bottom:16px">
                        <label for="duration_weeks">Duration (weeks)</label>
                        <input id="duration_weeks" class="form-control" type="number" name="duration_weeks" min="1" value="{{ old('duration_weeks', $course->duration_weeks) }}">
                    </div>
                    <div style="margin-bottom:16px">
                        <label for="status">Status</label>
                        <select id="status" class="form-control" name="status">
                            <option value="draft" @selected(old('status', $course->status) === 'draft')>Draft</option>
                            <option value="published" @selected(old('status', $course->status) === 'published')>Published</option>
                            <option value="archived" @selected(old('status', $course->status) === 'archived')>Archived</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Update course</button>
                    <a class="btn btn-secondary" href="{{ route('registrar.courses.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')
@section('title', 'Transfer Section — ' . $enrollment->student?->name)
@php $activeNav = 'enrollments'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Transfer Student to Another Section"
        subtitle="{{ $enrollment->student?->name ?? 'Student' }} — from {{ $enrollment->class?->code ?? '—' }} ({{ $enrollment->class?->course?->title ?? '' }})"
        icon="fa-arrow-right-arrow-left"
    >
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('admin.enrollments.index') }}"><i class="fa-solid fa-arrow-left"></i> Back to Enrollments</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.enrollments.transfer', $enrollment) }}" class="user-form" style="max-width:640px">
                @csrf

                @if($errors->any())
                    <div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}</div>
                @endif

                <div class="form-field" style="margin-bottom:16px">
                    <label for="class_id">Transfer to section/class <span class="required">*</span></label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">— Select target class —</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>
                                {{ $class->code }} — {{ $class->course?->code ?? '' }} {{ $class->course?->title ?? '' }} ({{ $class->instructor?->full_name ?? 'No instructor' }})
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')<span class="field-error">{{ $message }}</span>@enderror
                </div>

                <div class="user-actions" style="margin-top:8px">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-arrow-right-arrow-left"></i> Transfer Student</button>
                    <a href="{{ route('admin.enrollments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

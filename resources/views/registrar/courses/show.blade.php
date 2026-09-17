@extends('layouts.registrar')
@section('title', 'Course Details')
@php $activeNav = 'courses'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $course->title }}"
        subtitle="{{ $course->code }} — course details"
        icon="fa-book"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $course->status }}" />
            <span class="user-status">{{ $course->classes->count() }} classes</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('registrar.courses.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Course Details</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Code</label>
                    <div><strong>{{ $course->code }}</strong></div>
                </div>
                <div class="form-field">
                    <label>Category</label>
                    <div>{{ $course->category?->name ?? $course->courseCategory?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Credits</label>
                    <div>{{ $course->credits ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $course->status }}" /></div>
                </div>
                @if($course->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $course->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-school"></i> Classes</h3>
            <span class="user-status">{{ $course->classes->count() }} classes</span>
        </div>
        <div class="user-panel-body">
            @if($course->classes->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Instructor</th>
                                <th>Enrolled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($course->classes as $class)
                                <tr>
                                    <td><strong>{{ $class->code }}</strong></td>
                                    <td>{{ $class->instructor?->name ?? '—' }}</td>
                                    <td><span class="user-status">{{ $class->enrollments->count() }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state icon="fa-school" title="No classes" description="No classes are associated with this course." />
            @endif
        </div>
    </div>
</div>
@endsection

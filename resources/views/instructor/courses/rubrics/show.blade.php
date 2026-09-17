@extends('layouts.instructor')
@section('title', $rubric->title)
@php $activeNav = 'rubrics'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $rubric->title }}"
        subtitle="{{ $course->title }} — rubric details"
        icon="fa-table-list"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $rubric->status }}" />
            @if($rubric->is_shared)<span class="user-status active">Shared</span>@endif
            <span class="user-status">{{ $rubric->criteria->count() }} criteria</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('instructor.courses.rubrics.edit', [$course, $rubric]) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('instructor.courses.rubrics.index', $course) }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-info-circle"></i> Rubric</h3></div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $rubric->class?->code ?? 'General' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $rubric->status }}" /></div>
                </div>
                @if($rubric->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $rubric->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-scale-balanced"></i> Criteria &amp; Levels</h3></div>
        <div class="user-panel-body">
            @if($rubric->criteria->count() > 0)
                @foreach($rubric->criteria as $criterion)
                    <div class="user-panel" style="margin-bottom:12px">
                        <div class="user-panel-head">
                            <h4>{{ $criterion->criterion }} <span class="user-status">{{ $criterion->max_points }} pts</span></h4>
                        </div>
                        <div class="user-panel-body">
                            @if($criterion->levels->count() > 0)
                                <div class="user-table-wrap">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Level</th>
                                                <th>Points</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($criterion->levels as $level)
                                                <tr>
                                                    <td><strong>{{ $level->name ?? 'Level' }}</strong></td>
                                                    <td>{{ $level->points }}</td>
                                                    <td>{{ $level->description ?? '—' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <x-user-empty-state icon="fa-scale-balanced" title="No criteria yet" description="Edit this rubric to add scoring criteria." />
            @endif
        </div>
    </div>

    <div class="user-actions">
        <form action="{{ route('instructor.courses.rubrics.destroy', [$course, $rubric]) }}" method="POST" onsubmit="return confirm('Delete this rubric?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

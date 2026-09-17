@extends('layouts.admin')

@section('title', $rubric->title)
@php $activeNav = 'rubrics'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $rubric->title }}"
        subtitle="Rubric criteria, levels, and scoring."
        icon="fa-table-list"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $rubric->status }}" />
            @if($rubric->is_shared)<span class="user-status active">Shared</span>@endif
            <span class="user-status">{{ $rubric->criteria->count() }} criteria</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.rubrics.edit', $rubric) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.rubrics.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Rubric Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $rubric->course?->title ?? 'General' }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $rubric->class?->code ?? 'General' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $rubric->status }}" /></div>
                </div>
                <div class="form-field">
                    <label>Created By</label>
                    <div>{{ $rubric->creator?->name ?? '—' }}</div>
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
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-scale-balanced"></i> Criteria &amp; Levels</h3>
        </div>
        <div class="user-panel-body">
            @if($rubric->criteria->count() > 0)
                @foreach($rubric->criteria as $criterion)
                    <div class="user-panel" style="margin-bottom:12px">
                        <div class="user-panel-head">
                            <h4>{{ $criterion->criterion }} <span class="user-status">{{ $criterion->max_points }} pts</span></h4>
                        </div>
                        <div class="user-panel-body">
                            @if($criterion->description)
                                <p class="user-email">{{ $criterion->description }}</p>
                            @endif
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
                <x-user-empty-state
                    icon="fa-scale-balanced"
                    title="No criteria yet"
                    description="Edit this rubric to add scoring criteria and levels."
                />
            @endif
        </div>
    </div>

    @if($rubric->assignments->count() > 0)
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-tasks"></i> Linked Assignments</h3>
            </div>
            <div class="user-panel-body">
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Assignment</th>
                                <th>Class</th>
                                <th>Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rubric->assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->title }}</td>
                                    <td>{{ $assignment->class?->code ?? '—' }}</td>
                                    <td>{{ $assignment->class?->course?->title ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="user-actions">
        <form action="{{ route('admin.rubrics.destroy', $rubric) }}" method="POST" onsubmit="return confirm('Delete this rubric?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', $competency->name)
@php $activeNav = 'competencies'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $competency->code }} — {{ $competency->name }}"
        subtitle="Competency details, hierarchy, and course mappings."
        icon="fa-compass"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $competency->framework?->name ?? 'No framework' }}</span>
            <span class="user-status">{{ $competency->courseMappings->count() }} mapped courses</span>
        </x-slot>
        <x-slot name="actions">
            @can('update', $competency)
                <a href="{{ route('admin.competencies.edit', $competency) }}" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit</a>
            @endcan
            <a href="{{ route('admin.competencies.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Competency Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Framework</label>
                    <div>{{ $competency->framework?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Parent</label>
                    <div>{{ $competency->parent?->name ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Code</label>
                    <div><strong>{{ $competency->code }}</strong></div>
                </div>
                <div class="form-field">
                    <label>Position</label>
                    <div>{{ $competency->position }}</div>
                </div>
                @if($competency->description)
                    <div class="form-field full">
                        <label>Description</label>
                        <div>{{ $competency->description }}</div>
                    </div>
                @endif
                @if($competency->learning_outcomes)
                    <div class="form-field full">
                        <label>Learning Outcomes</label>
                        <ul>
                            @foreach((array) $competency->learning_outcomes as $outcome)
                                <li>{{ is_array($outcome) ? json_encode($outcome) : $outcome }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-sitemap"></i> Child Competencies</h3>
            <span class="user-status">{{ $competency->children->count() }} children</span>
        </div>
        <div class="user-panel-body">
            @if($competency->children->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Framework</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competency->children as $child)
                                <tr>
                                    <td>{{ $child->code }}</td>
                                    <td><a href="{{ route('admin.competencies.show', $child) }}">{{ $child->name }}</a></td>
                                    <td>{{ $child->framework?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-sitemap"
                    title="No child competencies"
                    description="This competency has no sub-competencies."
                />
            @endif
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-book"></i> Course Mappings</h3>
            <span class="user-status">{{ $competency->courseMappings->count() }} mappings</span>
        </div>
        <div class="user-panel-body">
            @if($competency->courseMappings->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competency->courseMappings as $mapping)
                                <tr>
                                    <td>{{ $mapping->course?->code ?? '—' }} — {{ $mapping->course?->title ?? '' }}</td>
                                    <td>{{ $mapping->weight ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-book"
                    title="No course mappings"
                    description="This competency is not mapped to any course yet."
                />
            @endif
        </div>
    </div>

    @can('delete', $competency)
        <div class="user-actions">
            <form action="{{ route('admin.competencies.destroy', $competency) }}" method="POST" onsubmit="return confirm('Delete this competency?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
        </div>
    @endcan
</div>
@endsection

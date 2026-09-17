@extends('layouts.admin')

@section('title', 'Competencies')
@php $activeNav = 'competencies'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Competencies"
        subtitle="Define competency frameworks and learning outcomes."
        icon="fa-compass"
    >
        <x-slot name="actions">
            @can('competencies.create')
                <a href="{{ route('admin.competencies.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Competency</a>
            @endcan
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.competencies.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search competencies..." aria-label="Search competencies">
            <select class="form-control" name="framework_id" aria-label="Filter framework">
                <option value="">All Frameworks</option>
                @foreach($frameworks as $framework)
                    <option value="{{ $framework->id }}" @selected(request('framework_id') == $framework->id)>{{ $framework->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.competencies.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($competencies->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Competency</th>
                                <th>Framework</th>
                                <th>Parent</th>
                                <th>Mapped Courses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competencies as $competency)
                                <tr>
                                    <td>
                                        <div class="user-name">{{ $competency->code }} — {{ $competency->name }}</div>
                                        <div class="user-email">{{ Str::limit($competency->description ?? '', 50) }}</div>
                                    </td>
                                    <td>{{ $competency->framework?->name ?? '—' }}</td>
                                    <td>{{ $competency->parent?->name ?? '—' }}</td>
                                    <td>{{ $competency->courseMappings->count() }}</td>
                                    <td>
                                        <div class="user-actions">
                                            <a class="btn btn-icon" href="{{ route('admin.competencies.show', $competency) }}" title="View"><i class="fa-solid fa-eye"></i></a>
                                            <a class="btn btn-icon" href="{{ route('admin.competencies.edit', $competency) }}" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                            @can('delete', $competency)
                                                <form method="POST" action="{{ route('admin.competencies.destroy', $competency) }}" onsubmit="return confirm('Delete this competency?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-user-empty-state
                    icon="fa-compass"
                    title="No competencies found"
                    description="Create a competency to define measurable learning outcomes."
                />
            @endif

            @if($competencies->hasPages())
                <div class="pagination">{{ $competencies->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

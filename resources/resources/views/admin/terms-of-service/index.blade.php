@extends('layouts.admin')

@section('title', 'Terms of Service')

@section('content')
<div class="content-header">
    <h1>Terms of Service</h1>
    <a href="{{ route('admin.terms_of_service.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($termsList->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Effective Date</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($termsList as $terms)
                    <tr>
                        <td>{{ $terms->title }}</td>
                        <td>{{ $terms->version }}</td>
                        <td>
                            @if($terms->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>{{ $terms->effective_date ? $terms->effective_date->format('M d, Y') : '—' }}</td>
                        <td>{{ $terms->creator->name ?? '—' }}</td>
                        <td>{{ $terms->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.terms_of_service.show', $terms) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.terms_of_service.edit', $terms) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($terms->is_active)
                                <form action="{{ route('admin.terms_of_service.deactivate', $terms) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-sm btn-secondary" onclick="return confirm('Deactivate this Terms of Service?')">
                                        <i class="fas fa-toggle-off"></i>
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('admin.terms_of_service.activate', $terms) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Activate this Terms of Service? This will deactivate all other active terms.')">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $termsList->links() }}
        @else
            <div class="text-center py-5">
                <p class="text-muted">No Terms of Service found.</p>
                <a href="{{ route('admin.terms_of_service.create') }}" class="btn btn-primary">
                    Create First Terms of Service
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

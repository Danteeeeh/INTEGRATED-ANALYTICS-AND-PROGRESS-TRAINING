@extends('layouts.admin')

@section('title', 'Terms of Service — ' . $termsOfService->title)

@section('content')
<div class="content-header">
    <h1>{{ $termsOfService->title }}</h1>
    <div>
        <a href="{{ route('admin.terms_of_service.edit', $termsOfService) }}" class="btn btn-warning">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="{{ route('admin.terms_of_service.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Version:</strong> {{ $termsOfService->version }}</p>
                <p><strong>Status:</strong>
                    @if($termsOfService->is_active)
                        <span class="badge badge-success">Active</span>
                    @else
                        <span class="badge badge-secondary">Inactive</span>
                    @endif
                </p>
                <p><strong>Effective Date:</strong> {{ $termsOfService->effective_date ? $termsOfService->effective_date->format('M d, Y H:i') : 'Not set' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Created By:</strong> {{ $termsOfService->creator->name ?? '—' }}</p>
                <p><strong>Created At:</strong> {{ $termsOfService->created_at->format('M d, Y H:i') }}</p>
                <p><strong>Updated At:</strong> {{ $termsOfService->updated_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h3>Content</h3>
    </div>
    <div class="card-body">
        <div style="white-space: pre-wrap; max-height: 500px; overflow-y: auto;">{{ $termsOfService->content }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Acceptances ({{ $termsOfService->acceptances->count() }})</h3>
    </div>
    <div class="card-body">
        @if($termsOfService->acceptances->count() > 0)
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Accepted At</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($termsOfService->acceptances as $acceptance)
                    <tr>
                        <td>{{ $acceptance->user->name ?? '—' }}</td>
                        <td>{{ $acceptance->accepted_at->format('M d, Y H:i') }}</td>
                        <td>{{ $acceptance->ip_address ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No acceptances yet.</p>
        @endif
    </div>
</div>
@endsection

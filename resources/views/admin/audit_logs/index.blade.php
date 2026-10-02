@extends('layouts.admin')
@section('title','Audit Logs')
@php($activeNav='audit_logs')
@section('content')
<div class="user-page">
    <x-user-page-header
        title="Audit Logs"
        subtitle="Track important system activity."
        icon="fa-shield-halved"
    />

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route('admin.audit_logs.index') }}">
            <input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search activity..." aria-label="Search audit logs">
            <input class="form-control" name="resource_type" value="{{ request('resource_type') }}" placeholder="Resource type" aria-label="Filter resource type">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.audit_logs.index') }}"><i class="fa-solid fa-xmark"></i> Clear</a>
        </form>
        @if($auditLogs->count() > 0)
            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Resource</th>
                            <th>IP</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->diffForHumans() }}</td>
                                <td>{{ $log->user?->name ?? $log->user?->email ?? 'System' }}</td>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->resource_type ?? '—' }}</td>
                                <td>{{ $log->ip_address ?? '—' }}</td>
                                <td><a class="btn btn-sm btn-secondary" href="{{ route('admin.audit_logs.show', $log) }}" title="View log"><i class="fa-solid fa-eye"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($auditLogs->hasPages())
                <div class="pagination">{{ $auditLogs->appends(request()->query())->links() }}</div>
            @endif
        @else
            <x-user-empty-state icon="fa-shield-halved" title="No audit logs found" description="System activity will appear here." />
        @endif
    </div>
</div>
@endsection
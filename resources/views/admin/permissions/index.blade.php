@extends('layouts.admin')
@section('title', 'Manage Permissions')
@php($activeNav = 'permissions')
@section('content')
<div class="user-page">
    <x-user-page-header
        title="Manage Permissions"
        subtitle="Assign capability sets to each role. Admin always has full access."
        icon="fa-user-shield"
    />

    <div class="user-panel">
        <div class="user-panel-body">
            @if(session('status'))
                <div class="message success" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
            @endif

            <div class="user-table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Description</th>
                            <th>Permissions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">{{ strtoupper(substr($role->name, 0, 1)) }}</div>
                                        <div class="user-name">{{ $role->name }}</div>
                                    </div>
                                </td>
                                <td style="color:var(--bcp-muted);font-size:.78rem">{{ $role->description }}</td>
                                <td>
                                    <span class="badge badge-active">{{ $role->permissions->count() }} permissions</span>
                                </td>
                                <td>
                                    @if($role->slug === \App\Models\Role::ADMIN)
                                        <span class="badge badge-inactive" title="Admin role is fixed">Full access</span>
                                    @else
                                        <a href="{{ route('admin.permissions.edit', $role) }}" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-pen"></i> Edit permissions
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Users')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => 'users'])
@endsection

@php $activeNav = 'users'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Users"
        subtitle="Manage all user accounts, roles, and access."
        icon="fa-users"
    >
        <x-slot name="actions">
            @can('users.create')
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Add User
                </a>
            @endcan
            @can('users.import')
                <button type="button" onclick="document.getElementById('importModal').classList.add('active')" class="btn btn-secondary">
                    <i class="fa-solid fa-upload"></i> Import
                </button>
            @endcan
            @can('users.export')
                <a href="{{ route('admin.users.export', request()->query()) }}" class="btn btn-secondary">
                    <i class="fa-solid fa-download"></i> Export
                </a>
            @endcan
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <form method="GET" action="{{ route('admin.users.index') }}" class="user-toolbar">
            <input type="text" name="search" placeholder="Search users..." value="{{ request('search') }}" class="form-control" aria-label="Search users">
            <select name="role_slug" class="form-control" aria-label="Filter role">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->slug }}" {{ request('role_slug') == $role->slug ? 'selected' : '' }}>
                        {{ ucfirst($role->slug) }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="form-control" aria-label="Filter status">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            </select>
            <select name="sort" class="form-control" aria-label="Sort users">
                <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
                <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
                <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
                <option value="last_login" @selected(request('sort') === 'last_login')>Recent login</option>
            </select>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Apply</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary"><i class="fa-solid fa-times"></i> Clear</a>
        </form>

        <div class="user-panel-body">
            @if($users->count() > 0)
                <div class="user-table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Identifier</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                            <div>
                                                <div class="user-name">{{ $user->name }}</div>
                                                <div class="user-email">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->identifier ?? '-' }}</td>
                                    <td><span class="user-status">{{ ucfirst($user->role->slug ?? 'user') }}</span></td>
                                    <td><x-user-status-badge status="{{ $user->status }}" /></td>
                                    <td>{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</td>
                                    <td>
                                        <div class="user-actions">
                                            @can('view', $user)
                                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-icon" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                            @endcan
                                            @can('update', $user)
                                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-icon" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            @endcan
                                            @if($user->isActive())
                                                @can('delete', $user)
                                                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="inline-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-icon btn-danger" title="Deactivate" onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('update', $user)
                                                    <form method="POST" action="{{ route('admin.users.reactivate', $user) }}" class="inline-form">
                                                        @csrf
                                                        <button type="submit" class="btn btn-icon btn-success" title="Reactivate" onclick="return confirm('Are you sure you want to reactivate this user?')">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @endif
                                            @can('resetPassword', $user)
                                                <button type="button" onclick="document.getElementById('resetPasswordModal-{{ $user->id }}').classList.add('active')" class="btn btn-icon" title="Reset Password">
                                                    <i class="fa-solid fa-key"></i>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="pagination">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <x-user-empty-state
                    icon="fa-users"
                    title="No users found"
                    description="Get started by adding your first user."
                >
                    @can('users.create')
                        <x-slot name="action">
                            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Add User
                            </a>
                        </x-slot>
                    @endcan
                </x-user-empty-state>
            @endif
        </div>
    </div>
</div>

<!-- Import Modal -->
@can('users.import')
<div class="modal" id="importModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Import Users</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('importModal').classList.remove('active')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" class="modal-body">
            @csrf
            <div class="form-group">
                <label>CSV File</label>
                <input type="file" name="file" accept=".csv" required class="form-control">
                <small class="form-text">Upload a CSV file with columns: first_name, last_name, email, identifier, role_slug</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('importModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Reset Password Modals -->
@foreach($users as $user)
    @can('resetPassword', $user)
        <div class="modal" id="resetPasswordModal-{{ $user->id }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Reset Password</h3>
                    <button type="button" class="modal-close" onclick="document.getElementById('resetPasswordModal-{{ $user->id }}').classList.remove('active')">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="modal-body">
                    @csrf
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="password" required minlength="8" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" required minlength="8" class="form-control">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('resetPasswordModal-{{ $user->id }}').classList.remove('active')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach

@push('styles')
<style>
.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex: 0 0 auto;
}

.user-name {
    font-weight: 600;
    color: var(--bcp-ink);
}

.user-email {
    font-size: 0.78rem;
    color: var(--bcp-muted);
}
</style>
@endpush
@endsection

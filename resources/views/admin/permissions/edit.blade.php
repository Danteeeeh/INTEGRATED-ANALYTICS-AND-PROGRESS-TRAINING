@extends('layouts.admin')
@section('title', 'Edit Permissions — ' . $role->name)
@php($activeNav = 'permissions')
@section('content')
<div class="user-page">
    <x-user-page-header
        title="Edit Permissions — {{ $role->name }}"
        subtitle="Check the capabilities this role should have. Changes apply immediately."
        icon="fa-user-shield"
    >
        <x-slot name="actions">
            <a class="btn btn-secondary" href="{{ route('admin.permissions.index') }}"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            <form method="POST" action="{{ route('admin.permissions.update', $role) }}">
                @csrf
                @method('PUT')

                @foreach($permissions as $group => $perms)
                    <div class="crud-card" style="margin-bottom:16px">
                        <div class="crud-header">
                            <h3><i class="fa-solid fa-cubes"></i> {{ ucwords(str_replace('_', ' ', $group)) }}</h3>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:6px;padding:14px 16px">
                            @foreach($perms as $permission)
                                <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border:1px solid var(--bcp-line);border-radius:8px;cursor:pointer;font-size:.78rem">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                        @checked(in_array($permission->id, $selected, true))>
                                    <span>{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save permissions</button>
                    <a class="btn btn-secondary" href="{{ route('admin.permissions.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

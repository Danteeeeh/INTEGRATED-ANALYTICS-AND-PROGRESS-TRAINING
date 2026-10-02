@extends('layouts.admin')
@section('title', 'Backup & Restore')
@php($activeNav = 'backup')
@section('content')
<div class="user-page">
    <x-user-page-header
        title="Backup & Restore"
        subtitle="Create database snapshots and restore from an uploaded backup."
        icon="fa-database"
    >
        <x-slot name="actions">
            <form method="POST" action="{{ route('admin.backup.create') }}" style="display:inline">
                @csrf
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-download"></i> Create backup now</button>
            </form>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-body">
            @if(session('status'))
                <div class="message success" role="status"><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
            @endif
            @if(session('error'))
                <div class="message error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
            @endif

            <div class="crud-card" style="margin-bottom:18px">
                <div class="crud-header"><h3><i class="fa-solid fa-upload"></i> Restore from file</h3></div>
                <div style="padding:16px">
                    <form method="POST" action="{{ route('admin.backup.restore') }}" enctype="multipart/form-data" onsubmit="return confirm('Restoring will replace the current database. Continue?')">
                        @csrf
                        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                            <input type="file" name="backup_file" accept=".sql,.json" class="form-control" style="max-width:380px" required>
                            <button class="btn btn-warning" type="submit"><i class="fa-solid fa-rotate-left"></i> Restore</button>
                        </div>
                        <small style="color:var(--bcp-muted);display:block;margin-top:6px">Supports .sql (mysqldump) and .json (LMS snapshot) files.</small>
                    </form>
                </div>
            </div>

            <div class="crud-card">
                <div class="crud-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Existing backups ({{ count($backups) }})</h3></div>
                <table class="crud-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $backup)
                            <tr>
                                <td><i class="fa-solid fa-file-zipper" style="color:var(--bcp-cyan-400);margin-right:6px"></i>{{ $backup['name'] }}</td>
                                <td>{{ round($backup['size'] / 1024, 1) }} KB</td>
                                <td>{{ $backup['modified_at'] }}</td>
                                <td>
                                    <a href="{{ route('admin.backup.download', $backup['name']) }}" class="btn btn-icon btn-view" title="Download"><i class="fa-solid fa-download"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="search-no-results">No backups yet — click "Create backup now".</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

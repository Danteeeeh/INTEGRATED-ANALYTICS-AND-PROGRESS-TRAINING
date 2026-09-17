@extends('layouts.admin')

@section('title', 'Audit Log Entry')
@php $activeNav = 'audit_logs'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Audit Log Entry"
        subtitle="Details of a single audited action."
        icon="fa-shield-halved"
    >
        <x-slot name="meta">
            <span class="user-status">{{ $auditLog->action }}</span>
            <span>{{ $auditLog->created_at?->format('M j, Y g:i:s A') }}</span>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.audit_logs.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Metadata</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Action</label>
                    <div><strong>{{ $auditLog->action }}</strong></div>
                </div>
                <div class="form-field">
                    <label>Resource</label>
                    <div>{{ $auditLog->resource_type }} #{{ $auditLog->resource_id }}</div>
                </div>
                <div class="form-field">
                    <label>User</label>
                    <div>{{ $auditLog->user?->name ?? 'System' }}</div>
                    <div class="user-email">{{ $auditLog->user?->email }}</div>
                </div>
                <div class="form-field">
                    <label>IP Address</label>
                    <div>{{ $auditLog->ip_address ?? '—' }}</div>
                </div>
                <div class="form-field full">
                    <label>User Agent</label>
                    <div style="word-break:break-all">{{ $auditLog->user_agent ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($auditLog->old_values || $auditLog->new_values)
        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-code-compare"></i> Before / After</h3>
            </div>
            <div class="user-panel-body">
                @php
                    $keys = collect(array_merge(array_keys($auditLog->old_values ?? []), array_keys($auditLog->new_values ?? [])))
                        ->unique()->sort()->values();
                @endphp
                @if($keys->count() > 0)
                    <div class="user-table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Before</th>
                                    <th>After</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($keys as $key)
                                    <tr>
                                        <td><strong>{{ $key }}</strong></td>
                                        <td>
                                            @if(isset($auditLog->old_values[$key]))
                                                <pre style="margin:0;white-space:pre-wrap;word-break:break-word">{{ is_array($auditLog->old_values[$key]) ? json_encode($auditLog->old_values[$key], JSON_PRETTY_PRINT) : $auditLog->old_values[$key] }}</pre>
                                            @else
                                                <span class="user-email">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(isset($auditLog->new_values[$key]))
                                                <pre style="margin:0;white-space:pre-wrap;word-break:break-word">{{ is_array($auditLog->new_values[$key]) ? json_encode($auditLog->new_values[$key], JSON_PRETTY_PRINT) : $auditLog->new_values[$key] }}</pre>
                                            @else
                                                <span class="user-email">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="user-actions">
        <a href="{{ route('admin.audit_logs.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Audit Logs</a>
    </div>
</div>
@endsection

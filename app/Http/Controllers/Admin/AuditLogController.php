<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAuditLogs');

        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('resource_type')) {
            $query->where('resource_type', 'like', "%{$request->resource_type}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('resource_type', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $auditLogs = $query->paginate(20);
        $users = User::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.audit_logs.index', compact('auditLogs', 'users'));
    }

    public function show(AuditLog $auditLog): View
    {
        $this->authorize('viewAuditLogs');

        $auditLog->load('user');

        return view('admin.audit_logs.show', compact('auditLog'));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('exportAuditLogs');

        $filename = 'audit-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User', 'Action', 'Resource Type', 'Resource ID', 'IP Address', 'User Agent', 'Created At']);

            AuditLog::with('user')
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', "%{$request->action}%"))
                ->when($request->filled('resource_type'), fn ($q) => $q->where('resource_type', 'like', "%{$request->resource_type}%"))
                ->orderByDesc('created_at')
                ->chunk(config('lms.export_chunk_size'), function ($chunk) use ($handle) {
                    foreach ($chunk as $log) {
                        fputcsv($handle, [
                            $log->id,
                            $log->user?->full_name ?? 'System',
                            $log->action,
                            $log->resource_type,
                            $log->resource_id,
                            $log->ip_address,
                            $log->user_agent,
                            $log->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

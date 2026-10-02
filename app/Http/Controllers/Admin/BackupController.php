<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private BackupService $backups) {}

    public function index(): View
    {
        $backups = $this->backups->list();

        return view('admin.backup.index', compact('backups'));
    }

    public function create(): RedirectResponse
    {
        $name = $this->backups->create();

        return back()->with('status', "Backup created: {$name}");
    }

    public function download(Request $request, string $file): StreamedResponse
    {
        $path = $this->backups->resolvePath($file);

        return response()->streamDownload(
            fn () => readfile($path),
            $file,
            ['Content-Type' => 'application/octet-stream']
        );
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'mimes:sql,json,txt,application/octet-stream', 'max:51200'],
        ]);

        $uploaded = $request->file('backup_file');
        $originalName = $uploaded->getClientOriginalName();

        if (! in_array(strtolower($uploaded->getClientOriginalExtension()), ['sql', 'json'], true)) {
            return back()->with('error', 'Only .sql or .json backup files are supported.');
        }

        $storedPath = $uploaded->storeAs('backups', 'restore_'.now()->format('Ymd_His').'.'.strtolower($uploaded->getClientOriginalExtension()));

        try {
            $this->backups->restore(basename($storedPath));
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }

        return back()->with('status', "Backup restored from {$originalName}.");
    }
}

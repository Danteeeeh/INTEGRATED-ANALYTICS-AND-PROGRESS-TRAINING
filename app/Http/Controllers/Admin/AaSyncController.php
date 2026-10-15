<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AaSyncLog;
use App\Services\AaSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin "Sync from AA" center — the ONLY way enrollments enter the LMS.
 *
 * The Super Admin does not enroll manually: one click pulls departments,
 * programs, sections, students, faculty, classes, and enrollments from the
 * AA database (direct connection, see config/aa_sync.php for the mapping).
 */
class AaSyncController extends Controller
{
    public function __construct(private AaSyncService $sync) {}

    public function index(): View
    {
        return view('admin.aa-sync.index', [
            'connected' => $this->sync->available(),
            'connectionError' => $this->sync->connectionError(),
            'entities' => config('aa_sync.sync_order', []),
            'logs' => AaSyncLog::with('performedBy')->latest('id')->limit(10)->get(),
            'lastSync' => AaSyncLog::with('performedBy')->latest('id')->first(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entities' => ['required', 'array', 'min:1'],
            'entities.*' => ['string'],
        ]);

        if (! $this->sync->available()) {
            return back()->with('error', 'Hindi makakonekta sa AA database. I-check ang AA_DB_* values sa .env.');
        }

        $log = $this->sync->run($validated['entities']);

        if ($log->status === AaSyncLog::STATUS_SUCCESS) {
            return back()->with(
                'success',
                "Sync tapos — bagong record: {$log->records_created}, na-update: {$log->records_updated}, naka-skip: {$log->records_skipped}, na-fail: {$log->records_failed}."
            );
        }

        return back()->with('error', 'Sync nabigo: '.$log->error_message);
    }
}

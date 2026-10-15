<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only enrollment listing for AA Staff — view only.
 *
 * This is the list AA checks against the AA system's official enrollment
 * record. The LMS copy is populated by the admin's "Sync from AA".
 */
class EnrollmentController extends Controller
{
    public function index(Request $request): View
    {
        $enrollments = Enrollment::with(['student', 'class.course'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->whereHas('student', fn ($s) => $s->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('aa.enrollments.index', compact('enrollments'));
    }
}

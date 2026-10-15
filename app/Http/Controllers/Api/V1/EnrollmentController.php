<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Enrollment API — READ-ONLY.
 *
 * AA is the authoritative source for enrollment, so this API intentionally
 * exposes no create/update/delete. Writes arrive via the admin's
 * "Sync from AA" (direct DB connection) only.
 */
class EnrollmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Enrollment::with(['student', 'class.course']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->latest()->paginate($request->input('per_page', 15));

        return EnrollmentResource::collection($enrollments);
    }

    public function show(Enrollment $enrollment): EnrollmentResource
    {
        $enrollment->load(['student', 'class.course', 'class.instructor']);

        return new EnrollmentResource($enrollment);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

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

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|exists:users,id',
                'class_id' => 'required|exists:classes,id',
                'status' => 'required|in:pending,active,completed,dropped',
                'notes' => 'nullable|string',
            ]);

            // Check if student is already enrolled in this class
            $existing = Enrollment::where('student_id', $validated['student_id'])
                ->where('class_id', $validated['class_id'])
                ->where('status', '!=', 'dropped')
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is already enrolled in this class',
                ], 400);
            }

            $enrollment = Enrollment::create($validated);
            $enrollment->load(['student', 'class.course']);

            return response()->json([
                'success' => true,
                'message' => 'Enrollment created successfully',
                'data' => new EnrollmentResource($enrollment),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, Enrollment $enrollment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,active,completed,dropped',
                'final_grade' => 'sometimes|numeric|min:0|max:100',
                'notes' => 'nullable|string',
            ]);

            $enrollment->update($validated);
            $enrollment->load(['student', 'class.course']);

            return response()->json([
                'success' => true,
                'message' => 'Enrollment updated successfully',
                'data' => new EnrollmentResource($enrollment),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Enrollment $enrollment): JsonResponse
    {
        $enrollment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Enrollment deleted successfully',
        ]);
    }
}

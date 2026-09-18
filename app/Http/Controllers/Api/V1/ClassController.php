<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Models\ClassModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ClassController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ClassModel::with(['course', 'instructor', 'academicPeriod']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%'.$request->search.'%')
                    ->orWhereHas('course', function ($q) use ($request) {
                        $q->where('title', 'like', '%'.$request->search.'%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        $classes = $query->latest()->paginate($request->input('per_page', 15));

        return ClassResource::collection($classes);
    }

    public function show(ClassModel $class): ClassResource
    {
        $class->load(['course', 'instructor', 'academicPeriod', 'enrollments']);

        return new ClassResource($class);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:classes',
                'course_id' => 'required|exists:courses,id',
                'instructor_id' => 'required|exists:users,id',
                'academic_period_id' => 'nullable|exists:academic_periods,id',
                'schedule' => 'nullable|string',
                'room' => 'nullable|string|max:100',
                'capacity' => 'nullable|integer|min:1',
                'status' => 'required|in:active,inactive,archived',
            ]);

            $class = ClassModel::create($validated);
            $class->load(['course', 'instructor', 'academicPeriod']);

            return response()->json([
                'success' => true,
                'message' => 'Class created successfully',
                'data' => new ClassResource($class),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, ClassModel $class): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'sometimes|string|max:50|unique:classes,code,'.$class->id,
                'course_id' => 'sometimes|exists:courses,id',
                'instructor_id' => 'sometimes|exists:users,id',
                'academic_period_id' => 'nullable|exists:academic_periods,id',
                'schedule' => 'nullable|string',
                'room' => 'nullable|string|max:100',
                'capacity' => 'nullable|integer|min:1',
                'status' => 'sometimes|in:active,inactive,archived',
            ]);

            $class->update($validated);
            $class->load(['course', 'instructor', 'academicPeriod']);

            return response()->json([
                'success' => true,
                'message' => 'Class updated successfully',
                'data' => new ClassResource($class),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(ClassModel $class): JsonResponse
    {
        $class->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class deleted successfully',
        ]);
    }
}

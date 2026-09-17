<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Course::with(['creator', 'category', 'academicPeriod']);

        // Apply filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('academic_period_id')) {
            $query->where('academic_period_id', $request->academic_period_id);
        }

        $courses = $query->latest()->paginate($request->input('per_page', 15));

        return CourseResource::collection($courses);
    }

    public function show(Course $course): CourseResource
    {
        $course->load(['creator', 'category', 'academicPeriod', 'modules']);

        return new CourseResource($course);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:courses',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'objectives' => 'nullable|string',
                'syllabus' => 'nullable|string',
                'prerequisites' => 'nullable|string',
                'duration_weeks' => 'nullable|integer|min:1',
                'credits' => 'nullable|integer|min:1',
                'category_id' => 'nullable|exists:course_categories,id',
                'academic_period_id' => 'nullable|exists:academic_periods,id',
                'thumbnail' => 'nullable|string',
                'status' => 'required|in:draft,published,archived',
            ]);

            $validated['created_by'] = auth()->id();
            $course = Course::create($validated);
            $course->load(['creator', 'category', 'academicPeriod']);

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => new CourseResource($course),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, Course $course): JsonResponse
    {
        try {
            $validated = $request->validate([
                'code' => 'sometimes|string|max:50|unique:courses,code,'.$course->id,
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'objectives' => 'nullable|string',
                'syllabus' => 'nullable|string',
                'prerequisites' => 'nullable|string',
                'duration_weeks' => 'nullable|integer|min:1',
                'credits' => 'nullable|integer|min:1',
                'category_id' => 'nullable|exists:course_categories,id',
                'academic_period_id' => 'nullable|exists:academic_periods,id',
                'thumbnail' => 'nullable|string',
                'status' => 'sometimes|in:draft,published,archived',
            ]);

            $course->update($validated);
            $course->load(['creator', 'category', 'academicPeriod']);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => new CourseResource($course),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course deleted successfully',
        ]);
    }
}

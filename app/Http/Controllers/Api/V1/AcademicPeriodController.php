<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicPeriodResource;
use App\Models\AcademicPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class AcademicPeriodController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = AcademicPeriod::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_current', $request->status === 'current');
        }

        $periods = $query->latest()->paginate($request->input('per_page', 15));

        return AcademicPeriodResource::collection($periods);
    }

    public function show(AcademicPeriod $academicPeriod): AcademicPeriodResource
    {
        return new AcademicPeriodResource($academicPeriod);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:50|unique:academic_periods',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'is_current' => 'sometimes|boolean',
                'is_enrollment_open' => 'sometimes|boolean',
                'description' => 'nullable|string',
            ]);

            $period = AcademicPeriod::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Academic period created successfully',
                'data' => new AcademicPeriodResource($period),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, AcademicPeriod $academicPeriod): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:50|unique:academic_periods,code,'.$academicPeriod->id,
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'is_current' => 'sometimes|boolean',
                'is_enrollment_open' => 'sometimes|boolean',
                'description' => 'nullable|string',
            ]);

            $academicPeriod->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Academic period updated successfully',
                'data' => new AcademicPeriodResource($academicPeriod),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function destroy(AcademicPeriod $academicPeriod): JsonResponse
    {
        $academicPeriod->delete();

        return response()->json([
            'success' => true,
            'message' => 'Academic period deleted successfully',
        ]);
    }
}

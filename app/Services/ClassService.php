<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClassService
{
    public function getAllClasses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ClassModel::with(['course', 'instructor', 'academicPeriod']);

        if (filled($filters['course_id'] ?? null)) {
            $query->where('course_id', $filters['course_id']);
        }

        if (filled($filters['instructor_id'] ?? null)) {
            $query->where('instructor_id', $filters['instructor_id']);
        }

        if (filled($filters['academic_period_id'] ?? null)) {
            $query->where('academic_period_id', $filters['academic_period_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('room', 'like', "%{$search}%")
                    ->orWhere('schedule', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getClassById(int $id): ClassModel
    {
        return ClassModel::with(['course', 'instructor', 'academicPeriod', 'enrollments.student'])
            ->findOrFail($id);
    }

    public function createClass(array $data): ClassModel
    {
        return DB::transaction(function () use ($data) {
            $class = ClassModel::create([
                'code' => $data['code'],
                'course_id' => $data['course_id'],
                'instructor_id' => $data['instructor_id'],
                'academic_period_id' => $data['academic_period_id'] ?? null,
                'schedule' => $data['schedule'] ?? null,
                'room' => $data['room'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            return $class;
        });
    }

    public function updateClass(int $id, array $data): ClassModel
    {
        $class = ClassModel::findOrFail($id);

        $class->update([
            'code' => $data['code'] ?? $class->code,
            'course_id' => $data['course_id'] ?? $class->course_id,
            'instructor_id' => $data['instructor_id'] ?? $class->instructor_id,
            'academic_period_id' => $data['academic_period_id'] ?? $class->academic_period_id,
            'schedule' => $data['schedule'] ?? $class->schedule,
            'room' => $data['room'] ?? $class->room,
            'capacity' => $data['capacity'] ?? $class->capacity,
            'status' => $data['status'] ?? $class->status,
        ]);

        return $class->fresh();
    }

    public function deleteClass(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $class = ClassModel::findOrFail($id);

            // Check if class has active enrollments
            if ($class->enrollments()->where('status', 'active')->exists()) {
                throw new \Exception('Cannot delete class with active enrollments');
            }

            return $class->delete();
        });
    }

    public function assignInstructor(int $classId, int $instructorId): ClassModel
    {
        $class = ClassModel::findOrFail($classId);
        $instructor = User::where('id', $instructorId)
            ->whereHas('role', function ($query) {
                $query->where('slug', 'instructor');
            })
            ->firstOrFail();

        $class->update(['instructor_id' => $instructorId]);

        return $class->fresh();
    }

    public function getClassesByInstructor(User $instructor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $instructor->classesInstructing()
            ->with(['course', 'academicPeriod', 'enrollments.student']);

        if (filled($filters['academic_period_id'] ?? null)) {
            $query->where('academic_period_id', $filters['academic_period_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getClassesByStudent(User $student, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $student->enrolledClasses()
            ->with(['course', 'instructor', 'academicPeriod'])
            ->wherePivot('status', 'active');

        if (isset($filters['academic_period_id'])) {
            $query->where('academic_period_id', $filters['academic_period_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getClassRoster(int $classId): array
    {
        $class = ClassModel::with(['enrollments.student'])->findOrFail($classId);

        return [
            'class' => $class,
            'students' => $class->enrollments->map(function ($enrollment) {
                return [
                    'student' => $enrollment->student,
                    'enrollment_status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'final_grade' => $enrollment->final_grade,
                ];
            }),
            'total_enrolled' => $class->enrollments->where('status', 'active')->count(),
            'capacity' => $class->capacity,
            'available_slots' => $class->capacity ? $class->capacity - $class->enrollments->where('status', 'active')->count() : null,
        ];
    }

    public function getClassPerformance(int $classId): array
    {
        $class = ClassModel::with(['enrollments.student'])->findOrFail($classId);

        $activeEnrollments = $class->enrollments->where('status', 'active');
        $gradedEnrollments = $activeEnrollments->filter(function ($enrollment) {
            return $enrollment->final_grade !== null;
        });

        return [
            'class' => $class,
            'total_students' => $activeEnrollments->count(),
            'graded_students' => $gradedEnrollments->count(),
            'average_grade' => $gradedEnrollments->avg('final_grade'),
            'highest_grade' => $gradedEnrollments->max('final_grade'),
            'lowest_grade' => $gradedEnrollments->min('final_grade'),
            'performance_distribution' => [
                'excellent' => $gradedEnrollments->filter(fn ($e) => $e->final_grade >= 90)->count(),
                'good' => $gradedEnrollments->filter(fn ($e) => $e->final_grade >= 80 && $e->final_grade < 90)->count(),
                'satisfactory' => $gradedEnrollments->filter(fn ($e) => $e->final_grade >= 70 && $e->final_grade < 80)->count(),
                'needs_improvement' => $gradedEnrollments->filter(fn ($e) => $e->final_grade < 70)->count(),
            ],
        ];
    }
}

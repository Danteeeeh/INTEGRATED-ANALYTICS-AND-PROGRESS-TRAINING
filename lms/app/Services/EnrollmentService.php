<?php

namespace App\Services;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function getAllEnrollments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::with(['student', 'class.course', 'class.instructor', 'class.academicPeriod']);

        if (filled($filters['student_id'] ?? null)) {
            $query->where('student_id', $filters['student_id']);
        }

        if (filled($filters['class_id'] ?? null)) {
            $query->where('class_id', $filters['class_id']);
        }

        if (filled($filters['course_id'] ?? null)) {
            $query->whereHas('class', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (filled($filters['academic_period_id'] ?? null)) {
            $query->whereHas('class', function ($q) use ($filters) {
                $q->where('academic_period_id', $filters['academic_period_id']);
            });
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getEnrollmentById(int $id): Enrollment
    {
        return Enrollment::with(['student', 'class.course', 'class.instructor', 'class.academicPeriod'])
            ->findOrFail($id);
    }

    public function createEnrollment(array $data): Enrollment
    {
        return DB::transaction(function () use ($data) {
            $class = ClassModel::findOrFail($data['class_id']);
            $student = User::findOrFail($data['student_id']);

            // Check if student is already enrolled in this class
            $existingEnrollment = Enrollment::where('class_id', $data['class_id'])
                ->where('student_id', $data['student_id'])
                ->where('status', 'active')
                ->first();

            if ($existingEnrollment) {
                throw new \Exception('Student is already enrolled in this class');
            }

            // Check class capacity
            if ($class->capacity) {
                $currentEnrollments = Enrollment::where('class_id', $data['class_id'])
                    ->where('status', 'active')
                    ->count();

                if ($currentEnrollments >= $class->capacity) {
                    throw new \Exception('Class has reached maximum capacity');
                }
            }

            $enrollment = Enrollment::create([
                'class_id' => $data['class_id'],
                'student_id' => $data['student_id'],
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'enrolled_at' => now(),
            ]);

            return $enrollment;
        });
    }

    public function updateEnrollment(int $id, array $data): Enrollment
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->update([
            'status' => $data['status'] ?? $enrollment->status,
            'final_grade' => $data['final_grade'] ?? $enrollment->final_grade,
            'notes' => $data['notes'] ?? $enrollment->notes,
            'completed_at' => ($data['status'] === 'completed') ? now() : $enrollment->completed_at,
        ]);

        return $enrollment->fresh();
    }

    public function deleteEnrollment(int $id): bool
    {
        $enrollment = Enrollment::findOrFail($id);

        return $enrollment->delete();
    }

    public function bulkEnroll(int $classId, array $studentIds): array
    {
        $class = ClassModel::findOrFail($classId);
        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::transaction(function () use ($classId, $studentIds, &$results) {
            foreach ($studentIds as $studentId) {
                try {
                    $enrollment = $this->createEnrollment([
                        'class_id' => $classId,
                        'student_id' => $studentId,
                        'status' => 'active',
                    ]);
                    $results['success'][] = $enrollment->student_id;
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'student_id' => $studentId,
                        'reason' => $e->getMessage(),
                    ];
                }
            }
        });

        return $results;
    }

    public function dropStudent(int $enrollmentId): Enrollment
    {
        $enrollment = Enrollment::findOrFail($enrollmentId);

        $enrollment->update([
            'status' => 'dropped',
        ]);

        return $enrollment->fresh();
    }

    public function transferStudent(int $enrollmentId, int $newClassId): Enrollment
    {
        return DB::transaction(function () use ($enrollmentId, $newClassId) {
            $oldEnrollment = Enrollment::findOrFail($enrollmentId);

            // Create new enrollment
            $newEnrollment = $this->createEnrollment([
                'class_id' => $newClassId,
                'student_id' => $oldEnrollment->student_id,
                'status' => 'active',
                'notes' => 'Transferred from class '.$oldEnrollment->class_id,
            ]);

            // Mark old enrollment as transferred
            $oldEnrollment->update([
                'status' => 'transferred',
                'notes' => 'Transferred to class '.$newClassId,
            ]);

            return $newEnrollment;
        });
    }

    public function getEnrollmentsByStudent(User $student, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $student->enrollments()
            ->with(['class.course', 'class.instructor', 'class.academicPeriod']);

        if (isset($filters['academic_period_id'])) {
            $query->whereHas('class', function ($q) use ($filters) {
                $q->where('academic_period_id', $filters['academic_period_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getEnrollmentsByClass(int $classId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Enrollment::where('class_id', $classId)
            ->with(['student', 'class.course', 'class.instructor']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getEnrollmentHistory(int $studentId): array
    {
        $enrollments = Enrollment::where('student_id', $studentId)
            ->with(['class.course', 'class.academicPeriod'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', 'active')->count(),
            'completed_enrollments' => $enrollments->where('status', 'completed')->count(),
            'dropped_enrollments' => $enrollments->where('status', 'dropped')->count(),
            'enrollments' => $enrollments,
        ];
    }
}

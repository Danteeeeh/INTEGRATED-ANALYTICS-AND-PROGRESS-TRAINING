<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public function getAllCourses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Course::with(['academicPeriod', 'creator']);

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
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getCourseById(int $id): Course
    {
        return Course::with(['academicPeriod', 'creator'])
            ->findOrFail($id);
    }

    public function createCourse(array $data, User $creator): Course
    {
        return DB::transaction(function () use ($data, $creator) {
            $course = Course::create([
                'code' => $data['code'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'syllabus' => $data['syllabus'] ?? null,
                'prerequisites' => $data['prerequisites'] ?? null,
                'duration_weeks' => $data['duration_weeks'] ?? null,
                'academic_period_id' => $data['academic_period_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'thumbnail' => $data['thumbnail'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'created_by' => $creator->id,
            ]);

            return $course;
        });
    }

    public function updateCourse(int $id, array $data): Course
    {
        return DB::transaction(function () use ($id, $data) {
            $course = Course::findOrFail($id);

            $course->update([
                'code' => $data['code'] ?? $course->code,
                'title' => $data['title'] ?? $course->title,
                'description' => $data['description'] ?? $course->description,
                'objectives' => $data['objectives'] ?? $course->objectives,
                'syllabus' => $data['syllabus'] ?? $course->syllabus,
                'prerequisites' => $data['prerequisites'] ?? $course->prerequisites,
                'credits' => $data['credits'] ?? $course->credits,
                'duration_weeks' => $data['duration_weeks'] ?? $course->duration_weeks,
                'academic_period_id' => $data['academic_period_id'] ?? $course->academic_period_id,
                'category_id' => $data['category_id'] ?? $course->category_id,
                'thumbnail' => $data['thumbnail'] ?? $course->thumbnail,
                'status' => $data['status'] ?? $course->status,
            ]);

            return $course->fresh();
        });
    }

    public function deleteCourse(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $course = Course::findOrFail($id);

            if ($course->classes()->exists()) {
                throw new \RuntimeException('Cannot delete a course that still has classes.');
            }

            return $course->delete();
        });
    }

    public function publishCourse(int $id): Course
    {
        $course = Course::findOrFail($id);
        $course->update(['status' => 'published']);

        return $course->fresh();
    }

    public function archiveCourse(int $id): Course
    {
        $course = Course::findOrFail($id);
        $course->update(['status' => 'archived']);

        return $course->fresh();
    }

    public function duplicateCourse(int $id, User $creator): Course
    {
        $original = Course::findOrFail($id);

        return DB::transaction(function () use ($original, $creator) {
            $newCourse = $original->replicate();
            $newCourse->code = $original->code.'-COPY';
            $newCourse->title = $original->title.' (Copy)';
            $newCourse->status = 'draft';
            $newCourse->created_by = $creator->id;
            $newCourse->save();

            return $newCourse;
        });
    }

    public function getCoursesByInstructor(User $instructor, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $courseIds = $instructor->classesInstructing()->pluck('course_id');

        $query = Course::with(['academicPeriod', 'creator'])
            ->whereIn('id', $courseIds);

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
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('code')->paginate($perPage);
    }

    public function getCoursesByStudent(User $student, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $student->enrolledClasses()
            ->with('course')
            ->whereHas('course')
            ->wherePivot('status', 'active')
            ->with(['course.academicPeriod']);

        if (isset($filters['academic_period_id'])) {
            $query->whereHas('course', function ($q) use ($filters) {
                $q->where('academic_period_id', $filters['academic_period_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}

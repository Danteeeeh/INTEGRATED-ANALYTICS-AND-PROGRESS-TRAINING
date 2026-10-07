<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Services\LessonCompletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function __construct(
        protected LessonCompletionService $completion
    ) {
    }

    public function indexAll(): View
    {
        $studentId = auth()->id();

        $enrollments = Enrollment::where('student_id', $studentId)
            ->where('status', 'active')
            ->with('class.course')
            ->get();

        $classIds = $enrollments->pluck('class_id');

        $lessons = Lesson::published()
            ->whereHas('module.course.classes', function ($q) use ($classIds) {
                $q->whereIn('id', $classIds);
            })
            ->with(['module.course', 'module'])
            ->orderBy('module_id')
            ->orderBy('position')
            ->paginate(20);

        return view('student.lessons.index', compact('lessons', 'enrollments'));
    }

    public function show(Course $course, Module $module, Lesson $lesson): View
    {
        $studentId = auth()->id();

        $enrollment = $this->activeEnrollment($course, $studentId);

        if (! $enrollment) {
            abort(403);
        }

        $lesson->load('lessonMaterials', 'lessonMaterials.mediaFile');

        $progress = LessonProgress::firstOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'progress_percent' => 0,
                'total_seconds' => 0,
            ]
        );

        // Auto-track: mark in_progress if it was not started, and always
        // accumulate time + refresh last_accessed_at.
        if ($progress->status === LessonProgress::STATUS_NOT_STARTED) {
            $progress->forceFill(['status' => LessonProgress::STATUS_IN_PROGRESS])->save();
        }

        $this->completion->recordVisit($progress);

        $accessedMaterialIds = $this->completion->accessedMaterialIds($lesson, $studentId);
        $checklist = $this->completion->checklist($lesson, $progress, $accessedMaterialIds);
        $canComplete = $this->completion->isEligible($lesson, $progress, $accessedMaterialIds);
        $hasRules = $this->completion->hasRules($lesson);

        return view('student.lessons.show', compact(
            'course',
            'module',
            'lesson',
            'enrollment',
            'progress',
            'checklist',
            'canComplete',
            'hasRules',
            'accessedMaterialIds'
        ));
    }

    public function complete(Request $request, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = $this->activeEnrollment($course, $studentId);

        if (! $enrollment) {
            abort(403);
        }

        $progress = LessonProgress::firstOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'progress_percent' => 0,
                'total_seconds' => 0,
            ]
        );

        $accessedMaterialIds = $this->completion->accessedMaterialIds($lesson, $studentId);

        if (! $this->completion->isEligible($lesson, $progress, $accessedMaterialIds)) {
            return redirect()->route('student.courses.modules.lessons.show', [$course, $module, $lesson])
                ->with('error', 'Complete the lesson requirements first before marking this lesson complete.');
        }

        $progress->forceFill([
            'status' => LessonProgress::STATUS_COMPLETED,
            'completed_at' => now(),
            'progress_percent' => 100,
            'last_accessed_at' => now(),
        ])->save();

        return redirect()->route('student.courses.modules.show', [$course, $module])
            ->with('status', 'Lesson marked as completed!');
    }

    /**
     * AJAX endpoint: current checklist + eligibility state, so the student
     * page can refresh (e.g. after time accrues) without a full reload.
     */
    public function checklist(Request $request, Course $course, Module $module, Lesson $lesson): JsonResponse
    {
        $studentId = auth()->id();

        if (! $this->activeEnrollment($course, $studentId)) {
            abort(403);
        }

        $lesson->load('lessonMaterials', 'lessonMaterials.mediaFile');

        $progress = LessonProgress::firstOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'progress_percent' => 0,
                'total_seconds' => 0,
            ]
        );

        // Track time on checklist refreshes too, so the min-minutes rule
        // advances while the student keeps the page open.
        $this->completion->recordVisit($progress);

        $accessedMaterialIds = $this->completion->accessedMaterialIds($lesson, $studentId);

        return response()->json([
            'success' => true,
            'canComplete' => $this->completion->isEligible($lesson, $progress, $accessedMaterialIds),
            'checklist' => $this->completion->checklist($lesson, $progress, $accessedMaterialIds),
        ]);
    }

    /**
     * AJAX endpoint: mark a lesson material as accessed by the student.
     */
    public function markMaterial(Request $request, Course $course, Module $module, Lesson $lesson, LessonMaterial $material): JsonResponse
    {
        $studentId = auth()->id();

        if (! $this->activeEnrollment($course, $studentId)) {
            abort(403);
        }

        if ($material->lesson_id !== $lesson->id) {
            abort(404);
        }

        $ok = $this->completion->markMaterialAccessed($lesson, $material->id, $studentId);

        // Recompute the checklist so the client can refresh its state.
        $progress = LessonProgress::firstOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'progress_percent' => 0,
                'total_seconds' => 0,
            ]
        );

        $accessedMaterialIds = $this->completion->accessedMaterialIds($lesson, $studentId);
        $checklist = $this->completion->checklist($lesson, $progress, $accessedMaterialIds);

        return response()->json([
            'success' => $ok,
            'canComplete' => $this->completion->isEligible($lesson, $progress, $accessedMaterialIds),
            'checklist' => $checklist,
        ]);
    }

    private function activeEnrollment(Course $course, int $studentId): ?Enrollment
    {
        return Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();
    }
}

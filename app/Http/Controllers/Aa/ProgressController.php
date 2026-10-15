<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\ContentProgressService;
use Illuminate\View\View;

/**
 * Read-only LMS progress view for AA Staff — view only.
 *
 * Shows how far students have gone through the LMS side of their subjects
 * (modules/lessons/quizzes/assignments). This is LMS activity data, NOT the
 * official grade — that stays in AA.
 */
class ProgressController extends Controller
{
    public function index(ContentProgressService $progress): View
    {
        $courses = Course::orderBy('title')->get();

        // Per-course average live LMS progress across active enrollments.
        $progressData = $courses->map(function ($course) use ($progress) {
            $enrollments = $course->enrollments()
                ->where('enrollments.status', 'active')
                ->get();

            if ($enrollments->isEmpty()) {
                return [
                    'course' => $course,
                    'students' => 0,
                    'average' => 0,
                ];
            }

            $total = 0;
            foreach ($enrollments as $enrollment) {
                $total += $progress->calculateCourseLiveProgress($course, $enrollment->student_id, $enrollment->class_id)['overall'] ?? 0;
            }

            return [
                'course' => $course,
                'students' => $enrollments->count(),
                'average' => $total / $enrollments->count(),
            ];
        })->filter(fn ($row) => $row['students'] > 0);

        return view('aa.progress.index', ['progressData' => $progressData]);
    }
}

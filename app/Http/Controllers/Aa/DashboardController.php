<?php

namespace App\Http\Controllers\Aa;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

/**
 * AA Staff / Academic Officer dashboard — VIEW-ONLY.
 *
 * The AA system remains the source of truth for official records and final
 * grades; this panel exists purely so AA can look at what the LMS holds
 * (who is enrolled, which classes exist, LMS-side progress). No create,
 * update, or delete actions are exposed anywhere in this controller.
 */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $studentRoleId = Role::where('slug', Role::STUDENT)->value('id');
        $instructorRoleId = Role::where('slug', Role::INSTRUCTOR)->value('id');

        $stats = [
            'students' => User::where('role_id', $studentRoleId)->where('status', 'active')->count(),
            'instructors' => User::where('role_id', $instructorRoleId)->where('status', 'active')->count(),
            'courses' => Course::count(),
            'classes' => ClassModel::where('status', 'active')->count(),
            'enrollments_active' => Enrollment::where('status', 'active')->count(),
            'enrollments_completed' => Enrollment::where('status', 'completed')->count(),
            'enrollments_pending' => Enrollment::where('status', 'pending')->count(),
        ];

        // Courses with at least one class, biggest enrollment first.
        $courseOverview = Course::withCount(['classes'])
            ->orderByDesc('classes_count')
            ->limit(10)
            ->get()
            ->map(function ($course) {
                $course->enrollments_count = Enrollment::whereHas('class', fn ($q) => $q->where('course_id', $course->id))
                    ->where('status', 'active')
                    ->count();

                return $course;
            });

        $recentEnrollments = Enrollment::with(['student', 'class.course'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('aa.dashboard', compact('stats', 'courseOverview', 'recentEnrollments'));
    }
}

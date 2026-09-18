<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Course $course, Module $module, Lesson $lesson): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $lesson->load('materials');

        $progress = LessonProgress::firstOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_IN_PROGRESS,
                'started_at' => now(),
                'progress_percent' => 0,
            ]
        );

        $progress->update(['last_accessed_at' => now()]);

        return view('student.lessons.show', compact('course', 'module', 'lesson', 'enrollment', 'progress'));
    }

    public function complete(Request $request, Course $course, Module $module, Lesson $lesson): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->whereHas('class', function ($q) use ($course) {
                $q->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $progress = LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $studentId],
            [
                'status' => LessonProgress::STATUS_COMPLETED,
                'completed_at' => now(),
                'progress_percent' => 100,
                'last_accessed_at' => now(),
            ]
        );

        if (! $progress->wasRecentlyCreated && ! $progress->started_at) {
            $progress->update(['started_at' => now()]);
        }

        return redirect()->route('student.courses.modules.show', [$course, $module])
            ->with('status', 'Lesson marked as completed!');
    }
}

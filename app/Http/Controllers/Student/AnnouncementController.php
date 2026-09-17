<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementView;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Course $course): View
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

        $announcements = Announcement::published()
            ->forUser($studentId)
            ->where(function ($q) use ($course, $enrollment) {
                $q->where(function ($q2) use ($course) {
                    $q2->where('audience_type', Announcement::AUDIENCE_COURSE)
                        ->where('course_id', $course->id);
                })->orWhere(function ($q2) use ($enrollment) {
                    $q2->where('audience_type', Announcement::AUDIENCE_CLASS)
                        ->where('class_id', $enrollment->class_id);
                });
            })
            ->with('creator', 'attachment')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('publish_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('student.announcements.index', compact('course', 'announcements', 'enrollment'));
    }

    public function show(Course $course, Announcement $announcement): View
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

        $isAccessible = false;
        if ($announcement->audience_type === Announcement::AUDIENCE_COURSE && $announcement->course_id === $course->id) {
            $isAccessible = true;
        } elseif ($announcement->audience_type === Announcement::AUDIENCE_CLASS && $announcement->class_id === $enrollment->class_id) {
            $isAccessible = true;
        }

        if (! $isAccessible) {
            abort(403);
        }

        $announcement->load('creator', 'attachment');

        AnnouncementView::firstOrCreate([
            'announcement_id' => $announcement->id,
            'user_id' => $studentId,
        ]);

        return view('student.announcements.show', compact('course', 'announcement', 'enrollment'));
    }
}

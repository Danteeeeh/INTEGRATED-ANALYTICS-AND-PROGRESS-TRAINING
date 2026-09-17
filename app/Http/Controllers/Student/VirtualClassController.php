<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualClassController extends Controller
{
    public function index(ClassModel $class): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        $virtualClasses = VirtualClass::where('class_id', $class->id)
            ->with(['instructor', 'attendees' => function ($q) use ($studentId) {
                $q->where('user_id', $studentId);
            }])
            ->orderBy('meeting_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(10);

        return view('student.virtual_classes.index', compact('class', 'enrollment', 'virtualClasses'));
    }

    public function show(ClassModel $class, VirtualClass $virtualClass): View
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if ($virtualClass->class_id !== $class->id) {
            abort(403);
        }

        $virtualClass->load('instructor');

        $myAttendance = VirtualClassAttendee::where('virtual_class_id', $virtualClass->id)
            ->where('user_id', $studentId)
            ->first();

        return view('student.virtual_classes.show', compact('class', 'enrollment', 'virtualClass', 'myAttendance'));
    }

    public function join(Request $request, ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $studentId = auth()->id();

        $enrollment = Enrollment::where('student_id', $studentId)
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if ($virtualClass->class_id !== $class->id) {
            abort(403);
        }

        $attendee = VirtualClassAttendee::firstOrCreate(
            ['virtual_class_id' => $virtualClass->id, 'user_id' => $studentId],
            [
                'joined_at' => now(),
                'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
            ]
        );

        if (! $attendee->wasRecentlyCreated && ! $attendee->joined_at) {
            $attendee->update(['joined_at' => now()]);
        }

        $meetingUrl = $virtualClass->meeting_url;

        if ($meetingUrl) {
            return redirect()->away($meetingUrl);
        }

        return back()->with('status', 'Your attendance has been recorded.');
    }
}

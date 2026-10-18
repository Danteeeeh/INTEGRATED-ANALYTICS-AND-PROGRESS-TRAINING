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

        // Sessions whose end time has passed close themselves on view, so the
        // student never sees a finished meeting still offering a Join button.
        $virtualClasses->getCollection()->each->syncStatus();

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
        $virtualClass->syncStatus();

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

        // Built-in Virtual Classroom (LiveKit): the student joins inside the
        // LMS. The room controller re-validates enrollment + class status
        // before issuing a token, and records attendance on token issue.
        if ($virtualClass->isLiveKit()) {
            if (! $virtualClass->canStudentJoin()) {
                return back()->with('error', $virtualClass->joinUnavailableReason()
                    ?? 'This virtual class is not open for joining yet.');
            }

            return redirect()->route('student.classes.virtual_classes.room', [$class, $virtualClass]);
        }

        if ($virtualClass->status === VirtualClass::STATUS_CANCELLED) {
            return back()->with('error', 'This class was cancelled.');
        }

        if ($virtualClass->hasEnded()) {
            return back()->with('error', 'This class has already ended.');
        }

        // Legacy external-provider records (Zoom/Meet/Teams) keep their link.
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

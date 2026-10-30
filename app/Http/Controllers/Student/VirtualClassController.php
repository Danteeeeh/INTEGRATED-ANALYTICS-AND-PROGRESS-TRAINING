<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use App\Services\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VirtualClassController extends Controller
{
    public function index(ClassModel $class, LiveKitTokenService $tokens): View
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

        // A broken video setup is worth knowing about while scanning the list,
        // not only after opening a class. One warning for the page, drawn from
        // every meeting of this class rather than just the visible page (this
        // list is oldest-first, so an upcoming meeting may be on a later page).
        // The "could still be joined" rule lives in diagnoseForClass.
        $livekitProblem = $tokens->diagnoseForClasses(
            VirtualClass::where('class_id', $class->id)->orderBy('meeting_date')->get()
        );

        return view('student.virtual_classes.index', compact('class', 'enrollment', 'virtualClasses', 'livekitProblem'));
    }

    public function show(ClassModel $class, VirtualClass $virtualClass, LiveKitTokenService $tokens): View
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

        // A broken video setup is shown here, before the Join button, so a
        // student is not left clicking a button that cannot work. The same
        // diagnosis comes back from the classroom itself.
        $livekitProblem = $tokens->diagnoseForClass($virtualClass);

        return view('student.virtual_classes.show', compact('class', 'enrollment', 'virtualClass', 'myAttendance', 'livekitProblem'));
    }

    /**
     * Whether this class can be joined yet, for the details page to poll.
     *
     * A student who opens the page before the instructor starts the class
     * used to sit on a disabled button until a manual reload. The page asks
     * this endpoint instead and swaps the button the moment the room opens.
     */
    public function status(ClassModel $class, VirtualClass $virtualClass): JsonResponse
    {
        $enrollment = Enrollment::where('student_id', auth()->id())
            ->where('class_id', $class->id)
            ->where('status', 'active')
            ->first();

        if (! $enrollment) {
            abort(403);
        }

        if ($virtualClass->class_id !== $class->id) {
            abort(403);
        }

        return response()->json([
            'status' => $virtualClass->status,
            'can_join' => $virtualClass->studentCanJoin(),
            'ended' => $virtualClass->hasEnded(),
            'join_url' => route('student.classes.virtual_classes.join', [$class, $virtualClass]),
        ]);
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
        if ($virtualClass->meeting_provider === VirtualClass::PROVIDER_LIVEKIT) {
            if ($virtualClass->status !== VirtualClass::STATUS_ONGOING || $virtualClass->hasEnded()) {
                return back()->with('error', 'The instructor must start the virtual class before you can join.');
            }

            return redirect()->route('student.classes.virtual_classes.room', [$class, $virtualClass]);
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

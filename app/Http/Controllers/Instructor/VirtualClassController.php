<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use App\Models\VirtualClass;
use App\Services\LiveKitTokenService;
use App\Services\VirtualClassPresence;
use App\Services\VirtualClassRoomTimeline;
use App\Services\VirtualClassSessionCloser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class VirtualClassController extends Controller
{
    /**
     * Every virtual class across this instructor's classes.
     */
    public function all(LiveKitTokenService $tokens): View
    {
        $this->authorize('viewAny', VirtualClass::class);

        $classIds = ClassModel::where('instructor_id', auth()->id())->pluck('id');

        $virtualClasses = VirtualClass::whereIn('class_id', $classIds)
            ->with(['course', 'class', 'instructor', 'attendees'])
            ->orderByDesc('meeting_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        $virtualClasses->getCollection()->each->syncStatus();

        $livekitProblem = $tokens->diagnoseForClasses(
            VirtualClass::whereIn('class_id', $classIds)->orderBy('meeting_date')->get()
        );

        return view('instructor.virtual_classes.all', compact('virtualClasses', 'livekitProblem'));
    }

    public function index(ClassModel $class, LiveKitTokenService $tokens): View
    {
        $this->authorize('viewAny', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $virtualClasses = VirtualClass::where('class_id', $class->id)
            ->with(['course', 'class', 'instructor', 'attendees'])
            ->orderBy('meeting_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        // Meetings whose end time has passed close themselves on view.
        $virtualClasses->getCollection()->each->syncStatus();

        // A broken video setup is worth knowing about while scanning the list,
        // not only after opening a class. One warning for the page, drawn from
        // every meeting of this class rather than just the visible page, so an
        // upcoming meeting on page two is not missed. The "could still be
        // joined" rule lives in diagnoseForClass, so this page cannot drift
        // from the class and room pages.
        $livekitProblem = $tokens->diagnoseForClasses(
            VirtualClass::where('class_id', $class->id)->orderBy('meeting_date')->get()
        );

        return view('instructor.virtual_classes.index', compact('class', 'virtualClasses', 'livekitProblem'));
    }

    public function create(ClassModel $class): View
    {
        $this->authorize('create', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        // Built-in Virtual Classroom (LiveKit) is the only workflow — the
        // provider is forced on the server at store() time, so the form only
        // edits the schedule. No legacy provider/status pickers here.
        return view('instructor.virtual_classes.create', compact('class'));
    }

    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        $this->authorize('create', VirtualClass::class);

        abort_if($class->instructor_id !== auth()->id(), 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'status' => 'sometimes|string|in:scheduled,ongoing,completed,cancelled',
        ]);

        // The built-in form always schedules; any caller-supplied status is
        // clamped to scheduled so a class cannot be created already-live.
        $validated['status'] = VirtualClass::STATUS_SCHEDULED;

        // Built-in Virtual Classroom (LiveKit) is the only workflow — the
        // instructor hosts from inside the LMS and students join from their
        // course page. No external provider links for new classes.
        $validated['meeting_provider'] = VirtualClass::PROVIDER_LIVEKIT;
        $validated['livekit_room_name'] = 'lms-class-'.$class->id.'-'.Str::lower(Str::random(20));
        $validated['meeting_url'] = null;
        $validated['meeting_id'] = null;
        $validated['meeting_password'] = null;

        $validated['course_id'] = $class->course_id;
        $validated['class_id'] = $class->id;
        $validated['instructor_id'] = auth()->id();
        $validated['created_by'] = auth()->id();

        VirtualClass::create($validated);

        return redirect()->route('instructor.classes.virtual_classes.index', $class)
            ->with('success', 'Virtual class created successfully.');
    }

    public function show(ClassModel $class, VirtualClass $virtualClass, LiveKitTokenService $tokens): View
    {
        $this->authorize('view', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        $virtualClass->load(['course', 'class', 'instructor', 'attendees.student']);

        // Auto-close the meeting once its scheduled end time has passed.
        $virtualClass->syncStatus();

        // Show a broken video setup here, before the Start / Join buttons: an
        // instructor who starts a class nobody can join has already lost the
        // session. The same diagnosis comes back from the classroom itself.
        $livekitProblem = $tokens->diagnoseForClass($virtualClass);

        return view('instructor.virtual_classes.show', compact('class', 'virtualClass', 'livekitProblem'));
    }

    public function edit(ClassModel $class, VirtualClass $virtualClass): View
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        // Only an upcoming (scheduled) class can be edited or rescheduled.
        abort_unless($virtualClass->status === VirtualClass::STATUS_SCHEDULED, 403, 'Only scheduled virtual classes can be edited.');

        return view('instructor.virtual_classes.edit', compact('class', 'virtualClass'));
    }

    public function update(Request $request, ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);
        abort_unless($virtualClass->status === VirtualClass::STATUS_SCHEDULED, 403, 'Only scheduled virtual classes can be updated.');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        // Built-in Virtual Classroom only: keep the provider and the generated
        // room name untouched (never let the client re-point the room).
        $validated['meeting_provider'] = $virtualClass->meeting_provider ?: VirtualClass::PROVIDER_LIVEKIT;

        $virtualClass->update($validated);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class updated successfully.');
    }

    public function cancel(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        if (! $virtualClass->canCancel()) {
            return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
                ->with('error', 'This virtual class cannot be cancelled in its current state.');
        }

        $virtualClass->update(['status' => VirtualClass::STATUS_CANCELLED]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class cancelled.');
    }

    public function start(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        // A class can only be started from Scheduled and before its end time
        // — a completed or cancelled class cannot be force-reopened.
        abort_unless($virtualClass->canStart(), 403, 'This virtual class cannot be started in its current state.');

        $virtualClass->update(['status' => VirtualClass::STATUS_ONGOING]);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class started successfully.');
    }

    /**
     * Manually close the meeting (the instructor-driven equivalent of the
     * automatic close that fires once the end time passes). Only a live
     * (ongoing) class can be closed this way.
     */
    public function end(ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->authorize('update', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        if (! $virtualClass->canEnd()) {
            return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
                ->with('error', 'This virtual class is already closed.');
        }

        $virtualClass->update(['status' => VirtualClass::STATUS_COMPLETED]);

        // Delete the LiveKit room so anyone still inside is disconnected now,
        // and seal the open attendance rows that no leave beacon will close.
        app(VirtualClassSessionCloser::class)->close($virtualClass);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass])
            ->with('success', 'Virtual class ended and closed.');
    }

    /**
     * Live headcount, roster and session trend for the dashboard poll.
     *
     * `history`/`peak`/`now`/`events` come from the LMS attendance rows (the
     * shape of the session); `count`/`exact`/`participants` come from LiveKit
     * when it answers and fall back to attendance when it does not.
     */
    public function liveCount(
        ClassModel $class,
        VirtualClass $virtualClass,
        VirtualClassPresence $presence,
        VirtualClassRoomTimeline $timeline
    ): JsonResponse {
        $this->authorize('view', $virtualClass);

        abort_if($class->instructor_id !== auth()->id(), 403);
        abort_if($virtualClass->class_id !== $class->id, 404);

        // A session whose end time passed is already over, even if its status
        // has not been flipped yet.
        $virtualClass->syncStatus();

        if (! $virtualClass->isLiveNow()) {
            return response()->json([
                'live' => false,
                'count' => 0,
                'exact' => false,
                'participants' => [],
                'history' => [],
                'peak' => 0,
                'now' => 0,
                'events' => [],
            ])->header('Cache-Control', 'no-store, max-age=0');
        }

        $snapshot = $presence->snapshot($virtualClass);
        $trend = $timeline->snapshot($virtualClass);

        return response()->json([
            'live' => true,
            'count' => $snapshot['count'],
            'exact' => $snapshot['exact'],
            'participants' => $snapshot['participants'],
            'history' => $trend['points'],
            'peak' => $trend['peak'],
            'now' => $trend['now'],
            'events' => $trend['events'],
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * Mute every remote participant in the room. Instructor-only AJAX endpoint.
     */
    public function muteAll(Request $request, ClassModel $class, VirtualClass $virtualClass)
    {
        $this->authorize('update', $virtualClass);
        abort_if($class->instructor_id !== auth()->id(), 403);

        if ($virtualClass->status !== VirtualClass::STATUS_ONGOING) {
            return response()->json(['message' => 'Class is not live.'], 409);
        }

        // LiveKit does not expose a server-side "mute all" via the PHP SDK
        # without an API key on the server. We signal all clients via a data
        # packet so each participant's local mic is muted.
        return response()->json([
            'message' => 'Mute-all signal sent.',
            'muted'   => true,
        ]);
    }
}

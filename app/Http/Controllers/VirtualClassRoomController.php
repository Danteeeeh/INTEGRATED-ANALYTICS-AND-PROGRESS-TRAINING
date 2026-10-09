<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use App\Services\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class VirtualClassRoomController extends Controller
{
    public function instructorRoom(ClassModel $class, VirtualClass $virtualClass): View
    {
        $user = request()->user();
        $this->assertInstructorAccess($user, $class, $virtualClass);
        $this->assertLiveKitRoomOpen($virtualClass);

        return $this->roomView(
            $class,
            $virtualClass,
            'layouts.instructor',
            'instructor.classes.virtual_classes.token',
            'instructor.classes.virtual_classes.leave',
            'instructor.classes.virtual_classes.show'
        );
    }

    public function studentRoom(ClassModel $class, VirtualClass $virtualClass): View
    {
        $user = request()->user();
        $this->assertStudentAccess($user, $class, $virtualClass);
        $this->assertLiveKitRoomOpen($virtualClass);

        return $this->roomView(
            $class,
            $virtualClass,
            'layouts.student',
            'student.classes.virtual_classes.token',
            'student.classes.virtual_classes.leave',
            'student.classes.virtual_classes.show'
        );
    }

    public function instructorToken(
        Request $request,
        ClassModel $class,
        VirtualClass $virtualClass,
        LiveKitTokenService $tokens
    ): JsonResponse {
        $this->assertInstructorAccess($request->user(), $class, $virtualClass);
        return $this->issueToken($request->user(), $virtualClass, $tokens);
    }

    public function studentToken(
        Request $request,
        ClassModel $class,
        VirtualClass $virtualClass,
        LiveKitTokenService $tokens
    ): JsonResponse {
        $this->assertStudentAccess($request->user(), $class, $virtualClass);
        return $this->issueToken($request->user(), $virtualClass, $tokens);
    }

    public function instructorLeave(Request $request, ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->assertInstructorAccess($request->user(), $class, $virtualClass, false);
        $this->markAttendeeLeft($request->user(), $virtualClass);

        return redirect()->route('instructor.classes.virtual_classes.show', [$class, $virtualClass]);
    }

    public function studentLeave(Request $request, ClassModel $class, VirtualClass $virtualClass): RedirectResponse
    {
        $this->assertStudentAccess($request->user(), $class, $virtualClass, false);
        $this->markAttendeeLeft($request->user(), $virtualClass);

        return redirect()->route('student.classes.virtual_classes.show', [$class, $virtualClass]);
    }

    private function roomView(
        ClassModel $class,
        VirtualClass $virtualClass,
        string $layout,
        string $tokenRoute,
        string $leaveRoute,
        string $backRoute
    ): View {
        $user = request()->user();

        return view('virtual_classes.room', [
            'class' => $class,
            'virtualClass' => $virtualClass,
            'layout' => $layout,
            'tokenUrl' => route($tokenRoute, [$class, $virtualClass]),
            'leaveUrl' => route($leaveRoute, [$class, $virtualClass]),
            'backUrl' => route($backRoute, [$class, $virtualClass]),
            'displayName' => $user->full_name ?: $user->name,
            'isInstructor' => $user->isInstructor(),
        ]);
    }

    private function issueToken(User $user, VirtualClass $virtualClass, LiveKitTokenService $tokens): JsonResponse
    {
        $this->assertLiveKitRoomOpen($virtualClass);

        $serverUrl = (string) config('services.livekit.url');
        if ($serverUrl === '') {
            return response()->json([
                'message' => 'LiveKit is not configured. Ask the system administrator to configure LIVEKIT_URL, LIVEKIT_API_KEY, and LIVEKIT_API_SECRET.',
            ], 503);
        }

        try {
            $token = $tokens->createJoinToken($user, $virtualClass);
        } catch (RuntimeException $exception) {
            report($exception);
            return response()->json(['message' => 'The virtual classroom is not configured yet.'], 503);
        }

        if ($user->isStudent()) {
            $this->markAttendeeJoined($user, $virtualClass);
        }

        return response()->json([
            'url' => $serverUrl,
            'token' => $token,
            'identity' => 'user-'.$user->getKey(),
            'name' => $user->full_name ?: $user->name,
        ]);
    }

    private function assertInstructorAccess(
        User $user,
        ClassModel $class,
        VirtualClass $virtualClass,
        bool $requireLive = true
    ): void {
        abort_unless($user->isInstructor(), 403);
        abort_unless((int) $class->instructor_id === (int) $user->id, 403);
        abort_unless((int) $virtualClass->class_id === (int) $class->id, 404);
        abort_unless((int) $virtualClass->instructor_id === (int) $user->id, 403);
        abort_unless($virtualClass->meeting_provider === VirtualClass::PROVIDER_LIVEKIT, 404);

        if ($requireLive) {
            $this->assertLiveKitRoomOpen($virtualClass);
        }
    }

    private function assertStudentAccess(
        User $user,
        ClassModel $class,
        VirtualClass $virtualClass,
        bool $requireLive = true
    ): void {
        abort_unless($user->isStudent(), 403);
        abort_unless((int) $virtualClass->class_id === (int) $class->id, 404);
        abort_unless(
            Enrollment::query()
                ->where('student_id', $user->id)
                ->where('class_id', $class->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->exists(),
            403
        );
        abort_unless($virtualClass->meeting_provider === VirtualClass::PROVIDER_LIVEKIT, 404);

        if ($requireLive) {
            $this->assertLiveKitRoomOpen($virtualClass);
        }
    }

    private function assertLiveKitRoomOpen(VirtualClass $virtualClass): void
    {
        $virtualClass->syncStatus();
        abort_unless($virtualClass->livekit_room_name, 503, 'This class does not have a LiveKit room configured.');
        abort_unless($virtualClass->status === VirtualClass::STATUS_ONGOING, 403, 'The instructor has not started this class or the class has ended.');
        abort_if($virtualClass->hasEnded(), 403, 'This virtual class has ended.');
    }

    private function markAttendeeJoined(User $user, VirtualClass $virtualClass): void
    {
        $attendee = VirtualClassAttendee::firstOrNew([
            'virtual_class_id' => $virtualClass->id,
            'user_id' => $user->id,
        ]);

        if (! $attendee->exists || ! $attendee->joined_at || $attendee->left_at) {
            $attendee->joined_at = now();
            $attendee->left_at = null;
        }

        $attendee->attendance_status = VirtualClassAttendee::STATUS_PRESENT;
        $attendee->save();
    }

    private function markAttendeeLeft(User $user, VirtualClass $virtualClass): void
    {
        if (! $user->isStudent()) {
            return;
        }

        $attendee = VirtualClassAttendee::query()
            ->where('virtual_class_id', $virtualClass->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $attendee || ! $attendee->joined_at || $attendee->left_at) {
            return;
        }

        $elapsed = max(0, (int) $attendee->joined_at->diffInMinutes(now()));
        $attendee->duration_minutes = (int) $attendee->duration_minutes + $elapsed;
        $attendee->left_at = now();
        $attendee->save();
    }
}

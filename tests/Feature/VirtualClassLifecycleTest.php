<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\VirtualClass;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Instructor Edit / Reschedule / Cancel + start/end guards + token throttle.
 */
class VirtualClassLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $enrolledStudent;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);
        $this->otherInstructor = User::factory()->instructor()->create(['status' => 'active']);

        $course = Course::factory()->create(['status' => 'published']);
        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        $this->enrolledStudent = User::factory()->student()->create(['status' => 'active']);
        Enrollment::create([
            'student_id' => $this->enrolledStudent->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    protected function makeRoom(string $status = VirtualClass::STATUS_SCHEDULED): VirtualClass
    {
        return VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Lifecycle Session',
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-class-'.$this->class->id.'-'.Str::lower(Str::random(20)),
            'status' => $status,
            'created_by' => $this->instructor->id,
        ]);
    }

    protected function configureLiveKit(): void
    {
        config([
            'services.livekit.url' => 'wss://test-project.livekit.cloud',
            'services.livekit.api_key' => 'APItestkey123',
            'services.livekit.api_secret' => 'supersecretvalue-xyz',
        ]);
    }

    public function test_instructor_can_edit_and_reschedule_own_scheduled_class(): void
    {
        $room = $this->makeRoom();
        $originalRoomName = $room->livekit_room_name;
        $newDate = now()->addDays(3)->toDateString();

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.edit', [$this->class, $room]))
            ->assertOk();

        $this->actingAs($this->instructor)
            ->put(route('instructor.classes.virtual_classes.update', [$this->class, $room]), [
                'title' => 'Rescheduled Session',
                'description' => 'Moved to next week',
                'meeting_date' => $newDate,
                'start_time' => '14:00',
                'end_time' => '15:30',
                'meeting_provider' => 'zoom',
            ])
            ->assertRedirect(route('instructor.classes.virtual_classes.show', [$this->class, $room]));

        $fresh = $room->fresh();
        $this->assertSame('Rescheduled Session', $fresh->title);
        $this->assertSame('Moved to next week', $fresh->description);
        $this->assertSame($newDate, $fresh->meeting_date->toDateString());
        $this->assertSame(VirtualClass::PROVIDER_LIVEKIT, $fresh->meeting_provider);
        $this->assertSame($originalRoomName, $fresh->livekit_room_name);
    }

    public function test_instructor_can_cancel_own_scheduled_class(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.virtual_classes.cancel', [$this->class, $room]))
            ->assertRedirect(route('instructor.classes.virtual_classes.show', [$this->class, $room]));

        $this->assertSame(VirtualClass::STATUS_CANCELLED, $room->fresh()->status);
    }

    public function test_other_instructor_cannot_edit_or_cancel_class(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.classes.virtual_classes.edit', [$this->class, $room]))
            ->assertForbidden();

        $this->actingAs($this->otherInstructor)
            ->put(route('instructor.classes.virtual_classes.update', [$this->class, $room]), [
                'title' => 'Hijacked',
                'meeting_date' => now()->addDays(5)->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
            ])
            ->assertForbidden();

        $this->actingAs($this->otherInstructor)
            ->post(route('instructor.classes.virtual_classes.cancel', [$this->class, $room]))
            ->assertForbidden();

        $this->assertSame(VirtualClass::STATUS_SCHEDULED, $room->fresh()->status);
        $this->assertSame('Lifecycle Session', $room->fresh()->title);
    }

    public function test_instructor_cannot_start_a_completed_class(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_COMPLETED);

        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.virtual_classes.start', [$this->class, $room]))
            ->assertForbidden();

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $room->fresh()->status);
    }

    public function test_instructor_cannot_end_a_scheduled_class(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->instructor)
            ->post(route('instructor.classes.virtual_classes.end', [$this->class, $room]))
            ->assertRedirect(route('instructor.classes.virtual_classes.show', [$this->class, $room]));

        $this->assertSame(VirtualClass::STATUS_SCHEDULED, $room->fresh()->status);
    }

    public function test_token_endpoint_is_throttled(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->enrolledStudent)
                ->postJson(route('student.classes.virtual_classes.token', [$this->class, $room]))
                ->assertOk();
        }

        $this->actingAs($this->enrolledStudent)
            ->postJson(route('student.classes.virtual_classes.token', [$this->class, $room]))
            ->assertStatus(429);
    }
}

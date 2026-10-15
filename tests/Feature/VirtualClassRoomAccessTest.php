<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * LiveKit Virtual Classroom — access control and token generation.
 *
 * Covers the security-critical parts of the built-in virtual classroom that
 * can be verified without a live LiveKit server: room access is limited to
 * the owning instructor and actively enrolled students, the room only opens
 * once the instructor starts the class, and server-issued JWTs carry the
 * right claims without ever exposing the API secret to the browser.
 */
class VirtualClassRoomAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $otherInstructor;

    protected User $enrolledStudent;

    protected User $outsiderStudent;

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

        $this->outsiderStudent = User::factory()->student()->create(['status' => 'active']);
    }

    protected function makeRoom(string $status = VirtualClass::STATUS_ONGOING): VirtualClass
    {
        return VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'LiveKit Session',
            'meeting_date' => now()->toDateString(),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
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

    public function test_instructor_can_open_the_room_once_started(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.room', [$this->class, $room]));

        $response->assertOk();
        $response->assertSee('virtualClassroom');
    }

    public function test_student_with_active_enrollment_can_open_the_room(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertOk();
    }

    public function test_student_without_enrollment_cannot_open_the_room(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->outsiderStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertForbidden();
    }

    public function test_other_instructor_cannot_open_or_token_the_room(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->otherInstructor)
            ->get(route('instructor.classes.virtual_classes.room', [$this->class, $room]))
            ->assertForbidden();

        $this->actingAs($this->otherInstructor)
            ->postJson(route('instructor.classes.virtual_classes.token', [$this->class, $room]))
            ->assertForbidden();
    }

    public function test_room_is_closed_before_the_instructor_starts_it(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        // Student gets a redirect+error from the join button…
        $this->actingAs($this->enrolledStudent)
            ->post(route('student.classes.virtual_classes.join', [$this->class, $room]))
            ->assertRedirect();

        // …and the room itself refuses to open.
        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertForbidden();
    }

    public function test_non_livekit_classes_have_no_room(): void
    {
        $zoom = VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Zoom Session',
            'meeting_date' => now()->toDateString(),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'meeting_provider' => VirtualClass::PROVIDER_ZOOM,
            'meeting_url' => 'https://zoom.us/j/123456789',
            'status' => VirtualClass::STATUS_ONGOING,
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.room', [$this->class, $zoom]))
            ->assertNotFound();
    }

    public function test_token_endpoint_returns_a_short_lived_livekit_jwt(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom();

        $response = $this->actingAs($this->enrolledStudent)
            ->postJson(route('student.classes.virtual_classes.token', [$this->class, $room]));

        $response->assertOk();
        $data = $response->json();

        $this->assertSame('wss://test-project.livekit.cloud', $data['url']);
        $this->assertSame('user-'.$this->enrolledStudent->id, $data['identity']);

        // Decode the JWT payload and check the claims (no secret leakage).
        $segments = explode('.', $data['token']);
        $this->assertCount(3, $segments);
        $payload = json_decode(base64_decode(strtr($segments[1], '-_', '+/')), true);

        $this->assertSame('APItestkey123', $payload['iss']);
        $this->assertSame('user-'.$this->enrolledStudent->id, $payload['sub']);
        $this->assertSame($room->livekit_room_name, $payload['video']['room']);
        $this->assertTrue($payload['video']['roomJoin']);
        $this->assertTrue($payload['video']['canPublish']);
        $this->assertGreaterThanOrEqual(1700, $payload['exp'] - $payload['iat']);
        $this->assertLessThanOrEqual(1800, $payload['exp'] - $payload['iat']);
    }

    public function test_room_page_never_exposes_the_api_secret(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom();

        $response = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]));

        $response->assertOk();
        $response->assertDontSee('supersecretvalue-xyz');
    }

    public function test_token_endpoint_fails_cleanly_when_livekit_is_not_configured(): void
    {
        config(['services.livekit.url' => null]);

        $room = $this->makeRoom();

        $this->actingAs($this->enrolledStudent)
            ->postJson(route('student.classes.virtual_classes.token', [$this->class, $room]))
            ->assertStatus(503);
    }

    public function test_creating_a_virtual_class_persists_the_livekit_room_name(): void
    {
        $name = 'lms-class-'.$this->class->id.'-'.Str::lower(Str::random(20));

        $room = VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Persisted Room',
            'meeting_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => $name,
            'status' => VirtualClass::STATUS_SCHEDULED,
            'created_by' => $this->instructor->id,
        ]);

        $this->assertDatabaseHas('virtual_classes', [
            'id' => $room->id,
            'livekit_room_name' => $name,
        ]);
        $this->assertSame($name, $room->fresh()->livekit_room_name);
        $this->assertArrayNotHasKey('livekit_room_name', $room->fresh()->toArray());
    }

    public function test_random_room_names_are_unique(): void
    {
        $first = $this->makeRoom();
        $second = $this->makeRoom();

        $this->assertNotSame($first->livekit_room_name, $second->livekit_room_name);
        $this->assertNotNull($first->fresh()->livekit_room_name);
        $this->assertNotNull($second->fresh()->livekit_room_name);
    }

    public function test_student_leave_records_attendance_duration(): void
    {
        $this->configureLiveKit();

        $room = $this->makeRoom();

        // Joining issues the token, which marks the attendee present.
        $this->actingAs($this->enrolledStudent)
            ->postJson(route('student.classes.virtual_classes.token', [$this->class, $room]))
            ->assertOk();

        $this->assertDatabaseHas('virtual_class_attendees', [
            'virtual_class_id' => $room->id,
            'user_id' => $this->enrolledStudent->id,
            'attendance_status' => 'present',
        ]);

        $this->actingAs($this->enrolledStudent)
            ->post(route('student.classes.virtual_classes.leave', [$this->class, $room]))
            ->assertRedirect();

        $this->assertDatabaseMissing('virtual_class_attendees', [
            'virtual_class_id' => $room->id,
            'user_id' => $this->enrolledStudent->id,
            'left_at' => null,
        ]);
    }
}

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
 * Student-facing join flow for virtual classes.
 *
 * LiveKit sessions never have a meeting_url, so the join buttons must be
 * driven by the class status instead. These tests lock in that behaviour and
 * the fact that the classroom page actually ships its module script (a stray
 * `</script type="module">` used to leave the whole room inert).
 */
class VirtualClassJoinFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);

        $course = Course::factory()->create(['status' => 'published']);
        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->student()->create(['status' => 'active']);
        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    protected function makeRoom(string $status = VirtualClass::STATUS_ONGOING): VirtualClass
    {
        return VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Join Flow Session',
            'meeting_date' => now()->toDateString(),
            'start_time' => now()->subMinutes(10)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-class-'.$this->class->id.'-'.Str::lower(Str::random(20)),
            'status' => $status,
            'created_by' => $this->instructor->id,
        ]);
    }

    public function test_livekit_provider_has_a_human_label(): void
    {
        $this->assertSame('Virtual Classroom', $this->makeRoom()->providerLabel());
    }

    public function test_student_index_offers_a_join_button_for_a_live_livekit_class(): void
    {
        $room = $this->makeRoom();

        $this->assertTrue($room->canStudentJoin());

        $response = $this->actingAs($this->student)
            ->get(route('student.classes.virtual_classes.index', $this->class));

        $response->assertOk();
        $response->assertSee(route('student.classes.virtual_classes.join', [$this->class, $room]), false);
        $response->assertSee('Join now', false);
        // The provider column used to fall through to "N/A" for LiveKit.
        $response->assertSee('Virtual Classroom');
        $response->assertDontSee('N/A');
    }

    public function test_student_index_waits_for_the_instructor_before_a_livekit_class_starts(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->assertFalse($room->canStudentJoin());
        $this->assertSame('Waiting for the instructor to start this class.', $room->joinUnavailableReason());

        $response = $this->actingAs($this->student)
            ->get(route('student.classes.virtual_classes.index', $this->class));

        $response->assertOk();
        $response->assertDontSee(route('student.classes.virtual_classes.join', [$this->class, $room]), false);
        $response->assertSee('Waiting for the instructor to start this class.');
    }

    public function test_join_post_redirects_a_student_into_the_livekit_room(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->student)
            ->post(route('student.classes.virtual_classes.join', [$this->class, $room]))
            ->assertRedirect(route('student.classes.virtual_classes.room', [$this->class, $room]));
    }

    public function test_join_post_explains_that_a_scheduled_class_has_not_started(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->student)
            ->from(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->post(route('student.classes.virtual_classes.join', [$this->class, $room]))
            ->assertRedirect(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertSessionHas('error', 'Waiting for the instructor to start this class.');
    }

    public function test_student_detail_page_shows_join_classroom_only_when_live(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->student)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('Join Classroom')
            ->assertSee(route('student.classes.virtual_classes.join', [$this->class, $room]), false);
    }

    public function test_room_page_ships_a_real_module_script(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->student)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]));

        $response->assertOk();

        // The room now loads its client from the Vite bundle, which works
        // under a strict CSP and when the CDN is blocked.
        $response->assertSee('<script type="module"', false);
        $response->assertSee('/build/assets/virtual-classroom', false);
        $response->assertDontSee('cdn.jsdelivr.net', false);
        // Neither the room styles nor the LiveKit import are inline any more:
        // the CSS rule and the bare import live in the bundle only.
        $response->assertDontSee('.vc-room{', false);
        $response->assertDontSee('import { Room', false);

        // The chat send button id is what the module binds; a mismatch used to
        // throw on the Joined event and leave the room half-initialised.
        $response->assertSee('id="sendChatButton"', false);
        // Room configuration is passed through data attributes, not @json().
        $response->assertSee('data-token-url="', false);
        $response->assertSee('data-instructor="0"', false);

        // Instructor-only controls must not be rendered for a student.
        $response->assertDontSee('id="muteAllButton"', false);
        $response->assertDontSee('id="endMeetingButton"', false);
    }

    public function test_instructor_room_is_flagged_and_still_cdn_free(): void
    {
        $room = $this->makeRoom();

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.room', [$this->class, $room]));

        $response->assertOk();
        $response->assertSee('data-instructor="1"', false);
        $response->assertSee('data-end-url="', false);
        $response->assertSee('data-mute-all-url="', false);
        $response->assertDontSee('cdn.jsdelivr.net', false);
        $response->assertDontSee('import { Room', false);
    }
}

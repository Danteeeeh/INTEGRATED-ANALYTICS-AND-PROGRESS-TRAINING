<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\GradeSubmission;
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

    public function test_room_page_opens_a_real_module_script(): void
    {
        $room = $this->makeRoom();

        $html = $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.room', [$this->class, $room]))
            ->assertOk()
            ->getContent();

        // The room script used to start with a stray closing tag, so the whole
        // block rendered as text and the Join button never fetched a token —
        // nobody could enter the room from any role.
        $this->assertStringContainsString('<script type="module">', $html);
        $this->assertStringNotContainsString(
            '</script type="module">',
            $html,
            'A stray closing tag means the room script block was never opened.'
        );
        $this->assertStringContainsString(
            "from 'https://cdn.jsdelivr.net/npm/livekit-client@2.15.6/+esm'",
            $html,
            'The LiveKit client import must sit inside the module script.'
        );

        // The Send button id is sendChatButton; wiring it as chatSend threw on
        // connect and skipped the rest of the Connected handler.
        $this->assertStringContainsString("chatSend:\$('sendChatButton')", $html);
        $this->assertStringNotContainsString("chatSend:\$('chatSend')", $html);
    }

    public function test_student_room_only_wires_the_controls_it_has(): void
    {
        $room = $this->makeRoom();

        $html = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="endMeetingButton"', $html);

        // Students have no mute-all, end-meeting or info buttons. Wiring them
        // without a guard killed the rest of the module on the student page.
        $this->assertStringContainsString('el.muteAll?.addEventListener', $html);
        $this->assertStringContainsString('el.endMeetingPop?.addEventListener', $html);
        $this->assertStringContainsString('el.endMeeting?.addEventListener', $html);
        $this->assertStringContainsString('el.info?.addEventListener', $html);
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

    public function test_room_page_warns_about_the_livekit_setup_before_anyone_joins(): void
    {
        // Nothing configured: the token endpoint would answer 503. The room page
        // has to say so up front instead of leaving a button that always fails.
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        $room = $this->makeRoom();

        foreach ([
            'instructor.classes.virtual_classes.room' => $this->instructor,
            'student.classes.virtual_classes.room' => $this->enrolledStudent,
        ] as $routeName => $user) {
            $response = $this->actingAs($user)
                ->get(route($routeName, [$this->class, $room]))
                ->assertOk();

            $response->assertSee('livekitConfigBanner', false);
            $response->assertSee('LIVEKIT_URL');
            $response->assertSee('LIVEKIT_API_KEY');
            $response->assertSee('LIVEKIT_API_SECRET');
            $response->assertSee('joining is disabled', false);

            // The button cannot work while the setup is incomplete.
            $this->assertStringContainsString(
                'id="joinButton" disabled',
                $response->getContent(),
                'The Join button must be disabled when LiveKit is not configured.'
            );

            // …and the status line must not invite a click that cannot happen.
            $response->assertSee('has to be configured before this room can be joined');
        }
    }

    public function test_room_page_stays_quiet_when_livekit_is_configured(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom();

        $response = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertOk();

        $response->assertDontSee('livekitConfigBanner', false);
        $response->assertDontSee('Joining is disabled');
        $response->assertSee('Click "Join classroom" to enter.');

        $this->assertStringNotContainsString(
            'id="joinButton" disabled',
            $response->getContent(),
            'A configured room must keep the Join button enabled.'
        );
    }

    public function test_class_details_pages_warn_about_the_livekit_setup_before_the_room(): void
    {
        // The warning has to reach the pages that lead to the classroom, not
        // only the room itself — otherwise the first sign of trouble is a Join
        // button that cannot work.
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        $room = $this->makeRoom();

        $instructorPage = $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk();

        $instructorPage->assertSee('livekitConfigWarning', false);
        $instructorPage->assertSee('joining is disabled', false);
        $instructorPage->assertSee('LIVEKIT_URL');

        // These pages do not carry the classroom's dark-room styling, so the
        // shared partial brings its own rather than emitting a bare div.
        $instructorPage->assertSee('class="livekit-config-warning"', false);
        $instructorPage->assertSee('.livekit-config-warning{', false);

        $studentPage = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk();

        $studentPage->assertSee('livekitConfigWarning', false);
        $studentPage->assertSee('LIVEKIT_API_SECRET');

        // It sits above the control it is about, not below it: a warning read
        // after the failed click has already wasted the click.
        $this->assertLessThan(
            strpos($instructorPage->getContent(), 'Join Virtual Classroom'),
            strpos($instructorPage->getContent(), 'livekitConfigWarning'),
            "The instructor warning must sit above the Join button."
        );
        $this->assertLessThan(
            strpos($studentPage->getContent(), 'Join Meeting'),
            strpos($studentPage->getContent(), 'livekitConfigWarning'),
            "The student warning must sit above the Join button."
        );
    }

    public function test_class_details_pages_warn_while_the_class_is_still_scheduled(): void
    {
        // An upcoming class is exactly when the setup can still be fixed, so
        // the warning belongs there too — not only on a live one.
        config([
            'services.livekit.url' => 'http://livekit.example.test',
            'services.livekit.api_key' => 'APItestkey123',
            'services.livekit.api_secret' => 'supersecretvalue-xyz',
        ]);

        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('livekitConfigWarning', false)
            ->assertSee('must be the WebSocket URL');
    }

    public function test_class_details_pages_stay_quiet_when_livekit_is_configured(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom();

        foreach ([
            'instructor.classes.virtual_classes.show' => $this->instructor,
            'student.classes.virtual_classes.show' => $this->enrolledStudent,
        ] as $routeName => $user) {
            $this->actingAs($user)
                ->get(route($routeName, [$this->class, $room]))
                ->assertOk()
                ->assertDontSee('livekitConfigWarning', false);
        }
    }

    public function test_class_details_pages_do_not_warn_once_the_class_is_closed(): void
    {
        // A closed or cancelled class cannot be joined at all, so the warning
        // there would be noise rather than help.
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        foreach ([VirtualClass::STATUS_COMPLETED, VirtualClass::STATUS_CANCELLED] as $status) {
            $room = $this->makeRoom($status);

            $this->actingAs($this->instructor)
                ->get(route('instructor.classes.virtual_classes.show', [$this->class, $room]))
                ->assertOk()
                ->assertDontSee('livekitConfigWarning', false);
        }
    }

    public function test_class_list_pages_warn_when_a_class_could_still_be_joined(): void
    {
        // Scanning upcoming classes should not end in a click that cannot work,
        // so the list pages carry the warning too.
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $instructorList = $this->actingAs($this->instructor)
            ->get(route('instructor.classes.virtual_classes.index', $this->class))
            ->assertOk();

        $instructorList->assertSee('livekitConfigWarning', false);
        $instructorList->assertSee('joining is disabled', false);
        $instructorList->assertSee('LIVEKIT_URL');

        $studentList = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.index', $this->class))
            ->assertOk();

        $studentList->assertSee('livekitConfigWarning', false);
        $studentList->assertSee('LIVEKIT_API_KEY');
    }

    public function test_class_list_pages_stay_quiet_when_nothing_can_be_joined(): void
    {
        // Only finished classes on offer: the warning there would be noise, just
        // as it is on those classes' own detail pages.
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

        $this->makeRoom(VirtualClass::STATUS_COMPLETED);
        $this->makeRoom(VirtualClass::STATUS_CANCELLED);

        foreach ([
            'instructor.classes.virtual_classes.index' => $this->instructor,
            'student.classes.virtual_classes.index' => $this->enrolledStudent,
        ] as $routeName => $user) {
            $this->actingAs($user)
                ->get(route($routeName, $this->class))
                ->assertOk()
                ->assertDontSee('livekitConfigWarning', false);
        }
    }

    public function test_class_list_pages_stay_quiet_when_livekit_is_configured(): void
    {
        $this->configureLiveKit();
        $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        foreach ([
            'instructor.classes.virtual_classes.index' => $this->instructor,
            'student.classes.virtual_classes.index' => $this->enrolledStudent,
        ] as $routeName => $user) {
            $this->actingAs($user)
                ->get(route($routeName, $this->class))
                ->assertOk()
                ->assertDontSee('livekitConfigWarning', false);
        }
    }

    public function test_student_list_offers_join_for_a_started_livekit_class(): void
    {
        // A built-in classroom carries no meeting_url at all, so this row used
        // to show only the eye icon even while the class was live.
        $room = $this->makeRoom();

        $this->assertNull($room->meeting_url);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.index', $this->class))
            ->assertOk()
            ->assertSee(route('student.classes.virtual_classes.join', [$this->class, $room]));
    }

    public function test_student_list_hides_join_before_the_instructor_starts_a_livekit_class(): void
    {
        // The join route refuses a LiveKit class the instructor has not started
        // yet, so the row must not offer a button that can only bounce back.
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.index', $this->class))
            ->assertOk()
            ->assertDontSee(route('student.classes.virtual_classes.join', [$this->class, $room]));
    }

    public function test_student_list_still_offers_join_for_an_external_meeting_link(): void
    {
        $zoom = VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Zoom Session',
            'meeting_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'meeting_provider' => VirtualClass::PROVIDER_ZOOM,
            'meeting_url' => 'https://zoom.us/j/123456789',
            'status' => VirtualClass::STATUS_SCHEDULED,
            'created_by' => $this->instructor->id,
        ]);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.index', $this->class))
            ->assertOk()
            ->assertSee(route('student.classes.virtual_classes.join', [$this->class, $zoom]));
    }

    public function test_student_details_page_offers_join_for_a_started_livekit_class(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('Join Meeting')
            ->assertSee(route('student.classes.virtual_classes.join', [$this->class, $room]));
    }

    public function test_student_details_page_waits_instead_of_offering_join_before_the_instructor_starts(): void
    {
        // The start time has arrived, but the instructor has not opened the
        // room. The join route refuses that click, so the details page must
        // show the wait rather than a button that can only bounce back.
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('Waiting for Instructor')
            ->assertDontSee(route('student.classes.virtual_classes.join', [$this->class, $room]));
    }

    public function test_student_details_page_still_says_not_started_yet_before_the_scheduled_time(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);
        $room->forceFill([
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ])->save();

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('Not Started Yet')
            ->assertDontSee(route('student.classes.virtual_classes.join', [$this->class, $room]));
    }

    public function test_class_state_endpoint_reports_the_room_opening(): void
    {
        // The details page polls this while it waits, so it has to follow the
        // same rule the Join button does — not just echo the status column.
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->enrolledStudent)
            ->getJson(route('student.classes.virtual_classes.status', [$this->class, $room]))
            ->assertOk()
            ->assertJson([
                'status' => VirtualClass::STATUS_SCHEDULED,
                'can_join' => false,
                'ended' => false,
            ]);

        $room->update(['status' => VirtualClass::STATUS_ONGOING]);

        $response = $this->actingAs($this->enrolledStudent)
            ->getJson(route('student.classes.virtual_classes.status', [$this->class, $room]))
            ->assertOk()
            ->assertJson([
                'status' => VirtualClass::STATUS_ONGOING,
                'can_join' => true,
            ]);

        $this->assertSame(
            route('student.classes.virtual_classes.join', [$this->class, $room]),
            $response->json('join_url')
        );
    }

    public function test_class_state_endpoint_is_closed_to_a_student_without_enrollment(): void
    {
        $room = $this->makeRoom();

        $this->actingAs($this->outsiderStudent)
            ->getJson(route('student.classes.virtual_classes.status', [$this->class, $room]))
            ->assertForbidden();
    }

    public function test_waiting_details_page_carries_the_state_poll_hook(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $page = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk();

        $page->assertSee('id="joinWatch"', false);
        $page->assertSee(route('student.classes.virtual_classes.status', [$this->class, $room]));

        // Once the room is open the wait button is gone (the form takes its
        // place), so nothing keeps polling a class that can already be joined.
        $room->update(['status' => VirtualClass::STATUS_ONGOING]);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertDontSee('id="joinWatch"', false);
    }

    public function test_details_page_renders_the_live_state_that_the_poll_switches_to(): void
    {
        // The poll rewrites the badge and the date row in place, so both the
        // server-rendered live state and the hooks it targets must exist.
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('id="classStatusBadge" data-status="scheduled"', false)
            ->assertSee('id="dateLiveChip" style="display: none;', false);

        $room->update(['status' => VirtualClass::STATUS_ONGOING]);

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk()
            ->assertSee('id="classStatusBadge" data-status="ongoing"', false)
            ->assertSee('id="dateLiveChip" style="display: inline-flex;', false)
            ->assertSee('Live now');
    }

    public function test_details_page_shows_the_schedule_on_the_status_card(): void
    {
        // "Live Now" alone says nothing about when the session runs; the date
        // and time belong on the same card, above the button.
        $room = $this->makeRoom();

        $response = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $room]))
            ->assertOk();

        $response->assertSee('id="classScheduleLine"', false);
        $response->assertSee($room->meeting_date->format('l, M d, Y'));
        $response->assertSee(\Carbon\Carbon::parse($room->start_time)->format('g:i A'));
        $response->assertSee(\Carbon\Carbon::parse($room->end_time)->format('g:i A'));

        // The Meeting Details row uses the same 12-hour format, not the raw
        // "HH:MM:SS" database columns.
        $response->assertDontSee($room->start_time);
        $response->assertDontSee($room->end_time);

        $this->assertLessThan(
            strpos($response->getContent(), 'Join Meeting'),
            strpos($response->getContent(), 'classScheduleLine'),
            'The schedule must sit on the status card, above the Join button.'
        );
    }

    public function test_overnight_duration_label_persists_and_renders_on_student_and_admin_details(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);
        $room->forceFill([
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '23:30:00',
            'end_time' => '00:30:00',
        ])->save();

        // An end time earlier than its start is an overnight session, not a
        // negative or 23-hour duration. The formatter owns that rule once for
        // each consumer, including records loaded fresh from the database.
        $persisted = $room->fresh();
        $this->assertSame('1 hr 00 min', $persisted->durationLabel());

        $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.show', [$this->class, $persisted]))
            ->assertOk()
            ->assertSee('Duration:')
            ->assertSee('1 hr 00 min');

        $admin = User::factory()->admin()->create(['status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.virtual_classes.show', $persisted))
            ->assertOk()
            ->assertSee('Duration:')
            ->assertSee('1 hr 00 min');
    }

    public function test_duration_label_treats_midnight_and_evening_boundaries_as_forward_sessions(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED);

        $room->forceFill([
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '00:00:00',
            'end_time' => '00:30:00',
        ])->save();

        $this->assertSame('0 hr 30 min', $room->fresh()->durationLabel());

        // 11:59 PM is the single-digit evening boundary that has historically
        // exposed string-based time parsing mistakes.
        $room->update([
            'start_time' => '23:59:00',
            'end_time' => '00:00:00',
        ]);

        $this->assertSame('0 hr 01 min', $room->fresh()->durationLabel());
    }

    public function test_student_room_page_uses_the_same_12_hour_time_format(): void
    {
        $room = $this->makeRoom();
        $room->forceFill([
            // Keep this display fixture ahead of now so the room authorization
            // correctly sees an ongoing session rather than an already-ended
            // early-morning class from today.
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '23:59:00',
            'end_time' => '00:30:00',
        ])->save();

        $response = $this->actingAs($this->enrolledStudent)
            ->get(route('student.classes.virtual_classes.room', [$this->class, $room]))
            ->assertOk();

        $response->assertSee('11:59 PM');
        $response->assertSee('12:30 AM');
        $response->assertDontSee('23:59:00');
        $response->assertDontSee('00:30:00');
    }

    public function test_aa_staff_grade_submissions_use_the_same_12_hour_time_format(): void
    {
        GradeSubmission::create([
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'submitted_by' => $this->instructor->id,
            'status' => GradeSubmission::STATUS_SUBMITTED,
            'student_count' => 1,
            'graded_count' => 1,
            'missing_count' => 0,
            'submitted_at' => now()->setTime(23, 59),
        ]);

        $aaStaff = User::factory()->aaStaff()->create(['status' => 'active']);

        $response = $this->actingAs($aaStaff)
            ->get(route('aa.grade-submissions.index'))
            ->assertOk();

        $response->assertSee('11:59 PM');
        $response->assertDontSee(now()->setTime(23, 59)->format('H:i:s'));
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

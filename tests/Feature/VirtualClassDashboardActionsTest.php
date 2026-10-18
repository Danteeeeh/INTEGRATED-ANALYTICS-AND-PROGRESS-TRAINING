<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Live Join/Start shortcuts on the student and instructor dashboards.
 *
 * The dashboard queries used to drop every session that had already started,
 * which is exactly the row a user can join. These tests pin down both the
 * query window and the rendered actions.
 */
class VirtualClassDashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze the clock mid-morning so "today" and the time windows are
        // deterministic regardless of when the suite runs.
        $this->travelTo(Carbon::parse('2026-02-10 10:00:00'));

        // Tests must never reach the real LiveKit Cloud even when the local
        // .env carries live credentials; the exact-count tests opt back in
        // with fake values plus Http::fake().
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

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

    protected function configureLiveKit(): void
    {
        config([
            'services.livekit.url' => 'wss://test-project.livekit.cloud',
            'services.livekit.api_key' => 'APItestkey123',
            'services.livekit.api_secret' => 'supersecretvalue-xyz',
        ]);
    }

    protected function makeRoom(
        string $status,
        string $date = '2026-02-10',
        string $start = '09:50:00',
        string $end = '11:00:00'
    ): VirtualClass {
        return VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Dashboard '.$status.' session',
            'meeting_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-class-'.$this->class->id.'-'.Str::lower(Str::random(20)),
            'status' => $status,
            'created_by' => $this->instructor->id,
        ]);
    }

    public function test_student_dashboard_lists_the_live_class_with_a_join_action(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertSee('Live now');
        $response->assertSee(route('student.classes.virtual_classes.join', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
        $response->assertSee('dash-join-btn', false);
    }

    public function test_student_dashboard_has_no_join_action_before_the_class_starts(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertDontSee('Live now');
        $response->assertDontSee(route('student.classes.virtual_classes.join', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
    }

    public function test_student_dashboard_counts_upcoming_virtual_classes(): void
    {
        $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertOk();
        $this->assertSame(1, $response->viewData('stats')['total_virtual_classes']);
    }

    public function test_student_dashboard_hides_a_session_once_its_end_time_passed(): void
    {
        $this->makeRoom(VirtualClass::STATUS_ONGOING, '2026-02-10', '08:00:00', '09:00:00');

        $response = $this->actingAs($this->student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertDontSee('Dashboard ongoing session');
    }

    public function test_instructor_dashboard_offers_join_for_a_live_class(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertSee('Live now');
        $response->assertSee(route('instructor.classes.virtual_classes.room', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
        // The row itself links to the class detail page.
        $response->assertSee(route('instructor.classes.virtual_classes.show', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
    }

    public function test_instructor_dashboard_shows_live_headcount_and_an_end_action(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        foreach ([$this->student, User::factory()->student()->create(['status' => 'active'])] as $attendee) {
            VirtualClassAttendee::create([
                'virtual_class_id' => $room->id,
                'user_id' => $attendee->id,
                'joined_at' => now()->subMinutes(5),
                'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
            ]);
        }

        // A student who already left must not be counted as still in the room.
        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => User::factory()->student()->create(['status' => 'active'])->id,
            'joined_at' => now()->subMinutes(20),
            'left_at' => now()->subMinutes(2),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        // LiveKit is unconfigured here, so the count is the attendance estimate.
        $response->assertSee('~2 in room');
        $response->assertDontSee('3 in room');
        $response->assertSee(route('instructor.classes.virtual_classes.end', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
        $response->assertSee('dash-end', false);
    }

    public function test_instructor_dashboard_uses_the_exact_livekit_headcount(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        // Attendance says one student; LiveKit says three connected plus one
        // that already disconnected.
        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => $this->student->id,
            'joined_at' => now()->subMinutes(5),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        Http::fake([
            '*ListParticipants' => Http::response([
                'participants' => [
                    ['identity' => 'user-1', 'state' => 2],
                    ['identity' => 'user-2', 'state' => 2],
                    ['identity' => 'user-3', 'state' => 1],
                    ['identity' => 'user-4', 'state' => 3], // DISCONNECTED
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('3 in room');
        $response->assertDontSee('~3 in room');
        $response->assertDontSee('1 in room');

        Http::assertSent(function ($request) use ($room) {
            if ($request->url() !== 'https://test-project.livekit.cloud/twirp/livekit.RoomService/ListParticipants') {
                return false;
            }

            $data = $request->data();
            if (($data['room'] ?? null) !== $room->livekit_room_name) {
                return false;
            }

            $authorization = $request->header('Authorization')[0] ?? '';
            $jwt = Str::after($authorization, 'Bearer ');
            $claims = json_decode(base64_decode(strtr(explode('.', $jwt)[1] ?? '', '-_', '+/')), true);

            return ($claims['iss'] ?? null) === 'APItestkey123'
                && ($claims['sub'] ?? null) === 'lms-server'
                && ($claims['video']['roomAdmin'] ?? false) === true
                && ($claims['video']['room'] ?? null) === $room->livekit_room_name;
        });
    }

    public function test_instructor_dashboard_falls_back_to_attendance_when_livekit_errors(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => $this->student->id,
            'joined_at' => now()->subMinutes(5),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        Http::fake([
            '*ListParticipants' => Http::response(['message' => 'unavailable'], 503),
        ]);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('~1 in room');
        $response->assertSee('dash-end', false);
    }

    public function test_instructor_dashboard_does_not_call_livekit_for_a_scheduled_class(): void
    {
        $this->configureLiveKit();
        $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        Http::fake();

        $this->actingAs($this->instructor)->get('/instructor/dashboard')->assertOk();

        Http::assertNothingSent();
    }

    public function test_live_count_endpoint_returns_the_exact_livekit_count(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        Http::fake([
            '*ListParticipants' => Http::response([
                'participants' => [
                    ['identity' => 'user-1', 'state' => 2],
                    ['identity' => 'user-2', 'state' => 1],
                    ['identity' => 'user-3', 'state' => 3],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->instructor)->getJson(
            route('instructor.classes.virtual_classes.live-count', [$this->class, $room])
        );

        $response->assertOk()->assertJson([
            'live' => true,
            'count' => 2,
            'exact' => true,
        ]);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_live_count_endpoint_falls_back_to_attendance_when_livekit_fails(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => $this->student->id,
            'joined_at' => now()->subMinutes(3),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        Http::fake(['*ListParticipants' => Http::response([], 500)]);

        $this->actingAs($this->instructor)
            ->getJson(route('instructor.classes.virtual_classes.live-count', [$this->class, $room]))
            ->assertOk()
            ->assertJson(['live' => true, 'count' => 1, 'exact' => false]);
    }

    public function test_live_count_endpoint_reports_a_scheduled_class_as_not_live(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        Http::fake();

        $this->actingAs($this->instructor)
            ->getJson(route('instructor.classes.virtual_classes.live-count', [$this->class, $room]))
            ->assertOk()
            ->assertJson(['live' => false, 'count' => 0, 'exact' => false]);

        Http::assertNothingSent();
    }

    public function test_live_count_endpoint_rejects_another_instructor(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);
        Http::fake();

        $other = User::factory()->instructor()->create(['status' => 'active']);

        $this->actingAs($other)
            ->getJson(route('instructor.classes.virtual_classes.live-count', [$this->class, $room]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_live_count_endpoint_returns_the_participant_roster(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        Http::fake([
            '*ListParticipants' => Http::response([
                'participants' => [
                    [
                        'identity' => 'user-'.$this->instructor->id,
                        'name' => 'a stale token name',
                        'state' => 2,
                        'joinedAt' => now()->subMinutes(12)->toIso8601String(),
                    ],
                    ['identity' => 'user-'.$this->student->id, 'state' => 2],
                    ['identity' => 'server-recorder', 'name' => 'Room recorder', 'state' => 1],
                    ['identity' => 'user-999999', 'state' => 3], // already disconnected
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->instructor)->getJson(
            route('instructor.classes.virtual_classes.live-count', [$this->class, $room])
        );

        $response->assertOk()->assertJson(['live' => true, 'count' => 3, 'exact' => true]);

        $byIdentity = collect($response->json('participants'))->keyBy('identity');

        // Names come from the LMS, so the roster shows the real student rather
        // than whatever the join token claimed.
        $this->assertSame($this->student->full_name, $byIdentity['user-'.$this->student->id]['name']);
        $this->assertSame('student', $byIdentity['user-'.$this->student->id]['role']);
        $this->assertFalse($byIdentity['user-'.$this->student->id]['is_instructor']);

        $this->assertSame($this->instructor->full_name, $byIdentity['user-'.$this->instructor->id]['name']);
        $this->assertSame('instructor', $byIdentity['user-'.$this->instructor->id]['role']);
        $this->assertTrue($byIdentity['user-'.$this->instructor->id]['is_instructor']);

        // Connect time is passed through as RFC 3339 for an "in room 12m" label.
        $this->assertNotNull($byIdentity['user-'.$this->instructor->id]['joined_at']);
        $this->assertNull($byIdentity['user-'.$this->student->id]['joined_at']);

        // An identity we never minted stays an observer, named by LiveKit.
        $this->assertSame('Room recorder', $byIdentity['server-recorder']['name']);
        $this->assertSame('participant', $byIdentity['server-recorder']['role']);

        // One ListParticipants call answers both the count and the roster.
        Http::assertSentCount(1);
    }

    public function test_live_count_fallback_roster_uses_attendance_names(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => $this->student->id,
            'joined_at' => now()->subMinutes(4),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        Http::fake(['*ListParticipants' => Http::response([], 500)]);

        $response = $this->actingAs($this->instructor)->getJson(
            route('instructor.classes.virtual_classes.live-count', [$this->class, $room])
        );

        $response->assertOk()->assertJson(['live' => true, 'count' => 1, 'exact' => false]);
        $this->assertSame($this->student->full_name, $response->json('participants.0.name'));
        $this->assertSame('student', $response->json('participants.0.role'));
        $this->assertNotNull($response->json('participants.0.joined_at'));
    }

    public function test_instructor_dashboard_embeds_the_roster_for_a_live_row(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        Http::fake([
            '*ListParticipants' => Http::response([
                'participants' => [
                    ['identity' => 'user-'.$this->instructor->id, 'state' => 2],
                    ['identity' => 'user-'.$this->student->id, 'state' => 2],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('data-live-participants="', false);
        // Both names are on the page before the first poll, ready to hover.
        $response->assertSee($this->student->full_name);
        $response->assertSee($this->instructor->full_name);
        // "(You)" is decided against the instructor reading the page.
        $response->assertSee('data-viewer-id="'.$this->instructor->id.'"', false);
        // The headcount and the embedded roster share a single LiveKit call.
        Http::assertSentCount(1);
    }

    public function test_dashboard_escapes_a_participant_name(): void
    {
        $this->configureLiveKit();
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        $this->student->forceFill([
            'first_name' => '<img src=x onerror=alert(1)>',
            'last_name' => 'Doe',
        ])->save();

        Http::fake([
            '*ListParticipants' => Http::response([
                'participants' => [
                    ['identity' => 'user-'.$this->student->id, 'state' => 2],
                    ['identity' => 'server-recorder', 'name' => '</script><script>alert(1)</script>', 'state' => 2],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        // Participant names are user-supplied; neither the encoded roster nor
        // the LiveKit-provided name may reach the page as live markup.
        $response->assertDontSee('<img src=x', false);
        $response->assertDontSee('</script><script>alert(1)</script>', false);
    }

    public function test_dashboard_marks_live_rows_for_polling_only(): void
    {
        $this->makeRoom(VirtualClass::STATUS_ONGOING);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        // The rendered attribute (not just the selector inside the script).
        $response->assertSee('data-live-count-url="', false);
        $response->assertSee('<span data-live-count-text>', false);
        $response->assertSee('POLL_MS = 20000', false);
    }

    public function test_dashboard_does_not_poll_a_scheduled_row(): void
    {
        $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertDontSee('data-live-count-url="', false);
        $response->assertDontSee('dash-live-count', false);
        // The polling script still ships; it just has no rows to poll.
        $response->assertSee('POLL_MS = 20000', false);
    }

    public function test_instructor_dashboard_has_no_end_action_before_a_class_starts(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertDontSee(route('instructor.classes.virtual_classes.end', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
        // No headcount chip for a session that is not live.
        $response->assertDontSee('dash-live-count', false);
    }

    public function test_instructor_dashboard_offers_start_shortly_before_a_class(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-10', '10:10:00', '11:10:00');

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertSee(route('instructor.classes.virtual_classes.start', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
    }

    public function test_instructor_dashboard_does_not_offer_start_days_ahead(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '2026-02-15', '10:00:00', '11:00:00');

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee($room->title);
        $response->assertDontSee(route('instructor.classes.virtual_classes.start', [
            'class' => $this->class->id,
            'virtualClass' => $room->id,
        ]), false);
    }

    public function test_instructor_dashboard_never_shows_another_instructors_class(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING);

        $other = User::factory()->instructor()->create(['status' => 'active']);

        $response = $this->actingAs($other)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertDontSee($room->title);
    }
}

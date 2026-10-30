<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use App\Services\VirtualClassRoomTimeline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The instructor dashboard's live room status widget: a headcount sparkline and
 * the session's recent joins and leaves.
 *
 * Both are derived from the session's attendance rows, so these tests pin the
 * derivation (who counts at each instant, which events, in what order) as well
 * as what the dashboard renders and what the poll endpoint returns.
 */
class VirtualClassRoomStatusWidgetTest extends TestCase
{
    protected User $instructor;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        // Frozen mid-session: the live class below runs 09:00-11:00 with the
        // clock at 10:00, so the whole session fits inside one hour-long window.
        $this->travelTo(Carbon::parse('2026-02-10 10:00:00'));

        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);

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

    protected function makeRoom(string $status = VirtualClass::STATUS_ONGOING, string $start = '09:00:00', string $end = '11:00:00'): VirtualClass
    {
        return VirtualClass::create([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Room status session',
            'meeting_date' => '2026-02-10',
            'start_time' => $start,
            'end_time' => $end,
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-room-'.Str::lower(Str::random(16)),
            'status' => $status,
            'created_by' => $this->instructor->id,
        ]);
    }

    protected function attend(VirtualClass $room, string $name, string $joinedAt, ?string $leftAt = null): User
    {
        $student = User::factory()->student()->create([
            'first_name' => $name,
            'last_name' => 'Student',
            'status' => 'active',
        ]);

        VirtualClassAttendee::create([
            'virtual_class_id' => $room->id,
            'user_id' => $student->id,
            'joined_at' => Carbon::parse('2026-02-10 '.$joinedAt),
            'left_at' => $leftAt === null ? null : Carbon::parse('2026-02-10 '.$leftAt),
            'attendance_status' => VirtualClassAttendee::STATUS_PRESENT,
        ]);

        return $student;
    }

    /**
     * Ana is still in, Beto joined and left, Cara is the newest join.
     */
    protected function seedSession(VirtualClass $room): void
    {
        $this->attend($room, 'Ana', '09:40:00');
        $this->attend($room, 'Beto', '09:10:00', '09:30:00');
        $this->attend($room, 'Cara', '09:55:00');
    }

    public function test_the_timeline_counts_who_was_in_the_room_at_each_point(): void
    {
        $room = $this->makeRoom();
        $this->seedSession($room);

        $snapshot = app(VirtualClassRoomTimeline::class)->snapshot($room);

        // 24 buckets over the session window, inclusive of both ends.
        $this->assertCount(25, $snapshot['points']);
        $this->assertSame(2, $snapshot['peak']);
        $this->assertSame(2, $snapshot['now']);

        $counts = array_column($snapshot['points'], 'count');

        // 09:00 nothing yet, 09:10 Beto, 09:30 saw Beto leave, 09:40 Ana,
        // 09:55 Cara — a hole in the middle, not a monotonically rising line.
        $this->assertSame(0, $counts[0]);
        $this->assertSame(1, $counts[4]);
        $this->assertSame(0, $counts[12]);
        $this->assertSame(1, $counts[16]);
        $this->assertSame(2, $counts[22]);
    }

    public function test_the_timeline_lists_joins_and_leaves_newest_first(): void
    {
        $room = $this->makeRoom();
        $this->seedSession($room);

        $events = app(VirtualClassRoomTimeline::class)->snapshot($room)['events'];

        $this->assertSame(['join', 'join', 'leave', 'join'], array_column($events, 'type'));
        $this->assertSame(
            ['Cara Student', 'Ana Student', 'Beto Student', 'Beto Student'],
            array_column($events, 'name'),
        );
        $this->assertSame('student', $events[0]['role']);
        $this->assertSame(
            Carbon::parse('2026-02-10 09:55:00')->toIso8601String(),
            $events[0]['at'],
        );
    }

    public function test_the_timeline_windows_a_long_session_and_caps_the_feed(): void
    {
        // Started three hours ago: only the last hour is charted, even though
        // the schedule and the earliest join go back further.
        $room = $this->makeRoom(VirtualClass::STATUS_ONGOING, '07:00:00', '11:00:00');
        $this->attend($room, 'Ana', '08:00:00');

        foreach (range(1, 12) as $index) {
            $this->attend($room, 'Churn'.$index, '09:00:00', '09:30:00');
        }

        $snapshot = app(VirtualClassRoomTimeline::class)->snapshot($room, eventLimit: 5);

        $this->assertCount(25, $snapshot['points']);
        $this->assertSame(Carbon::parse('2026-02-10 09:00:00')->toIso8601String(), $snapshot['points'][0]['at']);
        $this->assertSame(Carbon::parse('2026-02-10 10:00:00')->toIso8601String(), $snapshot['points'][24]['at']);

        // Thirteen students were in the room at the first bucket, and only the
        // one who arrived before the window was still there at the last.
        $this->assertSame(13, $snapshot['peak']);
        $this->assertSame(1, $snapshot['now']);

        // Twenty-five joins and leaves happened; the feed keeps the newest five.
        $this->assertCount(5, $snapshot['events']);
        $this->assertSame('leave', $snapshot['events'][0]['type']);
    }

    public function test_the_dashboard_shows_the_live_room_widget_with_its_trend_and_feed(): void
    {
        $room = $this->makeRoom();
        $this->seedSession($room);

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('Live room status');
        $response->assertSee($room->title);
        $response->assertSee('Students in room');
        // 2 in the room at the end of the session window, same peak.
        $response->assertSee('Peak 2 · now 2');
        $response->assertSee('data-live-room-canvas', false);
        $response->assertSee('data-live-room-payload="', false);
        // The widget's styles ship with the row that needs them.
        $response->assertSee('.dash-live-room-count{', false);

        // The feed, newest first, with the names that actually joined.
        $response->assertSee('dash-live-room-event is-join', false);
        $response->assertSee('dash-live-room-event is-leave', false);
        $response->assertSee('Cara Student');
        $response->assertSee('Ana Student');
        $response->assertSee('Beto Student');

        // The widget never claims the trend is LiveKit's own data.
        $response->assertSee('come from LMS attendance records');
    }

    public function test_the_dashboard_hides_the_widget_when_nothing_is_live(): void
    {
        $this->makeRoom(VirtualClass::STATUS_SCHEDULED, '10:30:00', '12:00:00');

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();
        // No widget markup: no watch URL, no embedded payload, no chart canvas.
        $response->assertDontSee('data-live-room-url="', false);
        $response->assertDontSee('data-live-room-payload="', false);
        $response->assertDontSee('<canvas data-live-room-canvas', false);
        // The widget's styles travel with the gated block too.
        $response->assertDontSee('.dash-live-room-count{', false);
    }

    public function test_the_poll_endpoint_returns_the_trend_and_the_feed(): void
    {
        $room = $this->makeRoom();
        $this->seedSession($room);

        $response = $this->actingAs($this->instructor)->getJson(
            route('instructor.classes.virtual_classes.live-count', [$this->class, $room])
        );

        $response->assertOk()
            ->assertJsonPath('live', true)
            ->assertJsonPath('peak', 2)
            ->assertJsonPath('now', 2)
            ->assertJsonCount(25, 'history')
            ->assertJsonPath('history.0.count', 0)
            ->assertJsonPath('events.0.type', 'join')
            ->assertJsonPath('events.0.name', 'Cara Student')
            ->assertJsonPath('events.2.type', 'leave')
            ->assertJsonPath('events.2.name', 'Beto Student');
    }

    public function test_the_ended_session_payload_still_carries_empty_trend_fields(): void
    {
        $room = $this->makeRoom(VirtualClass::STATUS_COMPLETED, '08:00:00', '09:00:00');

        $response = $this->actingAs($this->instructor)->getJson(
            route('instructor.classes.virtual_classes.live-count', [$this->class, $room])
        );

        $response->assertOk()->assertJson([
            'live' => false,
            'history' => [],
            'peak' => 0,
            'now' => 0,
            'events' => [],
        ]);

        Http::assertNothingSent();
    }
}

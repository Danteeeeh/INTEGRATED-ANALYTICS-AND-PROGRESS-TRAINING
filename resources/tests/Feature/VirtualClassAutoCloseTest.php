<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualClassAutoCloseTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create();

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
        ]);
    }

    protected function makeVirtualClass(array $overrides = []): VirtualClass
    {
        return VirtualClass::create(array_merge([
            'course_id' => $this->class->course_id,
            'class_id' => $this->class->id,
            'instructor_id' => $this->instructor->id,
            'title' => 'Week 1 - Introduction',
            'meeting_date' => now()->toDateString(),
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
            'meeting_provider' => VirtualClass::PROVIDER_GOOGLE_MEET,
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
            'status' => VirtualClass::STATUS_SCHEDULED,
            'created_by' => $this->instructor->id,
        ], $overrides));
    }

    public function test_end_datetime_combines_date_and_time(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => '2026-10-06',
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
        ]);

        $this->assertSame('2026-10-06 08:30:00', $vc->startsAt()->toDateTimeString());
        $this->assertSame('2026-10-06 09:30:00', $vc->endsAt()->toDateTimeString());
    }

    public function test_meeting_that_has_not_ended_is_not_auto_closed(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->addDay()->toDateString(),
            'status' => VirtualClass::STATUS_ONGOING,
        ]);

        $this->assertFalse($vc->hasEnded());
        $this->assertFalse($vc->syncStatus());
        $this->assertSame(VirtualClass::STATUS_ONGOING, $vc->fresh()->status);
    }

    public function test_ongoing_meeting_is_auto_closed_once_end_time_passes(): void
    {
        // Started 3 hours ago, ended 2 hours ago.
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->subHours(3)->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => VirtualClass::STATUS_ONGOING,
        ]);

        $this->assertTrue($vc->hasEnded());
        $this->assertTrue($vc->syncStatus());
        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_scheduled_meeting_is_auto_closed_when_end_time_passes(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->subHours(3)->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => VirtualClass::STATUS_SCHEDULED,
        ]);

        $vc->syncStatus();

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_cancelled_meetings_are_never_auto_closed(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->subDay()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => VirtualClass::STATUS_CANCELLED,
        ]);

        $this->assertFalse($vc->syncStatus());
        $this->assertSame(VirtualClass::STATUS_CANCELLED, $vc->fresh()->status);
    }

    public function test_completed_meetings_are_never_re_closed(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->subDay()->toDateString(),
            'status' => VirtualClass::STATUS_COMPLETED,
        ]);

        $this->assertFalse($vc->syncStatus());
        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_instructor_can_manually_end_a_meeting(): void
    {
        $vc = $this->makeVirtualClass(['status' => VirtualClass::STATUS_ONGOING]);

        $this->actingAs($this->instructor)
            ->post("/instructor/classes/{$this->class->id}/virtual_classes/{$vc->id}/end")
            ->assertRedirect(route('instructor.classes.virtual_classes.show', [$this->class, $vc]));

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_instructor_cannot_end_an_already_closed_meeting(): void
    {
        $vc = $this->makeVirtualClass(['status' => VirtualClass::STATUS_COMPLETED]);

        $this->actingAs($this->instructor)
            ->post("/instructor/classes/{$this->class->id}/virtual_classes/{$vc->id}/end");

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_show_page_auto_closes_an_expired_meeting(): void
    {
        $vc = $this->makeVirtualClass([
            'meeting_date' => now()->subHours(3)->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => VirtualClass::STATUS_ONGOING,
        ]);

        $this->actingAs($this->instructor)
            ->get("/instructor/classes/{$this->class->id}/virtual_classes/{$vc->id}")
            ->assertOk();

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $vc->fresh()->status);
    }

    public function test_index_page_auto_closes_expired_meetings(): void
    {
        $expired = $this->makeVirtualClass([
            'meeting_date' => now()->subHours(3)->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => VirtualClass::STATUS_ONGOING,
        ]);

        $this->actingAs($this->instructor)
            ->get("/instructor/classes/{$this->class->id}/virtual_classes")
            ->assertOk();

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $expired->fresh()->status);
    }

    public function test_console_command_closes_expired_meetings(): void
    {
        $expired = $this->makeVirtualClass([
            'meeting_date' => now()->subHours(3)->toDateString(),
            'start_time' => now()->subHours(3)->format('H:i:s'),
            'end_time' => now()->subHours(2)->format('H:i:s'),
            'status' => VirtualClass::STATUS_ONGOING,
        ]);

        $this->artisan('virtual-classes:auto-close')
            ->assertSuccessful();

        $this->assertSame(VirtualClass::STATUS_COMPLETED, $expired->fresh()->status);
    }
}
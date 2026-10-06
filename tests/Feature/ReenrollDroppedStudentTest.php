<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * enrollments is UNIQUE(student_id, class_id), so re-enrolling a student who
 * dropped a class cannot insert a second row. Every create path has to revive
 * the dropped row instead of blowing up with a raw SQL integrity error.
 */
class ReenrollDroppedStudentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->droppedRow = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_DROPPED,
            'enrolled_at' => now()->subMonths(2),
            'completed_at' => now()->subMonths(2),
            'final_grade' => 88.00,
        ]);
    }

    protected Enrollment $droppedRow;

    public function test_enroll_revives_the_dropped_row_instead_of_inserting(): void
    {
        [$enrollment, $revived] = Enrollment::enroll($this->student->id, $this->class->id);

        $this->assertTrue($revived, 'The dropped row should be reused.');
        $this->assertSame($this->droppedRow->id, $enrollment->id, 'No second row may be created.');
        $this->assertSame(1, Enrollment::count(), 'Still exactly one enrollment for this pair.');
        $this->assertSame(Enrollment::STATUS_ACTIVE, $enrollment->fresh()->status);
    }

    public function test_reviving_clears_the_stale_completion_fields(): void
    {
        Enrollment::enroll($this->student->id, $this->class->id);

        $fresh = $this->droppedRow->fresh();

        // The old completion would otherwise make the new enrollment look
        // finished the moment it is activated.
        $this->assertNull($fresh->completed_at);
        $this->assertNull($fresh->final_grade);
    }

    public function test_enroll_refuses_when_a_live_row_already_exists(): void
    {
        [$enrollment, $revived] = Enrollment::enroll($this->student->id, $this->class->id);

        // Second attempt must be a no-op, not an update of a live row.
        [$again, $wasRevived] = Enrollment::enroll($this->student->id, $this->class->id, Enrollment::STATUS_PENDING);

        $this->assertTrue($revived);
        $this->assertNull($again);
        $this->assertFalse($wasRevived);
        $this->assertSame(1, Enrollment::count());
    }

    public function test_admin_create_form_re_enrolls_a_dropped_student(): void
    {
        $response = $this->actingAs($this->admin)
            ->post('/admin/enrollments', [
                'student_id' => $this->student->id,
                'class_ids' => [$this->class->id],
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHas('status');

        $this->assertSame(1, Enrollment::count());
        $this->assertSame(Enrollment::STATUS_ACTIVE, $this->droppedRow->fresh()->status);

        // Per-row failures must not be flashed under 'errors', which Laravel
        // reserves for the ViewErrorBag.
        $this->assertNull(session('errors'));
        $response->assertSessionMissing('errors');
    }

    public function test_skipped_rows_are_reported_without_clobbering_the_error_bag(): void
    {
        // A second live enrollment for a class the student already holds.
        $other = ClassModel::factory()->create([
            'course_id' => $this->class->course_id,
            'instructor_id' => $this->class->instructor_id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $other->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/enrollments', [
            'student_id' => $this->student->id,
            'class_ids' => [$other->id],
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('enrollment_errors');
        $response->assertSessionMissing('errors');

        // And the page that shows those messages still renders.
        $this->actingAs($this->admin)->get('/admin/enrollments')->assertOk();
    }

    public function test_bulk_auto_enroll_re_enrolls_a_dropped_student(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/enrollments/bulk-auto-enroll', [
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertSame(1, Enrollment::count(), 'Auto-enroll must not violate the unique index.');
        $this->assertSame(Enrollment::STATUS_ACTIVE, $this->droppedRow->fresh()->status);
    }

    public function test_drop_then_re_enroll_round_trip(): void
    {
        $live = Enrollment::enroll($this->droppedRow->student_id, $this->class->id, Enrollment::STATUS_ACTIVE)[0];
        $live->drop();

        $this->assertSame(Enrollment::STATUS_DROPPED, $live->fresh()->status);

        $again = Enrollment::enroll($this->droppedRow->student_id, $this->class->id)[0];

        $this->assertSame(1, Enrollment::count());
        $this->assertSame($live->id, $again->id);
        $this->assertSame(Enrollment::STATUS_ACTIVE, $again->fresh()->status);
    }
}
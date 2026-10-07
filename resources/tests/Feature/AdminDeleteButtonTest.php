<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end check of the delete button on /admin/enrollments, plus the
 * authorization gap that let any admin delete regardless of permissions.
 */
class AdminDeleteButtonTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected ClassModel $class;

    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create(['status' => 'active']);

        $this->grant($this->admin, ['enrollments.view', 'enrollments.delete']);

        $this->student = User::factory()->student()->create(['status' => 'active']);

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    /**
     * Attach the listed permissions to the user's role, mirroring how a real
     * admin is granted access.
     */
    protected function grant(User $user, array $permissions): void
    {
        $user->role->permissions()->sync(
            \App\Models\Permission::whereIn('name', $permissions)->pluck('id')
        );
    }

    public function test_delete_button_removes_the_enrollment(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment));

        $response->assertRedirect(route('admin.enrollments.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseMissing('enrollments', ['id' => $this->enrollment->id]);
    }

    public function test_the_index_page_renders_a_working_delete_form(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->getContent();

        // A real form pointed at the destroy route, with the CSRF token and the
        // DELETE method spoof — not a dead <button> or a JS-only handler.
        $this->assertStringContainsString(route('admin.enrollments.destroy', $this->enrollment), $html);
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="DELETE"', $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_the_delete_form_only_targets_its_own_row(): void
    {
        $other = User::factory()->student()->create(['status' => 'active']);

        $sibling = Enrollment::create([
            'student_id' => $other->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', ['id' => $this->enrollment->id]);

        // Sibling enrollment must survive.
        $this->assertDatabaseHas('enrollments', ['id' => $sibling->id]);
    }

    public function test_the_rendered_form_points_at_each_own_row(): void
    {
        $other = User::factory()->student()->create(['status' => 'active']);

        $sibling = Enrollment::create([
            'student_id' => $other->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.enrollments.destroy', $this->enrollment), $html);
        $this->assertStringContainsString(route('admin.enrollments.destroy', $sibling), $html);
    }

    public function test_deleting_an_enrollment_keeps_the_grade_history(): void
    {
        $item = GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Week 1',
            'item_type' => 'assignment',
            'max_points' => 100,
            'is_released' => true,
            'released_at' => now(),
            'position' => 0,
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => 91,
            'score_percent' => 91,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment));

        // Grades are keyed on (student_id, grade_item_id), not enrollment_id, so
        // the transcript survives the roster removal. Deleting them silently
        // would be worse than keeping them.
        $this->assertDatabaseHas('grades', [
            'student_id' => $this->student->id,
            'grade_item_id' => $item->id,
        ]);
    }

    public function test_deleting_an_enrollment_keeps_its_audit_log(): void
    {
        EnrollmentLog::create([
            'enrollment_id' => $this->enrollment->id,
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'action' => 'auto_enroll',
            'details' => 'seeded',
            'status' => 'success',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment));

        // enrollment_logs.enrollment_id is ON DELETE SET NULL, so the log row
        // stays and simply loses the dangling pointer.
        $this->assertDatabaseHas('enrollment_logs', ['student_id' => $this->student->id]);
        $this->assertDatabaseMissing('enrollment_logs', [
            'student_id' => $this->student->id,
            'enrollment_id' => $this->enrollment->id,
        ]);
    }

    public function test_an_admin_without_the_delete_permission_is_blocked(): void
    {
        $readOnly = User::factory()->admin()->create(['status' => 'active']);

        // Same admin role, but no enrollments.delete permission granted.
        $this->grant($readOnly, ['enrollments.view']);

        $this->actingAs($readOnly)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertForbidden();

        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id]);
    }

    public function test_a_student_cannot_reach_the_admin_delete_route(): void
    {
        $this->actingAs($this->student)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertForbidden();

        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id]);
    }

    public function test_deleting_a_dropped_enrollment_also_works(): void
    {
        // Different student: enrollments is UNIQUE(student_id, class_id), so the
        // setUp student already holds a row for this class.
        $other = User::factory()->student()->create(['status' => 'active']);

        $dropped = Enrollment::create([
            'student_id' => $other->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_DROPPED,
            'enrolled_at' => now(),
        ]);

        // The index hides dropped rows, but the route must still work when
        // "Show Dropped" is on.
        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $dropped))
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', ['id' => $dropped->id]);
    }
}
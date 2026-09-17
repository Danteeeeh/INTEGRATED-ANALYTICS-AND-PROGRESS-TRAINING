<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRbac(): void
    {
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    protected function makeUser(string $role): User
    {
        $this->seedRbac();
        $roleId = Role::where('slug', $role)->value('id');

        return User::factory()->create([
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }

    public function test_user_filters_ignore_empty_all_values_and_apply_selected_values(): void
    {
        $admin = $this->makeUser(Role::ADMIN);
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();
        $instructorRole = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();

        $student = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Student',
            'email' => 'ana.student@example.com',
            'role_id' => $studentRole->id,
        ]);
        User::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'Instructor',
            'email' => 'ben.instructor@example.com',
            'role_id' => $instructorRole->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?role_slug=&status=&search=')
            ->assertOk()
            ->assertSee('Ana Student')
            ->assertSee('Ben Instructor');

        $this->actingAs($admin)
            ->get('/admin/users?role_slug=student&status=active&search=Ana')
            ->assertOk()
            ->assertSee('Ana Student')
            ->assertDontSee('Ben Instructor');
    }

    public function test_course_filter_changes_admin_result_set_and_clear_restores_all(): void
    {
        $admin = $this->makeUser(Role::ADMIN);
        $period = AcademicPeriod::factory()->create();
        $visible = Course::factory()->create(['title' => 'Visible Biology', 'status' => 'published', 'academic_period_id' => $period->id]);
        Course::factory()->create(['title' => 'Hidden History', 'status' => 'draft', 'academic_period_id' => $period->id]);

        $this->actingAs($admin)
            ->get('/admin/courses?status=published&search=Biology')
            ->assertOk()
            ->assertSee('Visible Biology')
            ->assertDontSee('Hidden History');

        $this->actingAs($admin)
            ->get('/admin/courses?status=&search=')
            ->assertOk()
            ->assertSee('Visible Biology')
            ->assertSee('Hidden History');
    }

    public function test_registrar_student_filters_are_applied(): void
    {
        $registrar = $this->makeUser(Role::REGISTRAR);
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();
        User::factory()->create(['first_name' => 'Filtered', 'last_name' => 'Student', 'role_id' => $studentRole->id, 'status' => 'inactive']);
        User::factory()->create(['first_name' => 'Other', 'last_name' => 'Student', 'role_id' => $studentRole->id, 'status' => 'active']);

        $this->actingAs($registrar)
            ->get('/registrar/students?status=inactive&search=Filtered')
            ->assertOk()
            ->assertSee('Filtered Student')
            ->assertDontSee('<div class="user-name">Other Student</div>', false);
    }

    public function test_admin_enrollment_filter_is_applied(): void
    {
        $admin = $this->makeUser(Role::ADMIN);
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();
        $student = User::factory()->create(['first_name' => 'Enrolled', 'last_name' => 'Student', 'role_id' => $studentRole->id]);
        $otherStudent = User::factory()->create(['first_name' => 'Other', 'last_name' => 'Student', 'role_id' => $studentRole->id]);
        $class = ClassModel::factory()->create();
        Enrollment::factory()->create(['student_id' => $student->id, 'class_id' => $class->id, 'status' => 'active']);
        Enrollment::factory()->create(['student_id' => $otherStudent->id, 'class_id' => $class->id, 'status' => 'pending']);

        $this->actingAs($admin)
            ->get('/admin/enrollments?status=active&search=Enrolled')
            ->assertOk()
            ->assertSeeInOrder(['<td>Enrolled Student</td>'])
            ->assertDontSee('<td>Other Student</td>', false);
    }
}

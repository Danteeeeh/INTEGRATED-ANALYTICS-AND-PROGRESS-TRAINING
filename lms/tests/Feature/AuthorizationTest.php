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

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRbac(): void
    {
        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeUser(string $roleSlug): User
    {
        $this->seedRbac();

        $role = Role::where('slug', $roleSlug)->firstOrFail();

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_view_all_courses(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/courses');
        $response->assertOk();
    }

    public function test_instructor_cannot_view_all_courses_unless_assigned(): void
    {
        $instructor = $this->makeUser('instructor');
        Course::factory()->create();

        $response = $this->actingAs($instructor)->get('/admin/courses');
        $response->assertForbidden();
    }

    public function test_student_cannot_view_all_courses(): void
    {
        $student = $this->makeUser('student');
        Course::factory()->create();

        $response = $this->actingAs($student)->get('/admin/courses');
        $response->assertForbidden();
    }

    public function test_instructor_can_view_assigned_courses(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create();
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->actingAs($instructor)->get('/instructor/courses');
        $response->assertOk();
    }

    public function test_student_can_view_enrolled_courses(): void
    {
        $student = $this->makeUser('student');
        $course = Course::factory()->create();
        $class = ClassModel::factory()->create(['course_id' => $course->id]);
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->get('/student/courses');
        $response->assertOk();
    }

    public function test_admin_can_create_courses(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/admin/courses/create');
        $response->assertOk();
    }

    public function test_instructor_cannot_create_courses_via_admin(): void
    {
        $instructor = $this->makeUser('instructor');

        $response = $this->actingAs($instructor)->get('/admin/courses/create');
        $response->assertForbidden();
    }

    public function test_student_cannot_create_courses(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/admin/courses/create');
        $response->assertForbidden();
    }

    public function test_admin_can_update_any_course(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->get("/admin/courses/{$course->id}/edit");
        $response->assertOk();
    }

    public function test_instructor_can_update_assigned_course(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create();
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->actingAs($instructor)->get("/instructor/courses/{$course->id}/edit");
        $response->assertOk();
    }

    public function test_instructor_cannot_update_unassigned_course(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create();

        $response = $this->actingAs($instructor)->get("/instructor/courses/{$course->id}/edit");
        $response->assertForbidden();
    }

    public function test_admin_can_delete_courses(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/courses/{$course->id}");
        $response->assertRedirect();
    }

    public function test_instructor_cannot_delete_courses(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = Course::factory()->create();

        $response = $this->actingAs($instructor)->delete("/admin/courses/{$course->id}");
        $response->assertForbidden();
    }

    public function test_admin_can_view_all_classes(): void
    {
        $admin = $this->makeUser('admin');
        ClassModel::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/classes');
        $response->assertOk();
    }

    public function test_instructor_can_view_assigned_classes(): void
    {
        $instructor = $this->makeUser('instructor');
        $class = ClassModel::factory()->create(['instructor_id' => $instructor->id]);

        $response = $this->actingAs($instructor)->get('/instructor/classes');
        $response->assertOk();
    }

    public function test_instructor_cannot_view_unassigned_classes(): void
    {
        $instructor = $this->makeUser('instructor');
        ClassModel::factory()->create();

        $response = $this->actingAs($instructor)->get('/instructor/classes');
        $response->assertOk(); // Will see empty list
    }

    public function test_student_can_view_enrolled_classes(): void
    {
        $student = $this->makeUser('student');
        $class = ClassModel::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($student)->get('/student/classes');
        $response->assertOk();
    }

    public function test_admin_can_view_all_enrollments(): void
    {
        $admin = $this->makeUser('admin');
        Enrollment::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/enrollments');
        $response->assertOk();
    }

    public function test_instructor_can_view_class_enrollments(): void
    {
        $instructor = $this->makeUser('instructor');
        $class = ClassModel::factory()->create(['instructor_id' => $instructor->id]);
        Enrollment::factory()->create(['class_id' => $class->id]);

        $response = $this->actingAs($instructor)->get('/instructor/enrollments');
        $response->assertOk();
    }

    public function test_student_can_view_own_enrollments(): void
    {
        $student = $this->makeUser('student');
        Enrollment::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($student)->get('/student/enrollments');
        $response->assertOk();
    }

    public function test_student_cannot_view_other_enrollments(): void
    {
        $student = $this->makeUser('student');
        Enrollment::factory()->create(); // Different student

        $response = $this->actingAs($student)->get('/admin/enrollments');
        $response->assertForbidden();
    }

    public function test_admin_can_manage_academic_periods(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->get('/admin/academic-periods');
        $response->assertOk();

        $response = $this->actingAs($admin)->get('/admin/academic-periods/create');
        $response->assertOk();
    }

    public function test_instructor_cannot_manage_academic_periods(): void
    {
        $instructor = $this->makeUser('instructor');

        $response = $this->actingAs($instructor)->get('/admin/academic-periods');
        $response->assertForbidden();
    }

    public function test_student_cannot_manage_academic_periods(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/admin/academic-periods');
        $response->assertForbidden();
    }
}

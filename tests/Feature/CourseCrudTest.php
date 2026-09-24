<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCrudTest extends TestCase
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

    public function test_admin_can_create_course(): void
    {
        $admin = $this->makeUser('admin');
        $academicPeriod = AcademicPeriod::factory()->create();

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Introduction to Computer Science',
            'description' => 'A foundational course',
            'objectives' => 'Learn programming basics',
            'duration_weeks' => 12,
            'academic_period_id' => $academicPeriod->id,
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', ['code' => 'CS101']);
    }

    public function test_instructor_cannot_create_course_via_admin(): void
    {
        $instructor = $this->makeUser('instructor');

        $response = $this->actingAs($instructor)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Introduction to Computer Science',
            'status' => 'draft',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_open_course_edit_page(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $this->actingAs($admin)->get("/admin/courses/{$course->id}/edit")
            ->assertOk()
            ->assertSee('Edit Course');
    }

    public function test_admin_can_update_course(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->put("/admin/courses/{$course->id}", [
            'title' => 'Updated Course Title',
            'status' => 'published',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('courses', ['title' => 'Updated Course Title']);
    }

    public function test_admin_can_delete_course(): void
    {
        $admin = $this->makeUser('admin');
        $course = Course::factory()->create();

        $response = $this->actingAs($admin)->delete("/admin/courses/{$course->id}");

        $response->assertRedirect();
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
    }

    public function test_course_code_must_be_unique(): void
    {
        $admin = $this->makeUser('admin');
        Course::factory()->create(['code' => 'CS101']);

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Duplicate Course',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_duration_weeks_must_be_positive_integer(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Test Course',
            'status' => 'draft',
            'duration_weeks' => -5,
        ]);

        $response->assertSessionHasErrors('duration_weeks');
    }

    public function test_academic_period_must_exist(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Test Course',
            'status' => 'draft',
            'academic_period_id' => 99999,
        ]);

        $response->assertSessionHasErrors('academic_period_id');
    }

    public function test_course_validation_requires_required_fields(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/admin/courses', []);

        $response->assertSessionHasErrors(['code', 'title', 'status']);
    }

    public function test_course_status_must_be_valid(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin)->post('/admin/courses', [
            'code' => 'CS101',
            'title' => 'Test Course',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors('status');
    }
}

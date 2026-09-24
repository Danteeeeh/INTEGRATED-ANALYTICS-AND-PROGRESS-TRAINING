<?php

namespace Tests\Feature\Authorization;

use App\Models\AcademicPeriod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrarCourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    protected function makeRegistrar(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::REGISTRAR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function makeInstructor(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    public function test_registrar_can_open_course_create_form(): void
    {
        $this->actingAs($this->makeRegistrar())
            ->get(route('registrar.courses.create'))
            ->assertStatus(200)
            ->assertSee('Add Course');
    }

    public function test_registrar_can_create_course(): void
    {
        $registrar = $this->makeRegistrar();
        $period = AcademicPeriod::factory()->create();

        $this->actingAs($registrar)
            ->post(route('registrar.courses.store'), [
                'code' => 'IT-101',
                'title' => 'Introduction to IT',
                'academic_period_id' => $period->id,
                'duration_weeks' => 12,
                'status' => 'draft',
            ])
            ->assertRedirect(route('registrar.courses.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('courses', [
            'code' => 'IT-101',
            'title' => 'Introduction to IT',
            'created_by' => $registrar->id,
        ]);
    }

    public function test_registrar_can_edit_course(): void
    {
        $registrar = $this->makeRegistrar();
        $course = \App\Models\Course::factory()->create(['created_by' => $registrar->id]);

        $this->actingAs($registrar)
            ->get(route('registrar.courses.edit', $course))
            ->assertStatus(200)
            ->assertSee('Edit Course');

        $this->actingAs($registrar)
            ->put(route('registrar.courses.update', $course), [
                'code' => $course->code,
                'title' => 'Updated Course Title',
                'status' => 'published',
            ])
            ->assertRedirect(route('registrar.courses.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
        ]);
    }

    public function test_instructor_cannot_create_courses_via_registrar(): void
    {
        $instructor = $this->makeInstructor();

        $this->actingAs($instructor)
            ->get(route('registrar.courses.create'))
            ->assertStatus(403);

        $this->actingAs($instructor)
            ->post(route('registrar.courses.store'), [
                'code' => 'X-999',
                'title' => 'Should Not Exist',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('courses', ['code' => 'X-999']);
    }
}

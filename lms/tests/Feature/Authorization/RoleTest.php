<?php

namespace Tests\Feature\Authorization;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
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

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertStatus(200);
    }

    public function test_instructor_cannot_access_admin_routes(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($instructor)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);

        $this->actingAs($instructor)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_student_cannot_access_admin_routes(): void
    {
        $student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);

        $this->actingAs($student)
            ->get(route('admin.users.index'))
            ->assertStatus(403);
    }

    public function test_instructor_can_access_instructor_routes(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertStatus(200);

        $this->actingAs($instructor)
            ->get(route('instructor.courses.index'))
            ->assertStatus(200);
    }

    public function test_student_cannot_access_instructor_routes(): void
    {
        $student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('instructor.dashboard'))
            ->assertStatus(403);

        $this->actingAs($student)
            ->get(route('instructor.courses.index'))
            ->assertStatus(403);
    }

    public function test_student_can_access_student_routes(): void
    {
        $student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertStatus(200);

        $this->actingAs($student)
            ->get(route('student.courses.index'))
            ->assertStatus(200);
    }

    public function test_admin_cannot_access_student_routes(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('student.dashboard'))
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_protected_routes(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('instructor.dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_user_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertStatus(403);
    }

    public function test_user_has_correct_role_helper_methods(): void
    {
        $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();
        $instructorRole = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $instructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $student = User::factory()->create(['role_id' => $studentRole->id]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isInstructor());
        $this->assertFalse($admin->isStudent());

        $this->assertFalse($instructor->isAdmin());
        $this->assertTrue($instructor->isInstructor());
        $this->assertFalse($instructor->isStudent());

        $this->assertFalse($student->isAdmin());
        $this->assertFalse($student->isInstructor());
        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isRegistrar());
    }

    public function test_registrar_can_access_registrar_routes_but_not_admin_or_instructor(): void
    {
        $registrar = User::factory()->create([
            'role_id' => Role::where('slug', Role::REGISTRAR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($registrar)->get(route('registrar.dashboard'))->assertStatus(200);
        $this->actingAs($registrar)->get(route('registrar.students.index'))->assertStatus(200);
        $this->actingAs($registrar)->get(route('registrar.enrollments.index'))->assertStatus(200);
        $this->actingAs($registrar)->get(route('admin.dashboard'))->assertStatus(403);
        $this->actingAs($registrar)->get(route('admin.settings.index'))->assertStatus(403);
        $this->actingAs($registrar)->get(route('instructor.dashboard'))->assertStatus(403);
        $this->actingAs($registrar)->get(route('admin.quizzes.index'))->assertStatus(403);
    }

    public function test_instructor_and_student_cannot_access_registrar_routes(): void
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);
        $student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->actingAs($instructor)->get(route('registrar.dashboard'))->assertStatus(403);
        $this->actingAs($student)->get(route('registrar.students.index'))->assertStatus(403);
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Department;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolHierarchyTest extends TestCase
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

    protected function makeAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
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

    protected function makeStudent(): User
    {
        return User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    public function test_hierarchy_tables_exist_after_migration(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('departments'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('programs'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasTable('sections'));

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('courses', 'department_id'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('courses', 'program_id'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('classes', 'section_id'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('users', 'section_id'));
    }

    public function test_admin_can_crud_department(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->get(route('admin.departments.index'))->assertStatus(200);

        $this->actingAs($admin)->post(route('admin.departments.store'), [
            'name' => 'College of Computer Studies',
            'code' => 'CCIS',
        ])->assertRedirect(route('admin.departments.index'));

        $department = Department::where('code', 'CCIS')->firstOrFail();
        $this->assertSame('College of Computer Studies', $department->name);

        $this->actingAs($admin)->get(route('admin.departments.show', $department))->assertStatus(200);

        $this->actingAs($admin)->put(route('admin.departments.update', $department), [
            'name' => 'College of Computing',
            'code' => 'CCIS',
        ])->assertRedirect(route('admin.departments.index'));

        $this->assertSame('College of Computing', $department->fresh()->name);
    }

    public function test_admin_can_crud_program(): void
    {
        $admin = $this->makeAdmin();
        $department = Department::create(['name' => 'CCIS', 'code' => 'CCIS']);

        $this->actingAs($admin)->post(route('admin.programs.store'), [
            'department_id' => $department->id,
            'name' => 'BS Information Technology',
            'code' => 'BSIT',
        ])->assertRedirect(route('admin.programs.index'));

        $program = Program::where('code', 'BSIT')->firstOrFail();
        $this->assertSame($department->id, $program->department_id);
    }

    public function test_admin_can_crud_section(): void
    {
        $admin = $this->makeAdmin();
        $program = Program::create(['name' => 'BSIT', 'code' => 'BSIT']);
        $period = AcademicPeriod::factory()->create();

        $this->actingAs($admin)->post(route('admin.sections.store'), [
            'program_id' => $program->id,
            'academic_period_id' => $period->id,
            'name' => 'BSIT 2A',
            'code' => 'BSIT-2A',
            'max_students' => 40,
        ])->assertRedirect(route('admin.sections.index'));

        $section = Section::where('code', 'BSIT-2A')->firstOrFail();
        $this->assertSame('BSIT 2A', $section->name);
        $this->assertSame(40, $section->max_students);
    }

    public function test_section_relations_resolve_program_and_department(): void
    {
        $department = Department::create(['name' => 'CCIS', 'code' => 'CCIS']);
        $program = Program::create(['department_id' => $department->id, 'name' => 'BSIT', 'code' => 'BSIT']);
        $section = Section::create(['program_id' => $program->id, 'name' => 'BSIT 2A', 'code' => 'BSIT-2A']);

        $this->assertSame($program->id, $section->program->id);
        $this->assertSame($department->id, $section->program->department->id);
        $this->assertSame($section->id, $program->sections()->first()->id);
    }

    public function test_instructor_and_student_cannot_access_hierarchy_admin_routes(): void
    {
        $instructor = $this->makeInstructor();
        $student = $this->makeStudent();

        $this->actingAs($instructor)->get(route('admin.departments.index'))->assertStatus(403);
        $this->actingAs($instructor)->get(route('admin.programs.index'))->assertStatus(403);
        $this->actingAs($instructor)->get(route('admin.sections.index'))->assertStatus(403);

        $this->actingAs($student)->get(route('admin.departments.index'))->assertStatus(403);
        $this->actingAs($student)->get(route('admin.sections.index'))->assertStatus(403);
    }
}

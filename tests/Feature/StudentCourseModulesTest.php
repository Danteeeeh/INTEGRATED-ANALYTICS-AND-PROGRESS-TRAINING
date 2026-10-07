<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §Student → My Courses → Modules.
 *
 * Regression guard for the "Column 'status' in WHERE is ambiguous" crash.
 * `section_module_assignments` and `modules` both own `course_id` and
 * `status`, so every column this query family touches must be qualified —
 * otherwise MySQL/MariaDB refuses the query outright.
 */
class StudentCourseModulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $course;

    protected ClassModel $class;

    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $role = Role::where('slug', 'student')->firstOrFail();

        $this->student = User::factory()->create([
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);

        $this->section = Section::create([
            'name' => 'BSIT 41010 IS',
            'code' => 'BSIT41010-IS',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'section_id' => $this->section->id,
            'status' => 'active',
        ]);

        Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);
    }

    private function module(string $title, int $position, string $status = 'published'): Module
    {
        return Module::factory()->create([
            'course_id' => $this->course->id,
            'title' => $title,
            'position' => $position,
            'status' => $status,
        ]);
    }

    public function test_modules_index_renders_when_the_section_has_module_assignments(): void
    {
        $week1 = $this->module('Week 1', 1);
        $week2 = $this->module('Week 2', 2);

        foreach ([$week1, $week2] as $module) {
            SectionModuleAssignment::create([
                'section_id' => $this->section->id,
                'module_id' => $module->id,
                'course_id' => $this->course->id,
                'status' => 'active',
                'assigned_at' => now(),
            ]);
        }

        // The query that used to blow up on `status`/`course_id`.
        $this->actingAs($this->student)
            ->get(route('student.courses.modules.index', $this->course))
            ->assertOk()
            ->assertSee('Week 1')
            ->assertSee('Week 2');
    }

    public function test_modules_index_renders_with_no_section_assignments_at_all(): void
    {
        $this->module('Week 1', 1);

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.index', $this->course))
            ->assertOk()
            ->assertSee('Week 1');
    }

    public function test_modules_index_ignores_inactive_section_assignments(): void
    {
        $active = $this->module('Active Module', 1);
        $inactive = $this->module('Inactive Module', 2);

        SectionModuleAssignment::create([
            'section_id' => $this->section->id,
            'module_id' => $active->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        SectionModuleAssignment::create([
            'section_id' => $this->section->id,
            'module_id' => $inactive->id,
            'course_id' => $this->course->id,
            'status' => 'inactive',
            'assigned_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.index', $this->course))
            ->assertOk()
            ->assertSee('Active Module')
            ->assertDontSee('Inactive Module');
    }

    public function test_module_show_page_renders(): void
    {
        $module = $this->module('Week 1', 1);

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.show', [$this->course, $module]))
            ->assertOk();
    }

    /**
     * The scopes are used standalone elsewhere (the student quiz list), so they
     * must stay correct whether or not a join is present.
     */
    public function test_scopes_qualify_their_columns(): void
    {
        $query = SectionModuleAssignment::active()->byCourse($this->course->id)->bySection($this->section->id);

        // Quote style differs per driver (MySQL uses `, SQLite uses "), so match
        // the qualified name without pinning the quotes.
        $sql = str_replace(['"', '`'], '', $query->toSql());

        foreach (['status', 'course_id', 'section_id'] as $column) {
            $this->assertStringContainsString(
                'section_module_assignments.'.$column,
                $sql,
                "Expected [{$column}] to be table-qualified."
            );
        }
    }

    public function test_scopes_are_usable_against_a_modules_join(): void
    {
        $module = $this->module('Week 1', 1);

        SectionModuleAssignment::create([
            'section_id' => $this->section->id,
            'module_id' => $module->id,
            'course_id' => $this->course->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $ids = SectionModuleAssignment::active()
            ->byCourse($this->course->id)
            ->bySection($this->section->id)
            ->join('modules', 'section_module_assignments.module_id', '=', 'modules.id')
            ->orderBy('modules.position', 'asc')
            ->pluck('section_module_assignments.module_id');

        $this->assertSame([$module->id], $ids->all());
    }

    public function test_student_cannot_open_modules_of_a_course_they_are_not_enrolled_in(): void
    {
        $other = Course::factory()->create(['status' => 'published']);

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.index', $other))
            ->assertForbidden();
    }
}
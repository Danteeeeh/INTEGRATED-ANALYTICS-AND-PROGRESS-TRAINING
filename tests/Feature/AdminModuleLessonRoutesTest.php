<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §Admin → Courses → Modules / Lessons.
 *
 * These routes are nested: admin/courses/{course}/modules/{module}/lessons/{lesson}.
 * The controllers only declared the *inner* models, so Laravel's positional
 * argument list was off by one — {course} landed in $module and every one of
 * these pages returned 500 for real users, not just in the route smoke test.
 */
class AdminModuleLessonRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Course $course;

    protected Module $module;

    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);

        $this->module = Module::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'published',
        ]);

        $this->lesson = Lesson::factory()->create([
            'module_id' => $this->module->id,
            'status' => 'published',
        ]);
    }

    /** @return array<string, array{0: string, 1: array<int, string>}> */
    public static function adminRoutes(): array
    {
        return [
            'modules index' => ['admin.courses.modules.index', ['course']],
            'modules create' => ['admin.courses.modules.create', ['course']],
            'modules show' => ['admin.courses.modules.show', ['course', 'module']],
            'modules edit' => ['admin.courses.modules.edit', ['course', 'module']],
            'lessons index' => ['admin.courses.modules.lessons.index', ['course', 'module']],
            'lessons create' => ['admin.courses.modules.lessons.create', ['course', 'module']],
            'lessons show' => ['admin.courses.modules.lessons.show', ['course', 'module', 'lesson']],
            'lessons edit' => ['admin.courses.modules.lessons.edit', ['course', 'module', 'lesson']],
        ];
    }

    /**
     * @dataProvider adminRoutes
     *
     * @param  array<int, string>  $keys
     */
    public function test_admin_module_and_lesson_pages_render(string $name, array $keys): void
    {
        $map = ['course' => $this->course, 'module' => $this->module, 'lesson' => $this->lesson];
        $args = array_map(fn ($key) => $map[$key], $keys);

        $this->actingAs($this->admin)
            ->get(route($name, $args))
            ->assertOk();
    }

    public function test_standalone_lesson_and_module_indexes_still_render(): void
    {
        // Both actions are also reachable without any {course}/{module}.
        $this->actingAs($this->admin)->get(route('admin.lessons.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.modules.index'))->assertOk();
    }

    public function test_module_page_is_scoped_to_the_course_in_the_url(): void
    {
        $other = Course::factory()->create(['status' => 'published']);

        $this->actingAs($this->admin)
            ->get(route('admin.courses.modules.show', [$other, $this->module]))
            ->assertOk();
    }
}
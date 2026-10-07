<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §Student → Courses → Lessons.
 *
 * Two regressions guarded here:
 *
 *  1. The "Choose a course for Lessons" picker pointed at the cross-course
 *     indexAll() page, so the picked course was thrown away and the student
 *     landed on every lesson from every class.
 *  2. Route model binding resolves {course}, {module} and {lesson} by primary
 *     key independently, so a lesson from a different module or course still
 *     rendered — under the wrong course/module header.
 */
class StudentLessonScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;

    protected Course $courseA;

    protected Course $courseB;

    protected Course $courseC;

    protected Module $moduleA1;

    protected Module $moduleA2;

    protected Module $moduleB1;

    protected Module $moduleC1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        // CS101 — the course the student picked.
        $this->courseA = Course::factory()->create(['status' => 'published', 'code' => 'CS101', 'title' => 'Intro to CS']);
        $this->courseB = Course::factory()->create(['status' => 'published', 'code' => 'IT202', 'title' => 'Web Systems']);

        $this->courseC = Course::factory()->create(['status' => 'published', 'code' => 'EE301', 'title' => 'Not Enrolled']);

        $this->moduleA1 = $this->module($this->courseA, 'Week 1', 1);
        $this->moduleA2 = $this->module($this->courseA, 'Week 2', 2);
        $this->moduleB1 = $this->module($this->courseB, 'Week 1', 1);
        $this->moduleC1 = $this->module($this->courseC, 'Week 1', 1);

        $this->lesson($this->moduleA1, 'Lesson A1', 1);
        $this->lesson($this->moduleA1, 'Lesson A2', 2);
        $this->lesson($this->moduleA2, 'Lesson A3', 1);
        $this->lesson($this->moduleB1, 'Lesson Other Course', 1);
        $this->lesson($this->moduleC1, 'Lesson Unenrolled Course', 1);

        // Enrolled in courseA and courseB — enough to tell a course-scoped
        // list apart from the cross-course one.
        $this->enroll($this->courseA);
        $this->enroll($this->courseB);
    }

    private function module(Course $course, string $title, int $position): Module
    {
        return Module::factory()->create([
            'course_id' => $course->id,
            'title' => $title,
            'position' => $position,
            'status' => 'published',
        ]);
    }

    private function lesson(Module $module, string $title, int $position): Lesson
    {
        return Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => $title,
            'position' => $position,
            'status' => 'published',
        ]);
    }

    private function enroll(Course $course): Enrollment
    {
        $class = ClassModel::factory()->create(['course_id' => $course->id, 'status' => 'active']);

        return Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);
    }

    // ── 1. The picker must respect the chosen course ──────────────

    public function test_course_lesson_index_only_lists_that_course_lessons(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.lessons.index', $this->courseA))
            ->assertOk()
            ->assertSee('Lesson A1')
            ->assertSee('Lesson A2')
            ->assertSee('Lesson A3')
            // Enrolled, but a different course — must not leak in.
            ->assertDontSee('Lesson Other Course')
            ->assertDontSee('Lesson Unenrolled Course');
    }

    public function test_course_lesson_index_groups_by_module(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.lessons.index', $this->courseA))
            ->assertOk()
            ->assertSee('Week 1')
            ->assertSee('Week 2');
    }

    public function test_feature_picker_links_to_the_scoped_lesson_route(): void
    {
        $html = $this->actingAs($this->student)
            ->get(route('student.courses.index', ['feature' => 'lessons']))
            ->assertOk()
            ->getContent();

        $this->actingAs($this->student)
            ->get(route('student.courses.lessons.index', $this->courseA))
            ->assertOk();

        // The picker must not point at the cross-course dump any more.
        $this->assertStringContainsString(
            route('student.courses.lessons.index', $this->courseA),
            $html,
            'Lessons picker should link to the course-scoped lessons page.'
        );
        $this->assertStringNotContainsString(
            route('student.lessons.index'),
            $html,
            'Lessons picker should not link to the cross-course lessons page.'
        );
    }

    public function test_cross_course_lesson_list_is_reachable_on_its_own_route(): void
    {
        // The all-lessons page still works — it deliberately spans every
        // enrolled course. It is just no longer where the picker points.
        $this->actingAs($this->student)
            ->get(route('student.lessons.index'))
            ->assertOk()
            ->assertSee('Lesson A1')
            ->assertSee('Lesson Other Course')
            // Never a course the student is not enrolled in.
            ->assertDontSee('Lesson Unenrolled Course');
    }

    public function test_student_cannot_open_lessons_for_a_course_they_are_not_enrolled_in(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.lessons.index', $this->courseC))
            ->assertForbidden();
    }

    // ── 2. Module/lesson/course must agree ────────────────────────

    public function test_lesson_from_another_module_is_not_found(): void
    {
        $foreignLesson = $this->moduleA1->lessons()->first();

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->courseA, $this->moduleA2, $foreignLesson]))
            ->assertNotFound();
    }

    /**
     * The case that actually leaked: the lesson *is* inside {module}, but that
     * module belongs to a different course than the one named in the URL, so
     * the page rendered "CS101 · <other course's module>".
     *
     * Note a global Route::bind('lesson') already scopes lessons to their
     * module, which is why the lesson-level check alone would not catch this.
     */
    public function test_lesson_under_a_module_from_another_course_is_not_found(): void
    {
        $foreignLesson = $this->moduleB1->lessons()->first();

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->courseA, $this->moduleB1, $foreignLesson]));

        $response->assertNotFound();
        $response->assertDontSee('Lesson Other Course');
        $response->assertDontSee('Week 1');
    }

    public function test_matching_lesson_under_another_course_module_is_still_shown_in_its_own_course(): void
    {
        // The same lesson IS reachable — under course B, where it really lives.
        $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->courseB, $this->moduleB1, $this->moduleB1->lessons()->first()]))
            ->assertOk()
            ->assertSee('Lesson Other Course');
    }

    public function test_lesson_from_another_course_is_not_found(): void
    {
        $foreignLesson = $this->moduleB1->lessons()->first();

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->courseA, $this->moduleA1, $foreignLesson]))
            ->assertNotFound();
    }

    public function test_module_from_another_course_is_not_found(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.courses.modules.show', [$this->courseA, $this->moduleB1]))
            ->assertNotFound();
    }

    public function test_module_page_still_renders_for_a_module_without_lessons(): void
    {
        $empty = $this->module($this->courseA, 'Week 3 (no lessons)', 3);

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.show', [$this->courseA, $empty]))
            ->assertOk()
            ->assertSee('Week 3 (no lessons)');
    }

    public function test_matching_course_module_and_lesson_still_resolves(): void
    {
        $lesson = $this->moduleA1->lessons()->first();

        $this->actingAs($this->student)
            ->get(route('student.courses.modules.lessons.show', [$this->courseA, $this->moduleA1, $lesson]))
            ->assertOk()
            ->assertSee('Lesson A1')
            ->assertSee('Week 1');
    }

    public function test_mark_complete_rejects_a_mismatched_lesson(): void
    {
        $foreignLesson = $this->moduleA2->lessons()->first();

        $this->actingAs($this->student)
            ->post(route('student.courses.modules.lessons.complete', [$this->courseA, $this->moduleA1, $foreignLesson]))
            ->assertNotFound();
    }
}
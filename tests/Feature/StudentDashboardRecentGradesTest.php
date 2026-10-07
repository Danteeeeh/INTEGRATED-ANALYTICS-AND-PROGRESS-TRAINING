<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Student dashboard "Recent grades" showed exam grades for courses the student
 * was not enrolled in, which read as "there are exams" when none had been
 * created.
 *
 * The panel must only ever surface grades whose class the student is actively
 * enrolled in, and must not surface unreleased items.
 */
class StudentDashboardRecentGradesTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $enrolledCourse;

    private Course $otherCourse;

    private ClassModel $enrolledClass;

    private ClassModel $otherClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->enrolledCourse = Course::factory()->create(['title' => 'Mathematics in the Modern World']);
        $this->otherCourse = Course::factory()->create(['title' => 'Information Management']);

        $this->enrolledClass = ClassModel::factory()->create([
            'course_id' => $this->enrolledCourse->id,
            'status' => 'active',
        ]);

        $this->otherClass = ClassModel::factory()->create([
            'course_id' => $this->otherCourse->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->enrolledClass->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(10),
        ]);
    }

    private function gradeIn(ClassModel $class, string $title, string $type = 'exam'): Grade
    {
        $item = GradeItem::create([
            'class_id' => $class->id,
            'title' => $title,
            'description' => $title,
            'max_points' => 100,
            'factor' => 0.3,
            'item_type' => $type,
            'position' => 0,
            'is_released' => true,
            'released_at' => now()->subDay(),
        ]);

        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => 80,
            'score_percent' => 80,
            'letter_grade' => 'B',
            'graded_at' => now()->subDay(),
        ]);
    }

    /** @return list<string> titles shown in the Recent grades panel */
    private function visibleTitles(): array
    {
        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertOk();

        // Read the panel straight out of the query the page used, so the
        // assertion tracks the real data rather than the markup.
        $titles = [];

        foreach ($response->viewData('stats')['recent_grades'] ?? [] as $grade) {
            $titles[] = $grade->item?->title;
        }

        return $titles;
    }

    public function test_grade_for_a_class_the_student_is_not_enrolled_in_is_hidden(): void
    {
        $this->gradeIn($this->otherClass, 'Final Exam');

        $this->assertSame([], $this->visibleTitles(), 'Grades from an unenrolled class must not leak onto the dashboard.');
    }

    public function test_unreleased_item_is_hidden(): void
    {
        $item = GradeItem::create([
            'class_id' => $this->enrolledClass->id,
            'title' => 'Preliminary Exam',
            'max_points' => 100,
            'factor' => 0.3,
            'item_type' => 'exam',
            'position' => 0,
            'is_released' => false,
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => 90,
            'score_percent' => 90,
            'graded_at' => now(),
        ]);

        $this->assertSame([], $this->visibleTitles(), 'An unreleased item must not appear.');
    }

    public function test_grade_for_an_enrolled_class_is_shown(): void
    {
        $this->gradeIn($this->enrolledClass, 'Assignment Average', 'assignment');

        $this->assertSame(['Assignment Average'], $this->visibleTitles());
    }

    public function test_grade_from_a_dropped_enrollment_is_hidden(): void
    {
        // Enrolled, then dropped: the panel follows the enrollment, not the row.
        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->otherClass->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(10),
        ]);

        $this->gradeIn($this->otherClass, 'Midterm Exam');

        $this->assertContains('Midterm Exam', $this->visibleTitles());

        Enrollment::where('student_id', $this->student->id)
            ->where('class_id', $this->otherClass->id)
            ->update(['status' => 'dropped']);

        $this->assertSame(
            [],
            $this->visibleTitles(),
            'A dropped enrollment must remove the grade from the panel.'
        );
    }
}
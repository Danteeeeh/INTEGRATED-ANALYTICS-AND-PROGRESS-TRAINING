<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use App\Services\ContentProgressService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorClassPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->course = Course::factory()->create(['status' => 'published']);
    }

    protected function makeClass(): ClassModel
    {
        return ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
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

    protected function enrol(ClassModel $class, User $student, string $status): Enrollment
    {
        return Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);
    }

    protected function gradeFor(ClassModel $class, User $student, float $points, float $max, bool $released = true): void
    {
        $item = GradeItem::create([
            'class_id' => $class->id,
            'title' => 'Week 1',
            'item_type' => 'assignment',
            'max_points' => $max,
            'is_released' => $released,
            'released_at' => $released ? now() : null,
            'position' => 0,
        ]);

        Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $student->id,
            'points' => $points,
            'score_percent' => $points > 0 ? round($points / $max * 100, 2) : 0,
        ]);
    }

    /**
     * The screenshot showed "0 students" next to a non-zero average grade.
     * Grades must come from the same active students the row counts.
     */
    public function test_dropped_students_are_excluded_from_the_class_average(): void
    {
        $class = $this->makeClass();

        // All enrolled students dropped — the class has no active roster.
        foreach (range(1, 3) as $i) {
            $student = $this->makeStudent();
            $this->enrol($class, $student, 'dropped');
            $this->gradeFor($class, $student, 88.4, 100);
        }

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk();

        $stats = $response->viewData('stats');
        $row = collect($stats['class_performance'])->first(fn ($r) => $r['class']->id === $class->id);

        $this->assertNotNull($row);
        $this->assertSame(0, $row['active_students'], 'Row should report no active students.');
        $this->assertSame(0.0, (float) $row['average_grade'], 'Dropped students must not influence the average.');
        $this->assertSame(0, $row['graded_students']);
    }

    public function test_active_students_drive_the_class_average(): void
    {
        $class = $this->makeClass();

        foreach ([[90.0, 'active'], [70.0, 'active'], [100.0, 'dropped']] as [$score, $status]) {
            $student = $this->makeStudent();
            $this->enrol($class, $student, $status);
            $this->gradeFor($class, $student, $score, 100);
        }

        $stats = $this->actingAs($this->instructor)
            ->get('/instructor/dashboard')
            ->viewData('stats');

        $row = collect($stats['class_performance'])->first(fn ($r) => $r['class']->id === $class->id);

        // (90 + 70) / 2 active students = 80; the dropped 100 is excluded.
        $this->assertSame(2, $row['active_students']);
        $this->assertSame(80.0, (float) $row['average_grade']);
        $this->assertSame(2, $row['graded_students']);
    }

    /**
     * A course with only modules and lessons must not be capped at 50%.
     */
    public function test_progress_only_averages_categories_that_have_content(): void
    {
        $service = app(ContentProgressService::class);
        $student = $this->makeStudent();

        // No modules, lessons, assignments or quizzes exist at all.
        $this->assertSame(0.0, $service->calculateCourseLiveProgress($this->course, $student->id)['overall']);
    }

    public function test_grade_distribution_ignores_dropped_enrollments(): void
    {
        $class = $this->makeClass();

        foreach ([[95.0, 'active'], [55.0, 'active'], [88.0, 'dropped']] as [$score, $status]) {
            $student = $this->makeStudent();
            $this->enrol($class, $student, $status);
            $this->gradeFor($class, $student, $score, 100);
        }

        $stats = $this->actingAs($this->instructor)
            ->get('/instructor/dashboard')
            ->viewData('stats');

        // Summaries feeding the doughnut are scoped to active students only.
        $graded = collect($stats['class_summaries'][$class->id] ?? [])
            ->where('is_graded', true);

        $this->assertCount(2, $graded, 'Only active students feed the grade distribution.');

        $letters = $graded->pluck('letter_grade')->countBy();

        $this->assertSame(1, $letters['A'] ?? 0);
        $this->assertSame(1, $letters['F'] ?? 0);
    }
}
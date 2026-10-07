<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "My Performance Analytics" blended every enrolled class into one series, so
 * the Grade Trend axis mixed "Class Participation" and "Final Exam" from
 * different courses and the average was meaningless.
 *
 * The panel must be able to scope to a single class, must label items with the
 * course they belong to when showing all of them, and the "Exam Performance"
 * option the dropdown offers must not silently fall back to grades.
 */
class StudentAnalyticsScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private ClassModel $mathClass;

    private ClassModel $itClass;

    private ClassModel $notEnrolledClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->mathClass = $this->classFor('Mathematics in the Modern World');
        $this->itClass = $this->classFor('Information Management');
        $this->notEnrolledClass = $this->classFor('Unenrolled Course');

        foreach ([$this->mathClass, $this->itClass] as $class) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'enrolled_at' => now()->subDays(5),
            ]);
        }
    }

    private function classFor(string $courseTitle): ClassModel
    {
        return ClassModel::factory()->create([
            'course_id' => Course::factory()->create(['title' => $courseTitle])->id,
            'status' => 'active',
        ]);
    }

    private function gradeIn(ClassModel $class, string $title, float $percent): Grade
    {
        $item = GradeItem::create([
            'class_id' => $class->id,
            'title' => $title,
            'max_points' => 100,
            'factor' => 0.3,
            'item_type' => 'exam',
            'position' => 0,
            'is_released' => true,
        ]);

        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $this->student->id,
            'points' => $percent,
            'score_percent' => $percent,
            'graded_at' => now()->subDays(2),
        ]);
    }

    /** @return array<string, mixed> */
    private function analytics(array $query = []): array
    {
        $response = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.analytics').'?'.http_build_query($query));

        $response->assertOk();

        return $response->json('data');
    }

    public function test_class_filter_scopes_the_trend_to_one_class(): void
    {
        $this->gradeIn($this->mathClass, 'Midterm Exam', 74);
        $this->gradeIn($this->itClass, 'Final Exam', 90);

        $data = $this->analytics(['type' => 'grades', 'class_id' => $this->mathClass->id]);

        $this->assertSame(['Midterm Exam'], $data['labels']);
        $this->assertSame(1, $data['total_grades']);
        // JSON drops the trailing ".0", so compare numerically.
        $this->assertEqualsWithDelta(74.0, $data['average'], 0.01);
    }

    public function test_average_reflects_only_the_selected_class(): void
    {
        $this->gradeIn($this->mathClass, 'A', 50);
        $this->gradeIn($this->mathClass, 'B', 70);
        $this->gradeIn($this->itClass, 'C', 100);

        $data = $this->analytics(['type' => 'grades', 'class_id' => $this->mathClass->id]);

        $this->assertEqualsWithDelta(60.0, $data['average'], 0.01, 'Average must not blend another class in.');
    }

    public function test_all_classes_view_labels_each_item_with_its_course(): void
    {
        $this->gradeIn($this->mathClass, 'Midterm Exam', 74);
        $this->gradeIn($this->itClass, 'Final Exam', 90);

        $data = $this->analytics(['type' => 'grades']);

        // Without course context the two items are indistinguishable.
        $this->assertCount(2, $data['labels']);

        foreach ($data['labels'] as $label) {
            $this->assertMatchesRegularExpression(
                '/(Mathematics in the Modern World|Information Management)/',
                $label,
                'Each label must name the course it came from.'
            );
        }
    }

    public function test_grade_from_an_unenrolled_class_is_excluded(): void
    {
        $this->gradeIn($this->notEnrolledClass, 'Hidden Exam', 99);

        $data = $this->analytics(['type' => 'grades']);

        $this->assertSame(0, $data['total_grades']);
        $this->assertNotContains('Hidden Exam', $data['labels']);
    }

    public function test_requesting_a_class_the_student_does_not_enroll_in_is_rejected(): void
    {
        $this->gradeIn($this->notEnrolledClass, 'Hidden Exam', 99);

        $this->actingAs($this->student)
            ->getJson(route('student.dashboard.analytics').'?type=grades&class_id='.$this->notEnrolledClass->id)
            ->assertStatus(422);
    }

    public function test_exams_type_returns_exam_results_and_does_not_fall_back_to_grades(): void
    {
        $exam = Exam::create([
            'class_id' => $this->mathClass->id,
            'course_id' => $this->mathClass->course_id,
            'title' => 'Midterm',
            'slug' => 'midterm-'.$this->mathClass->id,
            'exam_type' => 'midterm',
            'status' => 'published',
        ]);

        ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'status' => 'graded',
            'score_percent' => 88,
            'started_at' => now()->subDays(3),
            'submitted_at' => now()->subDays(3),
        ]);

        // A grade item that must NOT leak into the exam chart.
        $this->gradeIn($this->mathClass, 'Assignment Average', 55);

        $data = $this->analytics(['type' => 'exams']);

        $this->assertArrayHasKey('total_attempts', $data, 'The exams branch must run, not fall back to grades.');
        $this->assertSame(1, $data['total_attempts']);
        // Axis labels are length-capped, so match on the start of the label.
        $this->assertNotEmpty(array_filter(
            $data['labels'],
            fn ($l) => str_starts_with($l, 'Midterm')
        ), 'The graded exam should appear on the chart.');

        $this->assertNotContains('Assignment Average', $data['labels']);
    }

    /**
 * The panel must be per-account. A second student's attempts must never appear
 * on someone else's dashboard.
 */
public function test_another_students_quiz_attempts_never_appear(): void
    {
        $other = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $other->id,
            'class_id' => $this->itClass->id,
            'status' => 'active',
            'enrolled_at' => now()->subDays(5),
        ]);

        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'class_id' => $this->itClass->id,
            'title' => 'Belongs to the other student',
            'slug' => 'other-student-quiz-' . $this->itClass->id,
            'created_by' => $instructor->id,
            'status' => 'published',
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $other->id,
            'attempt_number' => 1,
            'score' => 10,
            'score_percent' => 100,
            'status' => 'graded',
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);

        $data = $this->analytics(['type' => 'quizzes']);

        $this->assertSame(0, $data['total_attempts'], 'Another student\'s attempt must not leak.');
        $this->assertNotContains('Belongs to the other student', $data['labels']);
    }

public function test_course_progress_is_scoped_to_the_selected_class(): void
    {
        $data = $this->analytics(['type' => 'progress', 'class_id' => $this->mathClass->id]);

        $this->assertSame(
            1,
            $data['total_courses'],
            'Selecting a class must narrow Course Progress to that one course.'
        );
        $this->assertNotContains('Information Management', $data['labels']);
    }

    public function test_quizzes_are_scoped_to_the_selected_class(): void
    {
        $data = $this->analytics(['type' => 'quizzes', 'class_id' => $this->mathClass->id]);

        $this->assertArrayHasKey('total_attempts', $data);
        $this->assertSame(0, $data['total_attempts']);
    }

    /**
     * The scoping is only useful if the page offers it: the dashboard must
     * render a class selector covering exactly the enrolled classes.
     */
    public function test_dashboard_offers_a_class_selector_for_the_enrolled_classes(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('analyticsClass', false);

        // Each enrolled class is offered, and the unenrolled one is not.
        $this->assertStringContainsString('value="'.$this->mathClass->id.'"', $response->getContent());
        $this->assertStringContainsString('value="'.$this->itClass->id.'"', $response->getContent());
        $this->assertStringNotContainsString('value="'.$this->notEnrolledClass->id.'"', $response->getContent());

        // "Exam Performance" must remain a real, distinct option.
        $this->assertStringContainsString('value="exams"', $response->getContent());
    }
}

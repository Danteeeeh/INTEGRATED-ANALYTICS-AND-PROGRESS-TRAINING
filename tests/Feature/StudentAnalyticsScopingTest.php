<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
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
        // Course Progress only charts courses that have content to complete.
        foreach ([$this->mathClass, $this->itClass] as $class) {
            $module = Module::create([
                'course_id' => $class->course_id,
                'title' => 'Module for '.$class->id,
                'status' => Module::STATUS_PUBLISHED,
                'position' => 1,
                'created_by' => $this->student->id,
            ]);
            Lesson::create([
                'module_id' => $module->id,
                'title' => 'Lesson for '.$class->id,
                'lesson_type' => 'text',
                'status' => Lesson::STATUS_PUBLISHED,
                'position' => 1,
                'created_by' => $this->student->id,
            ]);
        }

        $data = $this->analytics(['type' => 'progress', 'class_id' => $this->mathClass->id]);

        $this->assertSame(
            1,
            $data['total_courses'],
            'Selecting a class must narrow Course Progress to that one course.'
        );
        $this->assertNotContains('Information Management', $data['labels']);
    }

    public function test_progress_chart_labels_use_the_course_code(): void
    {
        // Course Progress only charts courses that have content to complete.
        foreach ([$this->mathClass, $this->itClass] as $class) {
            $module = Module::create([
                'course_id' => $class->course_id,
                'title' => 'Module for '.$class->id,
                'status' => Module::STATUS_PUBLISHED,
                'position' => 1,
                'created_by' => $this->student->id,
            ]);
            Lesson::create([
                'module_id' => $module->id,
                'title' => 'Lesson for '.$class->id,
                'lesson_type' => 'text',
                'status' => Lesson::STATUS_PUBLISHED,
                'position' => 1,
                'created_by' => $this->student->id,
            ]);
        }

        $dashboard = $this->actingAs($this->student)->get(route('student.dashboard'));
        $dashboard->assertOk();

        // The bar labels come from the JSON the chart is drawn with, so the
        // code must survive into that payload — not just the course cards.
        foreach ([$this->mathClass, $this->itClass] as $class) {
            $this->assertStringContainsString(
                '"code":"'.$class->course->code.'"',
                $dashboard->getContent(),
                'The progress chart payload must carry the course code.'
            );
        }

        $data = $this->analytics(['type' => 'progress']);

        $this->assertCount(2, $data['labels']);
        foreach ($data['labels'] as $label) {
            $this->assertMatchesRegularExpression(
                '/^[A-Z]+ · /',
                $label,
                'A course-code prefix keeps similar course titles apart.'
            );
        }
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

        // Nothing is hidden when the student has no section scope, so the
        // panel has no note to make.
        $this->assertStringNotContainsString('analyticsScopeNote', $response->getContent());
    }

    /**
     * The selector only offers classes inside the student's section. An active
     * enrolment in another block's class used to sit in the dropdown waiting to
     * be picked, and picking it could only ever produce the "not part of your
     * section" notice — the notice stays a fallback for stale or hand-made
     * requests rather than a choice the panel itself hands the student.
     */
    public function test_class_selector_omits_classes_outside_the_students_section(): void
    {
        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $section->id]);
        $this->mathClass->update(['section_id' => $section->id]);
        $this->itClass->update(['section_id' => $otherSection->id]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $html = $response->getContent();

        // The section's own class is offered as a filter…
        $this->assertStringContainsString('<option value="'.$this->mathClass->id.'">', $html);

        // …the other block's is not, even though the enrolment is active.
        $this->assertStringNotContainsString(
            '<option value="'.$this->itClass->id.'">',
            $html,
            "A class outside the student's section must not be offered by the analytics selector."
        );

        $stats = $response->viewData('stats');

        // The hidden class is gone from the whole page now: the course cards
        // and every count read the same scoped list, so there is no longer a
        // courses panel disagreeing with the dropdown.
        $this->assertCount(1, $stats['my_enrollments']);
        $this->assertCount(1, $stats['analytics_enrollments']);
        $this->assertSame(1, $stats['my_courses']);

        // …and its course is not drawn as a card either.
        $this->assertStringNotContainsString('Information Management', $html);

        // The panel says which class was left out, so the shorter list does not
        // read as a missing class.
        $this->assertStringContainsString('id="analyticsScopeNote"', $html);
        $this->assertMatchesRegularExpression(
            '/id="analyticsScopeNote".*'.preg_quote($this->itClass->course->code, '/').'/s',
            $html,
            'The note must name the class it hides, by course code.'
        );
        $this->assertCount(
            1,
            $stats['analytics_hidden_classes'],
            'Only the out-of-section class should be reported as hidden.'
        );

        // A stale or hand-made request still answers with the explanation
        // instead of an unexplained empty chart; that is the fallback.
        $fallback = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.analytics').'?type=grades&class_id='.$this->itClass->id)
            ->assertOk();

        $this->assertStringContainsString('section', (string) $fallback->json('notice'));
    }

    /**
     * The Grade Trend is drawn under the Average Grade tile, so it has to read
     * the same section-scoped grade set. A stale AA enrolment pointing at
     * another block's class used to put that class's points on the chart while
     * the tile above it kept ignoring them.
     */
    public function test_grade_trend_ignores_another_sections_grades_like_the_tile(): void
    {
        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $section->id]);
        $this->mathClass->update(['section_id' => $section->id]);
        $this->itClass->update(['section_id' => $otherSection->id]);

        $this->gradeIn($this->mathClass, 'Own Section Exam', 80);
        $this->gradeIn($this->itClass, 'Other Section Exam', 20);

        $dashboard = $this->actingAs($this->student)->get(route('student.dashboard'));
        $dashboard->assertOk();
        $stats = $dashboard->viewData('stats');

        $data = $this->analytics(['type' => 'grades']);

        $this->assertSame(
            $stats['total_grades'],
            $data['total_grades'],
            'The tile and the chart must count the same grades.'
        );
        $this->assertEqualsWithDelta(
            (float) $stats['average_grade'],
            (float) $data['average'],
            0.01,
            'The chart must plot the same average the tile shows.'
        );
        $this->assertSame(1, $data['total_grades']);
        $this->assertNotContains('Other Section Exam', $data['labels']);

        // Asking for the other block's class by id cannot reveal its grades
        // either — the class is enrolled, so it is not a rejection case, it is
        // simply out of the student's section scope. The panel is told why, so
        // the empty chart is not left looking like a broken one.
        $outOfScope = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.analytics').'?type=grades&class_id='.$this->itClass->id)
            ->assertOk();

        $this->assertSame(0, $outOfScope->json('data.total_grades'));
        $this->assertSame([], $outOfScope->json('data.labels'));
        $this->assertStringContainsString('section', (string) $outOfScope->json('notice'));

        // An in-scope answer carries no excuse.
        $inScope = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.analytics').'?type=grades')
            ->assertOk();

        $this->assertNull($inScope->json('notice'));
    }

    /**
     * The endpoint is asked for a class the student's section does not own, so
     * every chart type has nothing to plot — and every type has to say so
     * rather than draw the old zero-height "No data" bar.
     */
    public function test_an_empty_selection_is_explained_for_every_chart_type(): void
    {
        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $section->id]);
        $this->mathClass->update(['section_id' => $section->id]);
        $this->itClass->update(['section_id' => $otherSection->id]);

        $this->gradeIn($this->itClass, 'Hidden Exam', 99);

        $counts = [
            'grades' => 'total_grades',
            'quizzes' => 'total_attempts',
            'exams' => 'total_attempts',
            'progress' => 'total_courses',
        ];

        foreach ($counts as $type => $key) {
            $response = $this->actingAs($this->student)
                ->getJson(route('student.dashboard.analytics').'?type='.$type.'&class_id='.$this->itClass->id)
                ->assertOk();

            $this->assertSame(0, $response->json('data.'.$key), $type.' should have nothing to plot here.');
            $this->assertStringContainsString(
                'section',
                (string) $response->json('notice'),
                $type.' must explain why the chart is empty.'
            );
        }
    }

    /**
     * A chart with nothing in it must not be drawn as a 0% bar — the panel
     * shows the reason instead, from the endpoint's notice or from its own
     * wording.
     */
    public function test_analytics_panel_explains_an_empty_selection_instead_of_drawing_a_bar(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('analyticsEmptyHtml', $html, 'The panel needs an empty state to show.');
        $this->assertStringContainsString('displayAnalytics(data.data, type, data.notice)', $html, 'The endpoint notice must reach the panel.');
        $this->assertStringNotContainsString(
            "['No data']",
            $html,
            'An empty series must not be drawn as a single 0% bar.'
        );
        $this->assertStringContainsString('No finished quiz attempts in this selection yet.', $html);
    }

    /**
     * Quiz Performance is drawn from the same section-scoped quiz set as the
     * Quiz Progress tile. Matching class_id was not enough: a quiz sitting in
     * another section's module belongs to that block, not to this student.
     */
    public function test_quiz_chart_ignores_a_quiz_in_another_sections_module(): void
    {
        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $section->id]);
        $this->mathClass->update(['section_id' => $section->id]);

        $assignedModule = $this->moduleFor($this->mathClass, 'Assigned module', 1);
        $otherModule = $this->moduleFor($this->mathClass, 'Other section module', 2);

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $assignedModule->id,
            'course_id' => $this->mathClass->course_id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $this->mathClass->course_id,
            'status' => 'active',
        ]);

        $this->quizAttemptIn($this->mathClass, $assignedModule, 'Own section quiz', 70);
        $this->quizAttemptIn($this->mathClass, $otherModule, 'Other section quiz', 30);

        $data = $this->analytics(['type' => 'quizzes']);

        $this->assertSame(
            1,
            $data['total_attempts'],
            'Only the quiz inside the module assigned to this section counts.'
        );
        $this->assertNotContains('Other section quiz', $data['labels']);
        $this->assertEqualsWithDelta(70.0, $data['average'], 0.01);
    }

    private function moduleFor(ClassModel $class, string $title, int $position): Module
    {
        return Module::create([
            'course_id' => $class->course_id,
            'title' => $title,
            'status' => Module::STATUS_PUBLISHED,
            'position' => $position,
            'created_by' => $this->student->id,
        ]);
    }

    private function quizAttemptIn(ClassModel $class, Module $module, string $title, float $percent): QuizAttempt
    {
        $instructor = User::factory()->create([
            'role_id' => Role::where('slug', 'instructor')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $quiz = Quiz::create([
            'class_id' => $class->id,
            'module_id' => $module->id,
            'title' => $title,
            'slug' => 'quiz-'.uniqid(),
            'created_by' => $instructor->id,
            'status' => 'published',
        ]);

        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'score' => $percent,
            'score_percent' => $percent,
            'status' => 'graded',
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard search is a browse surface, but it reads the student's own
 * records — so it follows the section scope the tiles and charts already use.
 * An active enrolment in another block's class (transfers, AA drift) must not
 * put that block's courses, grades, quizzes, exams, assignments or lessons into
 * the results, and a module/assignment/quiz/lesson/exam living in another
 * section's module belongs to that block.
 */
class StudentSearchScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Section $ownSection;

    private Section $otherSection;

    private Course $course;

    private ClassModel $ownClass;

    private ClassModel $otherSectionClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->ownSection = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $this->otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $this->ownSection->id]);

        // One course, two sections: the student sits the Section A class, but a
        // stale enrolment also points at the Section B class of another course.
        $this->course = Course::factory()->create(['title' => 'Shared Course', 'status' => 'published']);

        $this->ownClass = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $this->ownSection->id,
        ]);

        $otherCourse = Course::factory()->create(['title' => 'Other Block Course', 'status' => 'published']);

        $this->otherSectionClass = ClassModel::factory()->create([
            'course_id' => $otherCourse->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $this->otherSection->id,
        ]);

        foreach ([$this->ownClass, $this->otherSectionClass] as $class) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'class_id' => $class->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now()->subDays(5),
            ]);
        }
    }

    /**
     * A published module of the shared course, assigned to one section only.
     */
    private function moduleAssignedTo(Section $section, string $title, int $position): Module
    {
        $module = Module::create([
            'course_id' => $this->course->id,
            'title' => $title,
            'status' => Module::STATUS_PUBLISHED,
            'position' => $position,
            'created_by' => $this->instructor->id,
        ]);

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $module->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        return $module;
    }

    private function lessonIn(Module $module, string $title): Lesson
    {
        return Lesson::create([
            'module_id' => $module->id,
            'title' => $title,
            'lesson_type' => 'text',
            'status' => Lesson::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $this->instructor->id,
        ]);
    }

    private function assignmentIn(ClassModel $class, ?Module $module, string $title): Assignment
    {
        return Assignment::create([
            'class_id' => $class->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'created_by' => $this->instructor->id,
        ]);
    }

    private function quizIn(?Module $module, string $title, ?ClassModel $class = null): Quiz
    {
        return Quiz::create([
            'class_id' => ($class ?? $this->ownClass)->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'quiz-'.uniqid(),
            'created_by' => $this->instructor->id,
            'status' => 'published',
        ]);
    }

    private function examIn(?Module $module, string $title, ?ClassModel $class = null): Exam
    {
        // exams.class_id is not nullable, so even a module exam carries one;
        // the module it belongs to is what decides whether it is this
        // student's exam.
        return Exam::create([
            'class_id' => ($class ?? $this->ownClass)->id,
            'course_id' => $this->course->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'exam-'.uniqid(),
            'exam_type' => 'midterm',
            'status' => 'published',
            'starts_at' => now()->addDays(3),
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

    /** @return array<int, array<string, mixed>> */
    private function search(string $type, string $query): array
    {
        $response = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.search').'?query='.urlencode($query).'&type='.$type);

        $response->assertOk();

        return $response->json('data');
    }

    /**
     * The result titles, sorted so the assertions do not depend on the row
     * order the database happens to return.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, string>
     */
    private function titles(array $results): array
    {
        // Grades are titled `name` in the payload, everything else `title`.
        $titles = array_values(array_filter(array_map(
            fn ($result) => $result['title'] ?? $result['name'] ?? null,
            $results
        )));
        sort($titles);

        return $titles;
    }

    public function test_grades_search_is_limited_to_the_students_section(): void
    {
        $this->gradeIn($this->ownClass, 'Own Section Midterm', 80);
        $this->gradeIn($this->otherSectionClass, 'Other Section Final', 20);

        $this->assertSame(
            ['Own Section Midterm'],
            $this->titles($this->search('grades', 'Section')),
            'Only the section\'s own grades belong in the results.'
        );

        // Searching the other block's title must not reveal it either.
        $this->assertSame([], $this->titles($this->search('grades', 'Other Section Final')));
    }

    public function test_quizzes_search_ignores_a_quiz_in_another_sections_module(): void
    {
        $ownModule = $this->moduleAssignedTo($this->ownSection, 'Assigned module', 1);
        $otherModule = $this->moduleAssignedTo($this->otherSection, 'Other section module', 2);

        $this->quizIn($ownModule, 'Own Section Quiz');
        $this->quizIn($otherModule, 'Other Section Quiz');

        $this->assertSame(['Own Section Quiz'], $this->titles($this->search('quizzes', 'Section Quiz')));
    }

    public function test_lessons_search_ignores_a_lesson_in_another_sections_module(): void
    {
        $ownModule = $this->moduleAssignedTo($this->ownSection, 'Assigned module', 1);
        $otherModule = $this->moduleAssignedTo($this->otherSection, 'Other section module', 2);

        $this->lessonIn($ownModule, 'Own Section Lesson');
        $this->lessonIn($otherModule, 'Other Section Lesson');

        $this->assertSame(['Own Section Lesson'], $this->titles($this->search('lessons', 'Section Lesson')));
    }

    public function test_exams_are_searchable_and_limited_to_the_sections_modules(): void
    {
        $ownModule = $this->moduleAssignedTo($this->ownSection, 'Assigned module', 1);
        $otherModule = $this->moduleAssignedTo($this->otherSection, 'Other section module', 2);

        $this->examIn($ownModule, 'Own Section Module Exam');
        $this->examIn($otherModule, 'Other Section Module Exam');
        $this->examIn(null, 'Own Class Final Exam');

        // The dropdown has always offered "Exams"; before this, the type was
        // rejected by the allow-list and the request failed outright.
        $results = $this->search('exams', 'Exam');

        $this->assertSame(
            ['Own Class Final Exam', 'Own Section Module Exam'],
            $this->titles($results),
            'A class-level exam stays searchable; another section\'s module exam does not.'
        );

        // The panel badges an exam by this field, so it has to be present.
        $this->assertNotNull($results[0]['starts_at'] ?? null);
    }

    public function test_courses_search_stops_at_the_students_section(): void
    {
        // The stale enrolment points at "Other Block Course", but that class
        // belongs to another section, so the course is not this student's to
        // find — not even by the code the two courses could share.
        $this->assertSame(['Shared Course'], $this->titles($this->search('courses', 'Course')));
        $this->assertSame([], $this->titles($this->search('courses', 'Other Block')));
    }

    public function test_modules_search_is_limited_to_the_sections_assigned_modules(): void
    {
        // Two modules of the same course, one per section. A course-wide module
        // search would return both and let the other block's work show up under
        // this student's course.
        $this->moduleAssignedTo($this->ownSection, 'Own Section Module', 1);
        $this->moduleAssignedTo($this->otherSection, 'Other Section Module', 2);

        $this->assertSame(['Own Section Module'], $this->titles($this->search('modules', 'Module')));
    }

    public function test_assignments_search_is_limited_to_the_sections_assigned_modules(): void
    {
        $ownModule = $this->moduleAssignedTo($this->ownSection, 'Assigned module', 1);
        $otherModule = $this->moduleAssignedTo($this->otherSection, 'Other section module', 2);

        $this->assignmentIn($this->ownClass, $ownModule, 'Own Section Assignment');
        $this->assignmentIn($this->ownClass, $otherModule, 'Other Section Assignment');

        $this->assertSame(
            ['Own Section Assignment'],
            $this->titles($this->search('assignments', 'Section Assignment')),
            'Work hanging off another section\'s module is not this student\'s assignment.'
        );
    }

    public function test_class_level_content_in_another_sections_class_does_not_surface(): void
    {
        // These rows name a hidden class and no module, so the module filter
        // cannot catch them: the class list itself has to be narrowed first.
        $this->assignmentIn($this->otherSectionClass, null, 'Hidden Block Assignment');
        $this->quizIn(null, 'Hidden Block Quiz', $this->otherSectionClass);
        $this->examIn(null, 'Hidden Block Exam', $this->otherSectionClass);

        $this->assertSame([], $this->titles($this->search('assignments', 'Hidden Block')));
        $this->assertSame([], $this->titles($this->search('quizzes', 'Hidden Block')));
        $this->assertSame([], $this->titles($this->search('exams', 'Hidden Block')));
    }

    public function test_search_results_carry_the_course_and_class_context(): void
    {
        $this->gradeIn($this->ownClass, 'Own Section Midterm', 80);

        $result = $this->search('grades', 'Midterm')[0] ?? null;

        $this->assertNotNull($result, 'The scoped search must still return the student\'s own rows.');
        $this->assertSame($this->ownClass->code, $result['class_code']);
        $this->assertSame('Shared Course', $result['course_title']);
    }

    public function test_a_student_without_a_section_keeps_the_full_enrolled_set(): void
    {
        // Nothing to narrow against: the same fallback the grade tile uses.
        $this->student->update(['section_id' => null]);

        $this->gradeIn($this->ownClass, 'Own Section Midterm', 80);
        $this->gradeIn($this->otherSectionClass, 'Other Section Final', 20);

        $this->assertSame(
            ['Other Section Final', 'Own Section Midterm'],
            $this->titles($this->search('grades', 'Section'))
        );
    }
}

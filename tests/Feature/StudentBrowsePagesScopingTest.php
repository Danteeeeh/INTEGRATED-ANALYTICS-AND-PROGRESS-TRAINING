<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard, My Progress and search already showed only the student's
 * section's work, but the browse pages still built their own queries. A class
 * of the same course that belongs to another block could therefore stock the
 * assignment/quiz/exam lists, inflate the course overview counters, fill the
 * class page's upcoming activities and drop events onto the calendar.
 *
 * They now all start from the shared section-scoped helpers.
 */
class StudentBrowsePagesScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Section $ownSection;

    private Course $course;

    private ClassModel $ownClass;

    private ClassModel $otherClass;

    private Module $ownModule;

    private Module $hiddenModule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->instructor()->create(['status' => 'active']);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->ownSection = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $this->ownSection->id]);

        $this->course = Course::factory()->create([
            'title' => 'Shared Course',
            'status' => 'published',
        ]);

        $this->ownClass = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $this->ownSection->id,
        ]);

        $this->otherClass = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $otherSection->id,
        ]);

        // Enrolled in both; only the section decides which work is theirs.
        foreach ([$this->ownClass, $this->otherClass] as $class) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'class_id' => $class->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now()->subDays(5),
            ]);
        }

        $this->ownModule = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Own Section Module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $this->instructor->id,
        ]);

        $this->hiddenModule = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Other Section Module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 2,
            'created_by' => $this->instructor->id,
        ]);

        SectionModuleAssignment::create([
            'section_id' => $this->ownSection->id,
            'module_id' => $this->ownModule->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);

        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $this->hiddenModule->id,
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
    }

    private function assignmentIn(?ClassModel $class, ?Module $module, string $title, $due = null): Assignment
    {
        return Assignment::create([
            'class_id' => $class?->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => $due ?? now()->addDays(2),
            'created_by' => $this->instructor->id,
        ]);
    }

    private function quizIn(?ClassModel $class, ?Module $module, string $title): Quiz
    {
        return Quiz::create([
            'class_id' => $class?->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'quiz-'.uniqid(),
            'created_by' => $this->instructor->id,
            'status' => Quiz::STATUS_PUBLISHED,
            // The calendar only shows quizzes that open this month.
            'availability_from' => now()->addDay(),
        ]);
    }

    private function examIn(?ClassModel $class, ?Module $module, string $title): Exam
    {
        return Exam::create([
            'class_id' => $class?->id,
            'course_id' => $this->course->id,
            'module_id' => $module?->id,
            'title' => $title,
            'slug' => 'exam-'.uniqid(),
            'exam_type' => 'midterm',
            'status' => Exam::STATUS_PUBLISHED,
            'starts_at' => now()->addDays(3),
        ]);
    }

    public function test_course_assignment_list_hides_another_sections_work(): void
    {
        $this->assignmentIn($this->ownClass, null, 'Own Class Assignment');
        $this->assignmentIn($this->ownClass, $this->ownModule, 'Own Module Assignment');
        $this->assignmentIn($this->otherClass, null, 'Other Block Assignment');
        $this->assignmentIn($this->ownClass, $this->hiddenModule, 'Hidden Module Assignment');

        $this->actingAs($this->student)
            ->get(route('student.courses.assignments.index', $this->course))
            ->assertOk()
            ->assertSee('Own Class Assignment')
            ->assertSee('Own Module Assignment')
            ->assertDontSee('Other Block Assignment')
            ->assertDontSee('Hidden Module Assignment');
    }

    public function test_course_quiz_list_hides_another_sections_work(): void
    {
        $this->quizIn($this->ownClass, null, 'Own Class Quiz');
        $this->quizIn($this->ownClass, $this->ownModule, 'Own Module Quiz');
        $this->quizIn($this->otherClass, null, 'Other Block Quiz');
        $this->quizIn($this->ownClass, $this->hiddenModule, 'Hidden Module Quiz');

        $this->actingAs($this->student)
            ->get(route('student.courses.quizzes.index', $this->course))
            ->assertOk()
            ->assertSee('Own Class Quiz')
            ->assertSee('Own Module Quiz')
            ->assertDontSee('Other Block Quiz')
            ->assertDontSee('Hidden Module Quiz');
    }

    public function test_course_exam_list_hides_another_sections_work(): void
    {
        $this->examIn($this->ownClass, null, 'Own Class Exam');
        $this->examIn($this->ownClass, $this->ownModule, 'Own Module Exam');
        $this->examIn($this->otherClass, null, 'Other Block Exam');
        $this->examIn($this->ownClass, $this->hiddenModule, 'Hidden Module Exam');

        $this->actingAs($this->student)
            ->get(route('student.courses.exams.index', $this->course))
            ->assertOk()
            ->assertSee('Own Class Exam')
            ->assertSee('Own Module Exam')
            ->assertDontSee('Other Block Exam')
            ->assertDontSee('Hidden Module Exam');
    }

    public function test_course_overview_counts_only_the_students_section_work(): void
    {
        // One class-level and one own-module item count; the other block's
        // class-level item and the hidden module's item do not.
        $this->assignmentIn($this->ownClass, null, 'Counted Assignment');
        $this->assignmentIn($this->ownClass, $this->ownModule, 'Counted Module Assignment');
        $this->assignmentIn($this->otherClass, null, 'Other Block Assignment');
        $this->assignmentIn($this->ownClass, $this->hiddenModule, 'Hidden Module Assignment');

        $this->quizIn($this->ownClass, null, 'Counted Quiz');
        $this->quizIn($this->ownClass, $this->ownModule, 'Counted Module Quiz');
        $this->quizIn($this->otherClass, null, 'Other Block Quiz');
        $this->quizIn($this->ownClass, $this->hiddenModule, 'Hidden Module Quiz');

        $this->examIn($this->ownClass, null, 'Counted Exam');
        $this->examIn($this->ownClass, $this->ownModule, 'Counted Module Exam');
        $this->examIn($this->otherClass, null, 'Other Block Exam');
        $this->examIn($this->ownClass, $this->hiddenModule, 'Hidden Module Exam');

        $response = $this->actingAs($this->student)
            ->get(route('student.courses.show', $this->course))
            ->assertOk();

        $this->assertSame(2, $response->viewData('assignmentsCount'));
        $this->assertSame(2, $response->viewData('quizzesCount'));
        $this->assertSame(2, $response->viewData('examsCount'));
    }

    public function test_class_page_upcoming_activities_ignore_another_sections_modules(): void
    {
        $this->assignmentIn($this->ownClass, null, 'Own Class Upcoming');
        $this->assignmentIn($this->ownClass, $this->hiddenModule, 'Hidden Module Upcoming');

        $response = $this->actingAs($this->student)
            ->get(route('student.classes.show', $this->ownClass))
            ->assertOk();

        $titles = $response->viewData('upcomingActivities')->pluck('title')->all();

        $this->assertContains('Own Class Upcoming', $titles);
        $this->assertNotContains(
            'Hidden Module Upcoming',
            $titles,
            'A module assigned to another section is not this class\'s upcoming work.'
        );
    }

    public function test_calendar_hides_work_from_another_sections_class(): void
    {
        $this->assignmentIn($this->ownClass, null, 'Own Calendar Assignment');
        $this->assignmentIn($this->otherClass, null, 'Other Block Calendar Assignment');
        $this->assignmentIn($this->ownClass, $this->hiddenModule, 'Hidden Module Calendar Assignment');

        $this->quizIn($this->ownClass, null, 'Own Calendar Quiz');
        $this->quizIn($this->otherClass, null, 'Other Block Calendar Quiz');

        $response = $this->actingAs($this->student)
            ->get(route('student.calendar'))
            ->assertOk();

        $titles = $response->viewData('events')->pluck('title')->all();

        $this->assertContains('Own Calendar Assignment', $titles);
        $this->assertContains('Own Calendar Quiz', $titles);
        $this->assertNotContains('Other Block Calendar Assignment', $titles);
        $this->assertNotContains('Hidden Module Calendar Assignment', $titles);
        $this->assertNotContains('Other Block Calendar Quiz', $titles);
    }
}

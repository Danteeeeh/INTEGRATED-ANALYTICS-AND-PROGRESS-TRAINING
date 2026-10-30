<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use App\Models\VirtualClass;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The dashboard used to narrow only its grades and the analytics filter. Every
 * other tile, course card and panel still read the raw enrolment list, so an
 * active enrolment pointing at another block's class (transfers, AA drift,
 * shared demo data) could be counted in a tile, drawn as a course card, or
 * listed in a panel.
 *
 * The whole page now starts from the same section-scoped class list as search.
 */
class StudentDashboardScopingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private User $instructor;

    private Section $ownSection;

    private Course $ownCourse;

    private Course $hiddenCourse;

    private ClassModel $ownClass;

    private ClassModel $hiddenClass;

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
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $this->student->update(['section_id' => $this->ownSection->id]);

        $this->ownCourse = Course::factory()->create([
            'title' => 'Own Section Course',
            'status' => 'published',
        ]);
        $this->hiddenCourse = Course::factory()->create([
            'title' => 'Hidden Block Course',
            'status' => 'published',
        ]);

        $this->ownClass = ClassModel::factory()->create([
            'course_id' => $this->ownCourse->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $this->ownSection->id,
        ]);

        $this->hiddenClass = ClassModel::factory()->create([
            'course_id' => $this->hiddenCourse->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $otherSection->id,
        ]);

        // The student really is enrolled in both; only the section scope
        // decides that one of them is not theirs to see.
        foreach ([$this->ownClass, $this->hiddenClass] as $class) {
            Enrollment::create([
                'student_id' => $this->student->id,
                'class_id' => $class->id,
                'status' => Enrollment::STATUS_ACTIVE,
                'enrolled_at' => now()->subDays(5),
            ]);
        }
    }

    private function assignmentIn(ClassModel $class, string $title): Assignment
    {
        return Assignment::create([
            'class_id' => $class->id,
            'title' => $title,
            'slug' => 'assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'created_by' => $this->instructor->id,
        ]);
    }

    private function quizIn(ClassModel $class, string $title): Quiz
    {
        return Quiz::create([
            'class_id' => $class->id,
            'title' => $title,
            'slug' => 'quiz-'.uniqid(),
            'created_by' => $this->instructor->id,
            'status' => 'published',
        ]);
    }

    private function examIn(ClassModel $class, string $title): Exam
    {
        return Exam::create([
            'class_id' => $class->id,
            'course_id' => $class->course_id,
            'title' => $title,
            'slug' => 'exam-'.uniqid(),
            'exam_type' => 'midterm',
            'status' => 'published',
            'starts_at' => now()->addDays(3),
        ]);
    }

    private function quizAttempt(Quiz $quiz): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'status' => QuizAttempt::STATUS_SUBMITTED,
        ]);
    }

    private function examAttempt(Exam $exam): ExamAttempt
    {
        return ExamAttempt::create([
            'exam_id' => $exam->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);
    }

    private function announcementIn(ClassModel $class, string $title): Announcement
    {
        return Announcement::create([
            'title' => $title,
            'body' => 'Body for '.$title,
            'audience_type' => Announcement::AUDIENCE_CLASS,
            'class_id' => $class->id,
            'publish_at' => now()->subHour(),
            'status' => Announcement::STATUS_PUBLISHED,
            'created_by' => $this->instructor->id,
        ]);
    }

    private function virtualClassIn(ClassModel $class, string $title): VirtualClass
    {
        return VirtualClass::create([
            'course_id' => $class->course_id,
            'class_id' => $class->id,
            'instructor_id' => $this->instructor->id,
            'title' => $title,
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-class-'.$class->id.'-'.Str::lower(Str::random(20)),
            'status' => VirtualClass::STATUS_SCHEDULED,
            'created_by' => $this->instructor->id,
        ]);
    }

    /** @return array<string, mixed> */
    private function dashboard(): array
    {
        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        return $response->viewData('stats');
    }

    public function test_tiles_and_course_cards_count_only_the_sections_classes(): void
    {
        $this->assignmentIn($this->ownClass, 'Own Section Assignment');
        $this->assignmentIn($this->hiddenClass, 'Hidden Block Assignment');

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');
        $html = $response->getContent();

        $this->assertSame(1, $stats['my_courses'], 'The Enrolled Courses tile must not count a hidden class.');
        $this->assertSame(1, $stats['active_enrollments']);
        $this->assertCount(1, $stats['my_enrollments'], 'The course cards must not list a hidden class.');
        $this->assertSame(1, $stats['total_assignments'], 'A hidden class\'s work must not be counted.');

        // The own card is still drawn…
        $this->assertStringContainsString('Own Section Course', $html);

        // …and the hidden class is gone from the cards, the counts and the
        // chart payload, not merely from the analytics dropdown.
        $this->assertStringNotContainsString('Hidden Block Course', $html);

        // The enrolment has not been deleted, so the page says where it went.
        $this->assertStringContainsString('id="analyticsScopeNote"', $html);
        $this->assertStringContainsString($this->hiddenCourse->code, $html);
    }

    public function test_panels_do_not_list_rows_from_a_hidden_class(): void
    {
        $this->quizAttempt($this->quizIn($this->ownClass, 'Own Section Quiz'));
        $this->quizAttempt($this->quizIn($this->hiddenClass, 'Hidden Block Quiz'));

        $this->examAttempt($this->examIn($this->ownClass, 'Own Section Exam'));
        $this->examAttempt($this->examIn($this->hiddenClass, 'Hidden Block Exam'));

        $this->announcementIn($this->ownClass, 'Own Section Announcement');
        $this->announcementIn($this->hiddenClass, 'Hidden Block Announcement');

        $this->virtualClassIn($this->ownClass, 'Own Section Session');
        $this->virtualClassIn($this->hiddenClass, 'Hidden Block Session');

        $stats = $this->dashboard();

        $this->assertSame(['Own Section Quiz'], $stats['recent_quiz_attempts']->pluck('quiz.title')->all());
        $this->assertSame(['Own Section Exam'], $stats['recent_exam_attempts']->pluck('exam.title')->all());
        $this->assertSame(['Own Section Session'], $stats['upcoming_virtual_classes']->pluck('title')->all());
        $this->assertSame(['Own Section Announcement'], $stats['announcements']->pluck('title')->all());
    }

    public function test_exam_tiles_and_lists_ignore_another_sections_class_of_the_same_course(): void
    {
        // Exams are matched by course id as well as class id, so a class of the
        // same course in another section is where the course-wide query used to
        // bring another block's exam back onto the dashboard.
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $sameCourseHiddenClass = ClassModel::factory()->create([
            'course_id' => $this->ownCourse->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $otherSection->id,
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $sameCourseHiddenClass->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->subDays(5),
        ]);

        $this->examIn($this->ownClass, 'Own Section Exam');
        $this->examIn($sameCourseHiddenClass, 'Hidden Same Course Exam');

        $stats = $this->dashboard();

        $this->assertSame(1, $stats['total_exams'], 'The exam tile must count only the section\'s exams.');
        $this->assertSame(['Own Section Exam'], $stats['upcoming_exams']->pluck('title')->all());

        $activityTitles = $stats['upcoming_activities']->pluck('title')->all();
        $this->assertContains('Own Section Exam', $activityTitles);
        $this->assertNotContains(
            'Hidden Same Course Exam',
            $activityTitles,
            'The combined upcoming list must not carry the hidden exam either.'
        );
    }

    public function test_incomplete_work_page_skips_a_hidden_class(): void
    {
        $this->assignmentIn($this->ownClass, 'Own Section Pending Task');
        $this->assignmentIn($this->hiddenClass, 'Hidden Block Pending Task');

        $this->quizIn($this->ownClass, 'Own Section Open Quiz')
            ->update(['availability_from' => now()->subHour()]);
        $this->quizIn($this->hiddenClass, 'Hidden Block Open Quiz')
            ->update(['availability_from' => now()->subHour()]);

        $this->examIn($this->ownClass, 'Own Section Open Exam')
            ->update(['starts_at' => now()->subHour()]);
        $this->examIn($this->hiddenClass, 'Hidden Block Open Exam')
            ->update(['starts_at' => now()->subHour()]);

        // A second class of the *same* course in another section. Exams are the
        // one query that also matches by course id, so this is where a hidden
        // class would sneak back in if the exam query stayed course-wide.
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $sameCourseHiddenClass = ClassModel::factory()->create([
            'course_id' => $this->ownCourse->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
            'section_id' => $otherSection->id,
        ]);
        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $sameCourseHiddenClass->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now()->subDays(5),
        ]);
        $this->examIn($sameCourseHiddenClass, 'Hidden Same Course Exam')
            ->update(['starts_at' => now()->subHour()]);

        $response = $this->actingAs($this->student)->get(route('student.incomplete'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(
            ['Own Section Pending Task'],
            $response->viewData('incompleteAssignments')->pluck('title')->all()
        );
        $this->assertSame(
            ['Own Section Open Quiz'],
            $response->viewData('incompleteQuizzes')->pluck('title')->all()
        );
        $this->assertSame(
            ['Own Section Open Exam'],
            $response->viewData('incompleteExams')->pluck('title')->all()
        );

        foreach ([
            'Hidden Block Pending Task',
            'Hidden Block Open Quiz',
            'Hidden Block Open Exam',
            'Hidden Same Course Exam',
        ] as $hidden) {
            $this->assertStringNotContainsString(
                $hidden,
                $html,
                'Another block\'s pending work must not be listed on the incomplete page.'
            );
        }

        $this->assertStringContainsString('Own Section Pending Task', $html);
    }

    public function test_real_time_stats_use_the_same_scope_as_the_page(): void
    {
        // This endpoint computes its own overdue list, so it needs the same
        // module scope as the rendered dashboard, not just the class scope.
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $ownModule = Module::create([
            'course_id' => $this->ownCourse->id,
            'title' => 'Own section module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $this->instructor->id,
        ]);
        SectionModuleAssignment::create([
            'section_id' => $this->ownSection->id,
            'module_id' => $ownModule->id,
            'course_id' => $this->ownCourse->id,
            'status' => 'active',
        ]);

        $hiddenModule = Module::create([
            'course_id' => $this->ownCourse->id,
            'title' => 'Other section module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 2,
            'created_by' => $this->instructor->id,
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $hiddenModule->id,
            'course_id' => $this->ownCourse->id,
            'status' => 'active',
        ]);

        Assignment::create([
            'class_id' => $this->ownClass->id,
            'module_id' => $hiddenModule->id,
            'title' => 'Hidden module task',
            'slug' => 'hidden-module-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->subDay(),
            'created_by' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.real-time-stats'))
            ->assertOk();

        $this->assertSame(
            1,
            $response->json('data.my_courses'),
            'The auto-refresh must not patch a hidden class back into the tile.'
        );
        $this->assertSame(
            0,
            $response->json('data.overdue_count'),
            'Work in another section\'s module is not overdue for this student.'
        );
    }

    public function test_a_student_without_a_section_still_sees_every_enrolled_class(): void
    {
        // Nothing to narrow against: the same fallback every other surface uses.
        $this->student->update(['section_id' => null]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');
        $html = $response->getContent();

        $this->assertSame(2, $stats['my_courses']);
        $this->assertCount(2, $stats['my_enrollments']);
        $this->assertStringContainsString('Own Section Course', $html);
        $this->assertStringContainsString('Hidden Block Course', $html);
        $this->assertStringNotContainsString('id="analyticsScopeNote"', $html);
    }
}

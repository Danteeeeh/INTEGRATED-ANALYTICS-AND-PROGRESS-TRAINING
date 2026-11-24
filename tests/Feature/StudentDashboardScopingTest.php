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

    public function test_overdue_hint_is_hidden_when_the_list_fits_the_two_item_cap(): void
    {
        foreach (range(1, 2) as $index) {
            $this->assignmentIn($this->ownClass, "Overdue task $index")
                ->update(['due_date' => now()->subDays($index)]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertOk();
        $response->assertSee('2 overdue items');
        $response->assertDontSee('Showing 2 of 2');
    }

    public function test_overdue_hint_counts_all_items_beyond_the_two_item_cap(): void
    {
        foreach (range(1, 7) as $index) {
            $this->assignmentIn($this->ownClass, "Overdue task $index")
                ->update(['due_date' => now()->subDays($index)]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(7, $stats['overdue_assignments_total']);
        $this->assertCount(5, $stats['overdue_assignments'], 'The overdue query keeps its five-row fetch cap.');

        $response->assertSee('7 overdue items');
        $response->assertSee('Showing 2 of 7');
        $this->assertSame(2, substr_count($response->getContent(), 'class="od-name"'));

        $this->assertSame(
            7,
            $this->actingAs($this->student)
                ->getJson(route('student.dashboard.real-time-stats'))
                ->json('data.overdue_count'),
            'The auto-refresh must keep the full overdue total, not the five-row fetch cap.'
        );
    }

    public function test_upcoming_activities_hint_uses_the_uncapped_total(): void
    {
        foreach (range(1, 5) as $index) {
            $this->assignmentIn($this->ownClass, "Upcoming task $index")
                ->update(['due_date' => now()->addDays($index)]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Showing 5 of 5');
        $this->assertSame(5, $response->viewData('stats')['upcoming_activities_total']);

        $this->assignmentIn($this->ownClass, 'Upcoming task 6')
            ->update(['due_date' => now()->addDays(6)]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertSee('Showing 5 of 6');

        $stats = $response->viewData('stats');
        $this->assertSame(6, $stats['upcoming_activities_total'], 'The hint must count all upcoming activities, not only the five listed.');
        $this->assertCount(5, $stats['upcoming_activities']);
    }

    public function test_announcements_hint_appears_only_after_the_three_item_cap(): void
    {
        foreach (range(1, 3) as $index) {
            $this->announcementIn($this->ownClass, "Announcement $index");
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Showing 3 of 3');

        $stats = $response->viewData('stats');
        $this->assertSame(3, $stats['total_announcements']);
        $this->assertCount(3, $stats['announcements']);

        $this->announcementIn($this->ownClass, 'Announcement 4');

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertSee('Showing 3 of 4');

        $stats = $response->viewData('stats');
        $this->assertSame(4, $stats['total_announcements']);
        $this->assertSame(
            3,
            substr_count($response->getContent(), 'class="announce-item"'),
            'The announcements panel keeps its three-row display cap.'
        );
    }

    public function test_capped_todo_and_announcement_hints_report_their_full_totals(): void
    {
        foreach (range(1, 6) as $index) {
            $this->assignmentIn($this->ownClass, "To-do task $index")
                ->update(['due_date' => now()->addDays($index)]);

            $this->announcementIn($this->ownClass, "Announcement $index");
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertOk();

        $hints = $this->panelShowingHints($response->getContent());
        $this->assertSame(
            ['text' => 'Showing 2 of 6', 'href' => route('student.incomplete')],
            $hints['To-do list'] ?? null,
            'The To-do panel hint must report its own uncapped total.'
        );
        $this->assertSame(
            ['text' => 'Showing 3 of 6', 'href' => route('student.announcements.index')],
            $hints['Announcements'] ?? null,
            'The Announcements panel hint must report its own uncapped total.'
        );

        $stats = $response->viewData('stats');
        $this->assertCount(6, $stats['upcoming_assignments']);
        $this->assertSame(6, $stats['total_announcements']);

        $this->actingAs($this->student)
            ->getJson(route('student.dashboard.real-time-stats'))
            ->assertOk()
            ->assertJsonPath('data.upcoming_tasks', 6);
    }

    public function test_capped_todo_and_announcement_hints_stay_hidden_at_the_boundary(): void
    {
        foreach (range(1, 2) as $index) {
            $this->assignmentIn($this->ownClass, "Boundary to-do $index")
                ->update(['due_date' => now()->addDays($index)]);
        }

        foreach (range(1, 3) as $index) {
            $this->announcementIn($this->ownClass, "Boundary announcement $index");
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $hints = $this->panelShowingHints($response->getContent());
        $this->assertArrayNotHasKey(
            'To-do list',
            $hints,
            'Two upcoming to-dos exactly fill the To-do cap, so the hint must stay hidden.'
        );
        $this->assertArrayNotHasKey(
            'Announcements',
            $hints,
            'Three announcements exactly fill the Announcements cap, so the hint must stay hidden.'
        );

        $stats = $response->viewData('stats');
        $this->assertCount(2, $stats['upcoming_assignments']);
        $this->assertSame(3, $stats['total_announcements']);
    }

    public function test_todo_hint_and_real_time_total_match_for_mixed_upcoming_work(): void
    {
        foreach (range(1, 3) as $index) {
            $this->assignmentIn($this->ownClass, "Mixed to-do assignment $index")
                ->update(['due_date' => now()->addDays($index)]);
        }

        foreach (range(1, 2) as $index) {
            $this->quizIn($this->ownClass, "Mixed to-do quiz $index")
                ->update(['availability_from' => now()->addDays($index)]);
        }

        $this->examIn($this->ownClass, 'Mixed to-do exam');

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();
        $response->assertSee('Showing 5 of 6');

        $stats = $response->viewData('stats');
        $this->assertCount(3, $stats['upcoming_assignments']);
        $this->assertCount(2, $stats['upcoming_quizzes']);
        $this->assertCount(1, $stats['upcoming_exams']);
        $this->assertSame(6, $stats['upcoming_tasks_total']);

        preg_match('/Showing \d+ of (\d+)/', $response->getContent(), $hint);
        $this->assertArrayHasKey(1, $hint, 'The rendered To-do hint should show its uncapped total.');

        $realTimeTotal = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.real-time-stats'))
            ->assertOk()
            ->json('data.upcoming_tasks');

        $this->assertSame(
            (int) $hint[1],
            $realTimeTotal,
            'The real-time count must match the rendered To-do hint for mixed assignments, quizzes and exams.'
        );
    }

    public function test_todo_hint_and_real_time_total_match_when_all_six_tasks_are_quizzes_and_exams(): void
    {
        foreach (range(1, 3) as $index) {
            $this->quizIn($this->ownClass, "Quiz-only to-do $index")
                ->update(['availability_from' => now()->addDays($index)]);

            $this->examIn($this->ownClass, "Exam-only to-do $index")
                ->update(['starts_at' => now()->addDays($index)]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        $hints = $this->panelShowingHints($response->getContent());
        $this->assertSame(
            ['text' => 'Showing 4 of 6', 'href' => route('student.incomplete')],
            $hints['To-do list'] ?? null,
            'All six upcoming items are quizzes and exams, so the To-do hint must report the full total.'
        );

        $stats = $response->viewData('stats');
        $this->assertCount(0, $stats['upcoming_assignments']);
        $this->assertCount(3, $stats['upcoming_quizzes']);
        $this->assertCount(3, $stats['upcoming_exams']);
        $this->assertSame(6, $stats['upcoming_tasks_total']);

        preg_match('/^Showing \d+ of (\d+)$/', $hints['To-do list']['text'], $hint);
        $this->assertArrayHasKey(1, $hint, 'The rendered To-do hint should expose its uncapped total.');

        $realTimeTotal = $this->actingAs($this->student)
            ->getJson(route('student.dashboard.real-time-stats'))
            ->assertOk()
            ->json('data.upcoming_tasks');

        $this->assertSame(
            (int) $hint[1],
            $realTimeTotal,
            'The real-time count must match the rendered To-do hint when all upcoming work is quizzes and exams.'
        );
    }

    public function test_deadline_and_activity_hints_open_the_full_calendar(): void
    {
        foreach (range(1, 6) as $index) {
            $this->assignmentIn($this->ownClass, "Calendar task $index")
                ->update(['due_date' => now()->addDays($index)]);
        }

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));
        $response->assertOk();

        // The deadline panel shows four of six events; the activity panel
        // shows five of six. Both hints must open the complete calendar, not a
        // class or course picker.
        $response->assertSee('Showing 4 of 6');
        $response->assertSee('Showing 5 of 6');
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($response->getContent(), 'href="'.route('student.calendar').'"'),
            'Both upcoming hints should link to the full calendar.'
        );

        $this->actingAs($this->student)
            ->get(route('student.calendar'))
            ->assertOk()
            ->assertSee('Calendar task 1');
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

    /**
     * Return the "Showing X of Y" hint inside each named dashboard panel.
     *
     * @return array<string, array{text: string, href: string}>
     */
    private function panelShowingHints(string $html): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $xpath = new \DOMXPath($dom);
        $hints = [];

        foreach ($xpath->query('//section[contains(concat(" ", normalize-space(@class), " "), " dash-panel ")]') as $panel) {
            $heading = $xpath->query('.//h4', $panel)->item(0);
            $link = $xpath->query('.//a[contains(concat(" ", normalize-space(@class), " "), " panel-cap-note ")]', $panel)->item(0);

            if (! $heading || ! $link) {
                continue;
            }

            $label = preg_replace('/\s+/', ' ', trim($heading->textContent));
            $hints[$label] = [
                'text' => trim($link->textContent),
                'href' => $link->getAttribute('href'),
            ];
        }

        return $hints;
    }
}

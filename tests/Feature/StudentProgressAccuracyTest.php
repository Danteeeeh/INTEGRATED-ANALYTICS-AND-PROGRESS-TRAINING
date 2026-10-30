<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\SectionModuleAssignment;
use App\Models\User;
use App\Services\ContentProgressService;
use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

/**
 * The student dashboard's "My progress" chart and the Analytics → Course
 * Progress graph both read ContentProgressService::calculateCourseLiveProgress.
 *
 * Two numbers were wrong:
 *
 *   - lessons inside draft modules were counted in the denominator but could
 *     never be completed, so every course looked less finished than it was;
 *   - completed assignments counted submission rows, so a resubmission (now
 *     allowed) pushed the category past 100% and skewed the overall average.
 */
class StudentProgressAccuracyTest extends TestCase
{
    use CreatesLmsUsers;

    private function enrollment(): array
    {
        $instructor = $this->makeUser('instructor');
        $student = $this->makeUser('student');

        $course = Course::factory()->create([
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        return compact('instructor', 'student', 'course', 'class');
    }

    private function progress(array $fixture): array
    {
        return app(ContentProgressService::class)
            ->calculateCourseLiveProgress(
                $fixture['course'],
                $fixture['student']->id,
                $fixture['class']->id
            );
    }

    public function test_lessons_in_draft_modules_do_not_count_against_progress(): void
    {
        $f = $this->enrollment();

        $publishedModule = Module::factory()->create([
            'course_id' => $f['course']->id,
            'status' => Module::STATUS_PUBLISHED,
            'created_by' => $f['instructor']->id,
        ]);

        $first = Lesson::factory()->create([
            'module_id' => $publishedModule->id,
            'status' => Lesson::STATUS_PUBLISHED,
            'created_by' => $f['instructor']->id,
        ]);

        Lesson::factory()->create([
            'module_id' => $publishedModule->id,
            'status' => Lesson::STATUS_PUBLISHED,
            'created_by' => $f['instructor']->id,
        ]);

        // A lesson inside a draft module: the student cannot open it, so it must
        // not sit in the denominator.
        $draftModule = Module::factory()->create([
            'course_id' => $f['course']->id,
            'status' => Module::STATUS_DRAFT,
            'created_by' => $f['instructor']->id,
        ]);

        Lesson::factory()->create([
            'module_id' => $draftModule->id,
            'status' => Lesson::STATUS_PUBLISHED,
            'created_by' => $f['instructor']->id,
        ]);

        LessonProgress::create([
            'lesson_id' => $first->id,
            'student_id' => $f['student']->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        $progress = $this->progress($f);

        $this->assertSame(2, $progress['totals']['lessons'], 'Only lessons in published modules should count.');
        $this->assertSame(1, $progress['completed']['lessons']);
        $this->assertEqualsWithDelta(50.0, $progress['lessons'], 0.01);
    }

    public function test_a_resubmission_counts_as_one_completed_assignment(): void
    {
        $f = $this->enrollment();

        $assignment = Assignment::create([
            'class_id' => $f['class']->id,
            'created_by' => $f['instructor']->id,
            'title' => 'Reflection Paper',
            'slug' => 'reflection-'.uniqid(),
            'points' => 100,
            'submission_type' => Assignment::TYPE_TEXT,
            'due_date' => now()->addDay(),
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        foreach ([1, 2] as $attempt) {
            AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $f['student']->id,
                'attempt_number' => $attempt,
                'submission_text' => 'Attempt '.$attempt,
                'submitted_at' => now()->subHour(),
                'status' => $attempt === 1
                    ? AssignmentSubmission::STATUS_SUBMITTED
                    : AssignmentSubmission::STATUS_RESUBMITTED,
            ]);
        }

        $progress = $this->progress($f);

        $this->assertSame(1, $progress['totals']['assignments']);
        $this->assertSame(1, $progress['completed']['assignments'], 'Two attempts are still one completed assignment.');
        $this->assertEqualsWithDelta(100.0, $progress['assignments'], 0.01);
        $this->assertEqualsWithDelta(100.0, $progress['overall'], 0.01, 'Overall progress must not exceed 100%.');
    }

    public function test_a_course_with_nothing_published_is_flagged_rather_than_scored_zero(): void
    {
        $f = $this->enrollment();

        $progress = $this->progress($f);

        $this->assertFalse($progress['has_content'], 'An empty course must not pretend to be 0% complete.');
        $this->assertSame(0, $progress['totals']['modules'] + $progress['totals']['lessons']);
    }

    public function test_learning_streak_counts_quizzes_and_assignments_not_only_lessons(): void
    {
        $f = $this->enrollment();

        $quiz = Quiz::create([
            'class_id' => $f['class']->id,
            'title' => 'Streak quiz',
            'slug' => 'streak-quiz-'.uniqid(),
            'status' => 'published',
            'created_by' => $f['instructor']->id,
        ]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $f['student']->id,
            'attempt_number' => 1,
            'score_percent' => 80,
            'status' => QuizAttempt::STATUS_GRADED,
            'graded_at' => now(),
            'created_at' => now(),
        ]);

        $assignment = Assignment::create([
            'class_id' => $f['class']->id,
            'created_by' => $f['instructor']->id,
            'title' => 'Yesterday paper',
            'slug' => 'yesterday-paper-'.uniqid(),
            'points' => 100,
            'submission_type' => Assignment::TYPE_TEXT,
            'due_date' => now()->addDay(),
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $f['student']->id,
            'attempt_number' => 1,
            'submission_text' => 'Submitted yesterday',
            'submitted_at' => now()->subDay(),
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
        ]);
        $submission->forceFill(['submitted_at' => now()->subDay()])->saveQuietly();

        $streak = app(ContentProgressService::class)->learningStreak($f['student']->id);

        $this->assertSame(2, $streak, 'A quiz today and a submission yesterday are two active days.');
    }

    public function test_dashboard_and_my_progress_ignore_empty_courses_and_share_one_streak(): void
    {
        $f = $this->enrollment();

        // A second class that actually has content: one published module with a
        // lesson the student completed today.
        $course = Course::factory()->create([
            'status' => 'published',
            'created_by' => $f['instructor']->id,
        ]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $f['instructor']->id,
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $f['student']->id,
            'class_id' => $class->id,
            'status' => 'active',
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Only module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $f['instructor']->id,
        ]);
        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => 'Only lesson',
            'lesson_type' => 'text',
            'status' => Lesson::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $f['instructor']->id,
        ]);
        ModuleProgress::create([
            'module_id' => $module->id,
            'student_id' => $f['student']->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
        LessonProgress::create([
            'lesson_id' => $lesson->id,
            'student_id' => $f['student']->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $dashboard = $this->actingAs($f['student'])->get('/student/dashboard');
        $dashboard->assertOk();
        $stats = $dashboard->viewData('stats');

        $this->assertEqualsWithDelta(
            100.0,
            (float) $stats['overall_progress'],
            0.01,
            'The empty course must not drag a fully completed course below 100%.'
        );
        $this->assertSame(1, $stats['learning_streak']);

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();
        $this->assertEqualsWithDelta(100.0, (float) $progress->viewData('overallProgress'), 0.01);
        $progress->assertSee('1 day streak');
        $progress->assertSee('100&percnt; overall progress', false);
        $progress->assertSee('No content published yet');
    }

    /**
     * @return array{0: Module, 1: Lesson}
     */
    private function moduleWithLesson(array $fixture, string $title): array
    {
        $module = Module::create([
            'course_id' => $fixture['course']->id,
            'title' => $title,
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $fixture['instructor']->id,
        ]);

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => $title.' lesson',
            'lesson_type' => 'text',
            'status' => Lesson::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $fixture['instructor']->id,
        ]);

        return [$module, $lesson];
    }

    public function test_progress_only_counts_modules_assigned_to_the_students_section(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        [$ownModule, $ownLesson] = $this->moduleWithLesson($f, 'Assigned module');
        [$otherModule, $otherLesson] = $this->moduleWithLesson($f, 'Other section module');

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $ownModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);

        $progress = $this->progress($f);

        $this->assertSame(1, $progress['totals']['modules'], 'Only the section\'s module belongs to this student.');
        $this->assertSame(1, $progress['totals']['lessons'], 'Lessons follow their module.');

        // Work done in another section's module must not count either way.
        ModuleProgress::create([
            'module_id' => $otherModule->id,
            'student_id' => $f['student']->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
        LessonProgress::create([
            'lesson_id' => $otherLesson->id,
            'student_id' => $f['student']->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        $progress = $this->progress($f);
        $this->assertSame(0, $progress['completed']['modules']);
        $this->assertSame(0, $progress['completed']['lessons']);

        // The assigned module still counts normally.
        ModuleProgress::create([
            'module_id' => $ownModule->id,
            'student_id' => $f['student']->id,
            'status' => ModuleProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);
        LessonProgress::create([
            'lesson_id' => $ownLesson->id,
            'student_id' => $f['student']->id,
            'status' => LessonProgress::STATUS_COMPLETED,
            'progress_percent' => 100,
            'completed_at' => now(),
        ]);

        $progress = $this->progress($f);
        $this->assertSame(1, $progress['completed']['modules']);
        $this->assertSame(1, $progress['completed']['lessons']);
        $this->assertEqualsWithDelta(100.0, $progress['overall'], 0.01);
    }

    public function test_work_attached_to_another_sections_module_is_not_counted(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        [$ownModule] = $this->moduleWithLesson($f, 'Assigned module');
        [$otherModule] = $this->moduleWithLesson($f, 'Other section module');

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $ownModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);

        Assignment::create([
            'class_id' => $f['class']->id,
            'module_id' => $otherModule->id,
            'created_by' => $f['instructor']->id,
            'title' => 'Other section essay',
            'slug' => 'other-section-'.uniqid(),
            'points' => 100,
            'submission_type' => Assignment::TYPE_TEXT,
            'due_date' => now()->addDay(),
            'status' => Assignment::STATUS_PUBLISHED,
        ]);

        $progress = $this->progress($f);

        $this->assertSame(0, $progress['totals']['assignments'], 'An assignment inside another section\'s module is not this student\'s work.');
    }

    public function test_progress_tiles_and_dashboard_counts_only_see_the_students_section(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        [$ownModule] = $this->moduleWithLesson($f, 'Assigned module');
        [$otherModule] = $this->moduleWithLesson($f, 'Other section module');

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $ownModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);

        $assignment = function (?int $moduleId, string $title) use ($f): Assignment {
            return Assignment::create([
                'class_id' => $f['class']->id,
                'module_id' => $moduleId,
                'created_by' => $f['instructor']->id,
                'title' => $title,
                'slug' => \Str::slug($title).'-'.uniqid(),
                'points' => 100,
                'submission_type' => Assignment::TYPE_TEXT,
                'due_date' => now()->addDay(),
                'status' => Assignment::STATUS_PUBLISHED,
            ]);
        };

        $assignment(null, 'Class work');
        $assignment($ownModule->id, 'Assigned module work');
        $assignment($otherModule->id, 'Other section work');

        $quiz = function (?int $moduleId, string $title) use ($f): Quiz {
            return Quiz::create([
                'class_id' => $f['class']->id,
                'module_id' => $moduleId,
                'title' => $title,
                'slug' => \Str::slug($title).'-'.uniqid(),
                'status' => 'published',
                'created_by' => $f['instructor']->id,
            ]);
        };

        $quiz(null, 'Class quiz');
        $quiz($ownModule->id, 'Assigned module quiz');
        $quiz($otherModule->id, 'Other section quiz');

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();
        $this->assertSame(2, $progress->viewData('totalAssignments'), 'Class work plus the assigned module only.');
        $this->assertSame(2, $progress->viewData('totalQuizzes'));

        $dashboard = $this->actingAs($f['student'])->get('/student/dashboard');
        $dashboard->assertOk();
        $stats = $dashboard->viewData('stats');

        $this->assertSame(2, $stats['total_assignments'], 'The dashboard count must match the progress tile.');
        $this->assertSame(2, $stats['total_quizzes']);
    }

    public function test_completed_activities_counts_only_lessons_in_assigned_modules(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        [$ownModule, $ownLesson] = $this->moduleWithLesson($f, 'Assigned module');
        [$otherModule, $otherLesson] = $this->moduleWithLesson($f, 'Other section module');

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $ownModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);

        // The student finished both lessons, but only one module is theirs.
        foreach ([$ownLesson, $otherLesson] as $lesson) {
            LessonProgress::create([
                'lesson_id' => $lesson->id,
                'student_id' => $f['student']->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);
        }

        $dashboard = $this->actingAs($f['student'])->get('/student/dashboard');
        $dashboard->assertOk();

        $stats = $dashboard->viewData('stats');

        $this->assertSame(
            1,
            $stats['completed_activities'],
            'A lesson inside another section\'s module is not this student\'s completed activity.'
        );
    }

    public function test_completion_timeline_only_lists_the_students_own_sections(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        // The course they finished here, in their own section.
        CourseCompletion::create([
            'class_id' => $f['class']->id,
            'student_id' => $f['student']->id,
            'completed_at' => now()->subWeek(),
            'completion_percent' => 100,
        ]);

        // History from an earlier term: the enrolment is completed, not dropped,
        // so that course still belongs on the student's timeline.
        $pastCourse = Course::factory()->create([
            'status' => 'published',
            'created_by' => $f['instructor']->id,
        ]);
        $pastClass = ClassModel::factory()->create([
            'course_id' => $pastCourse->id,
            'instructor_id' => $f['instructor']->id,
            'section_id' => $section->id,
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $f['student']->id,
            'class_id' => $pastClass->id,
            'status' => Enrollment::STATUS_COMPLETED,
        ]);
        CourseCompletion::create([
            'class_id' => $pastClass->id,
            'student_id' => $f['student']->id,
            'completed_at' => now()->subMonth(),
            'completion_percent' => 100,
        ]);

        // A completion row from another section's class — the kind the AA
        // import writes — must not appear: that class is not theirs.
        $foreignCourse = Course::factory()->create([
            'status' => 'published',
            'created_by' => $f['instructor']->id,
        ]);
        $foreignClass = ClassModel::factory()->create([
            'course_id' => $foreignCourse->id,
            'instructor_id' => $f['instructor']->id,
            'section_id' => $otherSection->id,
            'status' => 'active',
        ]);
        CourseCompletion::create([
            'class_id' => $foreignClass->id,
            'student_id' => $f['student']->id,
            'completed_at' => now(),
            'completion_percent' => 100,
        ]);

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();

        $timeline = $progress->viewData('completionTimeline');

        $this->assertSame(
            [$pastCourse->title, $f['course']->title],
            array_column($timeline, 'course'),
            'Only completions from the student\'s own section may appear, oldest first.'
        );
        $this->assertSame(2, $progress->viewData('completions')->count(), 'The footer count must match the timeline.');
    }

    public function test_average_grade_only_uses_released_grades_from_the_students_section(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $f['class']->update(['section_id' => $section->id]);
        $f['student']->update(['section_id' => $section->id]);

        // A class from the other block that the student is still enrolled in.
        // Transfers and AA drift leave rows like this behind, and they used to
        // drag the average away from the courses shown above the tile.
        $otherClass = ClassModel::factory()->create([
            'course_id' => $f['course']->id,
            'instructor_id' => $f['instructor']->id,
            'section_id' => $otherSection->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $f['student']->id,
            'class_id' => $otherClass->id,
            'status' => 'active',
        ]);

        $this->releasedGrade($f['student'], $f['class'], 'Section A project', 90);
        $this->releasedGrade($f['student'], $otherClass, 'Section B project', 10);
        $this->releasedGrade($f['student'], $f['class'], 'Graded but not released', 0, false);

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();

        $this->assertSame(
            90.0,
            (float) $progress->viewData('averageGrade'),
            'Another section\'s released grade must not move the average.'
        );
        $this->assertSame(1, $progress->viewData('gradedItemCount'));
        $this->assertCount(
            1,
            $progress->viewData('recentGrades'),
            'The list under the tile must use the same section scope as the average.'
        );
    }

    public function test_average_grade_keeps_every_enrolled_class_when_the_student_has_no_section(): void
    {
        $f = $this->enrollment();

        // Unsectioned student (legacy data): there is nothing to narrow
        // against, so every released grade in their classes still counts.
        $otherClass = ClassModel::factory()->create([
            'course_id' => $f['course']->id,
            'instructor_id' => $f['instructor']->id,
            'section_id' => Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()])->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $f['student']->id,
            'class_id' => $otherClass->id,
            'status' => 'active',
        ]);

        $this->releasedGrade($f['student'], $f['class'], 'First class', 90);
        $this->releasedGrade($f['student'], $otherClass, 'Second class', 30);

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();

        $this->assertSame(60.0, (float) $progress->viewData('averageGrade'));
        $this->assertSame(2, $progress->viewData('gradedItemCount'));
    }

    public function test_dashboard_average_grade_matches_my_progress_section_scope(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);

        $f['class']->update(['section_id' => $section->id]);
        $f['student']->update(['section_id' => $section->id]);

        // An active enrolment left over from another block: keep the grades
        // there out of both pages so the two cards cannot disagree.
        $otherClass = ClassModel::factory()->create([
            'course_id' => $f['course']->id,
            'instructor_id' => $f['instructor']->id,
            'section_id' => $otherSection->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $f['student']->id,
            'class_id' => $otherClass->id,
            'status' => 'active',
        ]);

        $this->releasedGrade($f['student'], $f['class'], 'Section A project', 90);
        $this->releasedGrade($f['student'], $otherClass, 'Section B project', 10);

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();

        $dashboard = $this->actingAs($f['student'])->get('/student/dashboard');
        $dashboard->assertOk();
        $stats = $dashboard->viewData('stats');

        // No AA final grade yet, so the card falls back to the released LMS scores.
        $this->assertFalse($stats['average_grade_is_final']);
        $this->assertSame(
            (float) $progress->viewData('averageGrade'),
            (float) $stats['average_grade'],
            'The dashboard card must show the same average as My Progress.'
        );
        $this->assertSame(90.0, (float) $stats['average_grade']);
        $this->assertSame(1, $stats['total_grades'], 'The card footer counts the same section-scoped grades.');
        $this->assertSame(1, $stats['recent_grades']->count());
    }

    public function test_weekly_activity_counts_only_assigned_modules_and_finished_work(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Section A', 'code' => 'SEC-A-'.uniqid()]);
        $otherSection = Section::create(['name' => 'Section B', 'code' => 'SEC-B-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        [$ownModule, $ownLesson] = $this->moduleWithLesson($f, 'Assigned module');
        [$otherModule, $otherLesson] = $this->moduleWithLesson($f, 'Other section module');

        SectionModuleAssignment::create([
            'section_id' => $section->id,
            'module_id' => $ownModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);
        SectionModuleAssignment::create([
            'section_id' => $otherSection->id,
            'module_id' => $otherModule->id,
            'course_id' => $f['course']->id,
            'status' => 'active',
        ]);

        // Both lessons were finished today; only one sits in this section's module.
        foreach ([$ownLesson, $otherLesson] as $lesson) {
            LessonProgress::create([
                'lesson_id' => $lesson->id,
                'student_id' => $f['student']->id,
                'status' => LessonProgress::STATUS_COMPLETED,
                'progress_percent' => 100,
                'started_at' => now()->subHour(),
                'completed_at' => now(),
            ]);
        }

        $quiz = function (?int $moduleId, string $title) use ($f): Quiz {
            return Quiz::create([
                'class_id' => $f['class']->id,
                'module_id' => $moduleId,
                'title' => $title,
                'slug' => \Str::slug($title).'-'.uniqid(),
                'status' => 'published',
                'created_by' => $f['instructor']->id,
            ]);
        };

        $ownQuiz = $quiz($ownModule->id, 'Assigned module quiz');
        $otherQuiz = $quiz($otherModule->id, 'Other section quiz');

        // One finished attempt in the student's own module, one still open, and
        // one in another section's module: only the first is activity.
        $attempts = [
            [$ownQuiz, QuizAttempt::STATUS_GRADED, 80],
            [$ownQuiz, QuizAttempt::STATUS_IN_PROGRESS, null],
            [$otherQuiz, QuizAttempt::STATUS_GRADED, 70],
        ];

        foreach ($attempts as $index => [$attemptQuiz, $status, $score]) {
            QuizAttempt::create([
                'quiz_id' => $attemptQuiz->id,
                'student_id' => $f['student']->id,
                'attempt_number' => $index + 1,
                'score_percent' => $score,
                'status' => $status,
                'graded_at' => $score === null ? null : now(),
                'created_at' => now(),
            ]);
        }

        $assignment = function (?int $moduleId, string $title) use ($f): Assignment {
            return Assignment::create([
                'class_id' => $f['class']->id,
                'module_id' => $moduleId,
                'created_by' => $f['instructor']->id,
                'title' => $title,
                'slug' => \Str::slug($title).'-'.uniqid(),
                'points' => 100,
                'submission_type' => Assignment::TYPE_TEXT,
                'due_date' => now()->addDay(),
                'status' => Assignment::STATUS_PUBLISHED,
            ]);
        };

        $ownAssignment = $assignment($ownModule->id, 'Assigned module work');
        $otherAssignment = $assignment($otherModule->id, 'Other section work');

        foreach ([$ownAssignment, $otherAssignment] as $target) {
            AssignmentSubmission::create([
                'assignment_id' => $target->id,
                'student_id' => $f['student']->id,
                'attempt_number' => 1,
                'submission_text' => 'Submitted today',
                'submitted_at' => now(),
                'status' => AssignmentSubmission::STATUS_SUBMITTED,
            ]);
        }

        $progress = $this->actingAs($f['student'])->get('/student/progress');
        $progress->assertOk();

        $weekly = $progress->viewData('weeklyActivity');
        $today = end($weekly['data']);

        $this->assertSame(1, $today['lessons_completed'], 'A lesson in another section\'s module is not this student\'s activity.');
        $this->assertSame(1, $today['quizzes_taken'], 'An unfinished attempt, or a quiz outside the section, is not activity.');
        $this->assertSame(1, $today['assignments_submitted'], 'Another section\'s assignment is not this student\'s activity.');
    }

    public function test_sections_without_assignments_keep_every_published_module(): void
    {
        $f = $this->enrollment();

        $section = Section::create(['name' => 'Unassigned section', 'code' => 'SEC-U-'.uniqid()]);
        $f['class']->update(['section_id' => $section->id]);

        $this->moduleWithLesson($f, 'First module');
        $this->moduleWithLesson($f, 'Second module');

        $progress = $this->progress($f);

        $this->assertSame(2, $progress['totals']['modules'], 'Legacy courses with no section assignments must not lose content.');
        $this->assertSame(2, $progress['totals']['lessons']);
    }

    /**
     * Give a student one graded item in a class, optionally still unreleased.
     */
    private function releasedGrade(User $student, ClassModel $class, string $title, float $score, bool $released = true): Grade
    {
        $item = GradeItem::create([
            'class_id' => $class->id,
            'title' => $title,
            'max_points' => 100,
            'item_type' => GradeItem::TYPE_ASSIGNMENT,
            'is_released' => $released,
            'released_at' => $released ? now() : null,
        ]);

        return Grade::create([
            'grade_item_id' => $item->id,
            'student_id' => $student->id,
            'points' => $score,
            'score_percent' => $score,
            'graded_at' => now(),
        ]);
    }
}

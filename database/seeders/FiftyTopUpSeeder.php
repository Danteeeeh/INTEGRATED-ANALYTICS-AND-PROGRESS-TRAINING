<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use App\Models\ClassModel;
use App\Models\Competency;
use App\Models\CompetencyFramework;
use App\Models\Course;
use App\Models\CourseCompetency;
use App\Models\Department;
use App\Models\Discussion;
use App\Models\GradeItem;
use App\Models\LearningPlan;
use App\Models\LearningPlanItem;
use App\Models\Program;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Topped up so every core module holds ~50 rows (safe to re-run):
 * everything keyed on stable unique slugs/codes via firstOrCreate.
 */
class FiftyTopUpSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@lms.local')->first()
            ?? User::where('role_id', 38)->firstOrFail();
        $admin = User::where('email', 'admin@lms.local')->first()
            ?? User::where('role_id', 37)->first();
        $studentRoleId = \DB::table('roles')->where('slug', 'student')->value('id');
        $studentIds = User::where('role_id', $studentRoleId)->pluck('id')->all();
        $classIds = ClassModel::pluck('id')->all();
        $courseIds = Course::pluck('id')->all();
        $now = now();

        $this->command?->info('TopUp: academic periods -> 50');
        for ($i = 1; $i <= 50; $i++) {
            AcademicPeriod::firstOrCreate(
                ['code' => 'PER-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Academic Period '.$i,
                    'start_date' => $now->copy()->subMonths(6)->addDays($i * 2)->toDateString(),
                    'end_date' => $now->copy()->addMonths(6)->addDays($i * 2)->toDateString(),
                    'is_current' => false,
                    'is_enrollment_open' => false,
                ]
            );
        }

        $this->command?->info('TopUp: departments/programs/sections -> 50');
        for ($i = 1; $i <= 50; $i++) {
            Department::firstOrCreate(
                ['code' => 'DEPT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                ['name' => 'Department '.$i]
            );
        }
        for ($i = 1; $i <= 50; $i++) {
            Program::firstOrCreate(
                ['code' => 'PROG-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                ['name' => 'Program '.$i, 'department_id' => 1]
            );
        }
        for ($i = 1; $i <= 50; $i++) {
            Section::firstOrCreate(
                ['code' => 'SEC-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                ['name' => 'Section '.$i, 'program_id' => 1, 'academic_period_id' => AcademicPeriod::value('id')]
            );
        }

        $this->command?->info('TopUp: courses -> 50');
        for ($i = 6; $i <= 50; $i++) {
            Course::firstOrCreate(
                ['code' => 'CS-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'title' => 'Course '.$i.': Systems & Applications',
                    'description' => 'BSIT-aligned course '.$i.'.',
                    'credits' => 3,
                    'duration_weeks' => 16,
                    'status' => 'published',
                    'academic_period_id' => AcademicPeriod::where('is_current', true)->value('id') ?? AcademicPeriod::value('id'),
                    'created_by' => $admin?->id ?? $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: classes -> 50');
        $courseAll = Course::pluck('id')->all();
        for ($i = 1; $i <= 50; $i++) {
            ClassModel::firstOrCreate(
                ['code' => 'CLS-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'course_id' => $courseAll[$i % count($courseAll)],
                    'instructor_id' => $instructor->id,
                    'academic_period_id' => AcademicPeriod::where('is_current', true)->value('id') ?? AcademicPeriod::value('id'),
                    'schedule' => 'Mon/Wed '.rand(8, 17).':00',
                    'room' => 'Room '.rand(101, 305),
                    'capacity' => 40,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('TopUp: question banks -> 50, quizzes -> 50');
        $classesAll = ClassModel::pluck('id')->all();
        for ($i = 1; $i <= 50; $i++) {
            QuestionBank::firstOrCreate(
                ['code' => 'QB-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                [
                    'title' => 'Question Bank '.$i,
                    'description' => 'Sample bank '.$i,
                    'category' => 'course',
                    'course_id' => $courseAll[$i % count($courseAll)],
                    'class_id' => null,
                    'created_by' => $instructor->id,
                    'is_shared' => true,
                    'status' => 'active',
                ]
            );
        }
        for ($i = 1; $i <= 50; $i++) {
            Quiz::firstOrCreate(
                ['slug' => 'quiz-'.$i.'-'.Str::slug((string) $i)],
                [
                    'class_id' => $classesAll[$i % count($classesAll)],
                    'title' => 'Quiz '.$i,
                    'description' => 'Module quiz '.$i,
                    'time_limit_minutes' => 15,
                    'attempt_limit' => 2,
                    'passing_score_percent' => 60,
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: assignments -> 50');
        for ($i = 1; $i <= 50; $i++) {
            Assignment::firstOrCreate(
                ['slug' => 'assignment-'.$i],
                [
                    'class_id' => $classesAll[$i % count($classesAll)],
                    'title' => 'Assignment '.$i,
                    'instructions' => 'Complete assignment '.$i.'.',
                    'points' => 100,
                    'submission_type' => 'file',
                    'due_date' => $now->copy()->addDays(rand(1, 30)),
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: discussions -> 50');
        for ($i = 1; $i <= 50; $i++) {
            Discussion::firstOrCreate(
                ['slug' => 'discussion-'.$i],
                [
                    'class_id' => $classesAll[$i % count($classesAll)],
                    'course_id' => $courseAll[$i % count($courseAll)],
                    'title' => 'Discussion Topic '.$i,
                    'description' => 'Weekly discussion '.$i,
                    'discussion_type' => 'general',
                    'is_pinned' => false,
                    'is_locked' => false,
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: announcements -> 50, calendar events -> 50');
        for ($i = 1; $i <= 50; $i++) {
            Announcement::firstOrCreate(
                ['title' => 'Announcement '.$i],
                [
                    'body' => 'Announcement '.$i.' — please check the portal for details.',
                    'audience_type' => 'institution',
                    'target_role_id' => $studentRoleId,
                    'is_pinned' => false,
                    'publish_at' => $now->copy()->subDays(rand(0, 20)),
                    'status' => 'published',
                    'created_by' => $instructor->id,
                ]
            );
        }
        for ($i = 1; $i <= 50; $i++) {
            CalendarEvent::firstOrCreate(
                ['title' => 'Event '.$i, 'class_id' => $classesAll[$i % count($classesAll)], 'start_at' => $now->copy()->addDays($i % 30)->setTime(9, 0)],
                [
                    'user_id' => $instructor->id,
                    'course_id' => $courseAll[$i % count($courseAll)],
                    'description' => 'Event '.$i,
                    'event_type' => ['quiz', 'assignment', 'exam', 'virtual_class'][$i % 4],
                    'is_all_day' => false,
                    'location' => 'Room '.rand(101, 305),
                    'recurrence' => 'none',
                    'visibility' => 'class',
                    'created_by' => $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: virtual classes -> 50');
        for ($i = 1; $i <= 50; $i++) {
            VirtualClass::firstOrCreate(
                ['title' => 'Virtual Session '.$i],
                [
                    'class_id' => $classesAll[$i % count($classesAll)],
                    'course_id' => $courseAll[$i % count($courseAll)],
                    'instructor_id' => $instructor->id,
                    'description' => 'Live session '.$i,
                    'meeting_date' => $now->copy()->addDays($i % 20)->toDateString(),
                    'start_time' => '10:00:00',
                    'end_time' => '11:30:00',
                    'meeting_provider' => 'google_meet',
                    'meeting_url' => 'https://meet.google.com/topup-'.Str::lower(Str::random(6)),
                    'recurrence' => 'none',
                    'status' => ['scheduled', 'completed'][$i % 2],
                    'created_by' => $instructor->id,
                ]
            );
        }

        $this->command?->info('TopUp: grade items -> 50 per class (already), add to 50+');
        foreach (ClassModel::pluck('id')->all() as $cid) {
            foreach (['Prelim Exam', 'Midterm Exam', 'Final Exam', 'Assignment Avg', 'Quiz Avg', 'Participation'] as $title) {
                GradeItem::firstOrCreate(
                    ['class_id' => $cid, 'title' => $title],
                    [
                        'description' => $title.' for class '.$cid,
                        'max_points' => 100,
                        'factor' => 0.2,
                        'item_type' => 'exam',
                        'position' => 0,
                        'is_released' => true,
                    ]
                );
            }
        }

        $this->command?->info('TopUp: learning plans -> 50');
        foreach ($studentIds as $sid) {
            LearningPlan::firstOrCreate(
                ['student_id' => $sid, 'title' => 'Personalized learning plan'],
                [
                    'description' => 'AI-derived learning plan for steady progress.',
                    'target_date' => $now->copy()->addDays(14)->toDateString(),
                    'status' => 'active',
                    'created_by' => $admin?->id ?? $instructor->id,
                ]
            );
        }
        foreach (LearningPlan::pluck('id')->all() as $lpId) {
            LearningPlanItem::firstOrCreate(
                ['learning_plan_id' => $lpId, 'title' => 'Review quiz topics'],
                [
                    'description' => 'Revisit related lessons and practice.',
                    'target_date' => $now->copy()->addDays(7)->toDateString(),
                    'progress_percent' => 50,
                    'status' => 'in_progress',
                ]
            );
        }

        $this->command?->info('TopUp: competencies -> 50');
        $fw = CompetencyFramework::first();
        if ($fw) {
            for ($i = 10; $i <= 50; $i++) {
                Competency::firstOrCreate(
                    ['framework_id' => $fw->id, 'code' => 'C-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                    [
                        'parent_id' => null,
                        'name' => 'Competency '.$i,
                        'description' => 'Competency '.$i.' for the BSIT program.',
                        'position' => $i,
                    ]
                );
            }
        }
        foreach (Competency::pluck('id')->all() as $compId) {
            foreach (Course::pluck('id')->all() as $cid) {
                CourseCompetency::firstOrCreate(
                    ['competency_id' => $compId, 'course_id' => $cid, 'module_id' => null, 'lesson_id' => null],
                    ['weight' => 1.0]
                );
            }
        }

        $this->command?->info('TopUp done.');
    }
}

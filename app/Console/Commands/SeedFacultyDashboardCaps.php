<?php

namespace App\Console\Commands;

use App\Models\AcademicPeriod;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradeSubmission;
use App\Models\Role;
use App\Models\User;
use App\Models\VirtualClass;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Recreates the local faculty dashboard data used by the capped-panel visual
 * checks. This command is intentionally local-only: it writes demo accounts,
 * classes and assessment rows, so it must never run against production.
 */
class SeedFacultyDashboardCaps extends Command
{
    protected $signature = 'lms:seed-faculty-dashboard-caps
                            {--instructor=instructor@lms.local : Faculty account whose dashboard should be populated}';

    protected $description = 'Seed local-only faculty dashboard cap fixtures and clear the cached dashboard stats';

    private const DEMO_MARKER = 'faculty-dashboard-cap-demo';

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->error('This command only runs in the local environment.');

            return self::FAILURE;
        }

        $instructor = User::where('email', $this->option('instructor'))->first();

        if (! $instructor || ! $instructor->isInstructor()) {
            $this->error('Faculty account not found. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $period = AcademicPeriod::where('is_current', true)->first() ?? AcademicPeriod::first();
        $baseClass = ClassModel::where('instructor_id', $instructor->id)->orderBy('id')->first();
        $courseId = $baseClass?->course_id ?? Course::query()->value('id');

        if (! $period || ! $courseId) {
            $this->error('Academic period or course data is missing. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $studentRole = Role::where('slug', Role::STUDENT)->first();

        if (! $studentRole) {
            $this->error('Student role is missing. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        $counts = DB::transaction(function () use ($instructor, $period, $courseId, $studentRole) {
            $capClass = ClassModel::updateOrCreate(
                ['code' => 'CAP-DEMO-01'],
                [
                    'course_id' => $courseId,
                    'academic_period_id' => $period->id,
                    'instructor_id' => $instructor->id,
                    'schedule' => 'Cap demo schedule',
                    'room' => 'Cap demo room',
                    'capacity' => 60,
                    'status' => 'active',
                ]
            );

            $this->ensureClassCount($instructor, $courseId, $period->id, 11);

            $enrollmentCount = Enrollment::whereHas(
                'class',
                fn ($query) => $query->where('instructor_id', $instructor->id)
            )->count();

            // Six students are needed for the to-check queue; the remaining
            // students bring recent course activity past its five-row cap.
            $studentsNeeded = max(6, 26 - $enrollmentCount);
            $students = collect();

            for ($i = 1; $i <= $studentsNeeded; $i++) {
                $student = User::updateOrCreate(
                    ['email' => sprintf('cap.demo.%02d@lms.local', $i)],
                    [
                        'first_name' => 'Cap Demo',
                        'last_name' => sprintf('Student %02d', $i),
                        'identifier' => sprintf('CAP-STU-%02d', $i),
                        'password' => Hash::make('Password123!'),
                        'role_id' => $studentRole->id,
                        'status' => 'active',
                    ]
                );

                Enrollment::updateOrCreate(
                    ['student_id' => $student->id, 'class_id' => $capClass->id],
                    [
                        'status' => Enrollment::STATUS_ACTIVE,
                        'enrolled_at' => now()->subMinutes($i),
                    ]
                );

                $students->push($student);
            }

            $this->ensureAtRiskGrades($capClass, $instructor, $students);
            $assignments = $this->ensureUpcomingAssignments($capClass, $instructor);
            $this->ensureToCheckSubmissions($assignments->first(), $students);
            $this->ensureGradeSubmissions($instructor);
            $this->ensureVirtualClasses($capClass, $courseId, $instructor);
            $this->ensureAnnouncements($capClass, $courseId, $instructor);

            return [
                'classes' => ClassModel::where('instructor_id', $instructor->id)->count(),
                'enrollments' => Enrollment::whereHas(
                    'class',
                    fn ($query) => $query->where('instructor_id', $instructor->id)
                )->count(),
                'at_risk' => $students->take(5)->count(),
                'upcoming_assignments' => $assignments->count(),
                'to_check' => $students->take(6)->count(),
                'grade_submissions' => GradeSubmission::where('instructor_id', $instructor->id)->count(),
                'virtual_classes' => VirtualClass::where('class_id', $capClass->id)->count(),
                'announcements' => Announcement::where('class_id', $capClass->id)->count(),
            ];
        });

        $cacheKey = "instructor_dashboard_{$instructor->id}";
        Cache::forget($cacheKey);

        $this->info("Faculty dashboard cap fixtures are ready for {$instructor->email}.");
        $this->table(
            ['Panel', 'Seeded total', 'Display cap'],
            [
                ['Class performance', $counts['classes'], '6'],
                ['Needs attention', $counts['at_risk'], '4'],
                ['My courses', $counts['classes'], '3'],
                ['Recent course activity', $counts['enrollments'], '5'],
                ['Extract & submit grades', $counts['grade_submissions'], '5'],
                ['To-do list', $counts['upcoming_assignments'].' assignments + 5 virtual classes', '5'],
                ['To check', $counts['to_check'], '5'],
                ['Virtual classes', $counts['virtual_classes'], '4'],
                ['Recent notifications', $counts['announcements'], '3'],
            ]
        );
        $this->line("Cleared cache key: {$cacheKey}");

        return self::SUCCESS;
    }

    private function ensureClassCount(User $instructor, int $courseId, int $periodId, int $target): void
    {
        for ($i = 2; ClassModel::where('instructor_id', $instructor->id)->count() < $target; $i++) {
            ClassModel::updateOrCreate(
                ['code' => sprintf('CAP-DEMO-%02d', $i)],
                [
                    'course_id' => $courseId,
                    'academic_period_id' => $periodId,
                    'instructor_id' => $instructor->id,
                    'schedule' => 'Cap demo schedule',
                    'room' => 'Cap demo room',
                    'capacity' => 60,
                    'status' => 'active',
                ]
            );
        }
    }

    private function ensureAtRiskGrades(ClassModel $class, User $instructor, $students): void
    {
        $item = GradeItem::updateOrCreate(
            ['class_id' => $class->id, 'title' => 'CAP Demo At-Risk Grade'],
            [
                'description' => 'Local demo grade used to exceed the Needs attention cap.',
                'max_points' => 100,
                'factor' => 1,
                'item_type' => GradeItem::TYPE_ASSIGNMENT,
                'position' => 0,
                'is_released' => true,
                'released_at' => now(),
            ]
        );

        foreach ($students->take(5) as $student) {
            Grade::updateOrCreate(
                ['grade_item_id' => $item->id, 'student_id' => $student->id],
                [
                    'points' => 30,
                    'score_percent' => 30,
                    'letter_grade' => 'F',
                    'graded_by' => $instructor->id,
                    'graded_at' => now(),
                    'feedback' => self::DEMO_MARKER,
                ]
            );
        }
    }

    private function ensureUpcomingAssignments(ClassModel $class, User $instructor)
    {
        $assignments = collect();

        for ($i = 1; $i <= 18; $i++) {
            $assignments->push(Assignment::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'title' => sprintf('CAP Demo Upcoming Assignment %02d', $i),
                ],
                [
                    'slug' => sprintf('cap-demo-upcoming-assignment-%02d', $i),
                    'instructions' => 'Local demo assignment used to exceed the To-do list cap.',
                    'points' => 100,
                    'submission_type' => Assignment::TYPE_TEXT,
                    'due_date' => now()->addDays($i + 1)->setTime(17, 0),
                    'allow_late' => true,
                    'late_submission_deduction_percent' => 10,
                    'max_attempts' => 1,
                    'allow_resubmission' => false,
                    'availability_from' => now()->subDay(),
                    'availability_until' => now()->addDays(30),
                    'status' => Assignment::STATUS_PUBLISHED,
                    'created_by' => $instructor->id,
                ]
            ));
        }

        return $assignments;
    }

    private function ensureToCheckSubmissions(?Assignment $assignment, $students): void
    {
        if (! $assignment) {
            return;
        }

        foreach ($students->take(6)->values() as $index => $student) {
            AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'student_id' => $student->id,
                    'attempt_number' => 1,
                ],
                [
                    'submission_text' => 'Local cap-demo submission awaiting grading.',
                    'submitted_at' => now()->subHours($index + 1),
                    'is_late' => false,
                    'status' => AssignmentSubmission::STATUS_SUBMITTED,
                    'graded_by' => null,
                    'graded_at' => null,
                ]
            );
        }
    }

    private function ensureGradeSubmissions(User $instructor): void
    {
        $classes = ClassModel::where('instructor_id', $instructor->id)
            ->orderBy('id')
            ->take(6)
            ->get();

        foreach ($classes as $index => $class) {
            GradeSubmission::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'instructor_id' => $instructor->id,
                    'period_label' => sprintf('CAP Demo Batch %02d', $index + 1),
                    'notes' => self::DEMO_MARKER,
                ],
                [
                    'submitted_by' => $instructor->id,
                    'status' => $index < 4
                        ? GradeSubmission::STATUS_SUBMITTED
                        : GradeSubmission::STATUS_FAILED,
                    'student_count' => 10,
                    'graded_count' => 8,
                    'missing_count' => $index < 4 ? 0 : 2,
                    'average_percent' => 78.5,
                    'payload' => ['source' => self::DEMO_MARKER],
                    'submitted_at' => now()->subHours($index + 1),
                ]
            );
        }
    }

    private function ensureVirtualClasses(ClassModel $class, int $courseId, User $instructor): void
    {
        for ($i = 1; $i <= 5; $i++) {
            VirtualClass::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'title' => sprintf('CAP Demo Virtual Session %02d', $i),
                ],
                [
                    'course_id' => $courseId,
                    'instructor_id' => $instructor->id,
                    'description' => 'Local demo session used to exceed the Virtual classes cap.',
                    'meeting_date' => today()->addDays($i),
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                    'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
                    'livekit_room_name' => sprintf('cap-demo-room-%02d', $i),
                    'status' => VirtualClass::STATUS_SCHEDULED,
                    'created_by' => $instructor->id,
                ]
            );
        }
    }

    private function ensureAnnouncements(ClassModel $class, int $courseId, User $instructor): void
    {
        for ($i = 1; $i <= 6; $i++) {
            Announcement::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'title' => sprintf('CAP Demo Announcement %02d', $i),
                ],
                [
                    'body' => 'Local demo announcement used to exceed the recent notifications cap.',
                    'audience_type' => Announcement::AUDIENCE_CLASS,
                    'course_id' => $courseId,
                    'created_by' => $instructor->id,
                    'publish_at' => now()->subHours($i),
                    'status' => Announcement::STATUS_PUBLISHED,
                ]
            );
        }
    }
}

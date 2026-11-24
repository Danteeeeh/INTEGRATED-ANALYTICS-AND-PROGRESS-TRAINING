<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo state for the student dashboard's capped header hints.
 *
 * Six unsubmitted upcoming assignments produce "Showing 2 of 6" in the To-do
 * panel, and six published class announcements produce "Showing 3 of 6" in
 * the Announcements panel. Safe to re-run: rows are keyed by stable titles.
 *
 * This is demo-only state: it is a no-op outside the local environment.
 */
class StudentDashboardCapSeeder extends Seeder
{
    private const ASSIGNMENT_PREFIX = 'Student Demo To-do';

    private const ANNOUNCEMENT_PREFIX = 'Student Demo Announcement';

    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $student = User::where('email', 'student@lms.local')->first();

        if (! $student) {
            $this->command?->warn('StudentDashboardCapSeeder: student@lms.local was not found.');

            return;
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('status', Enrollment::STATUS_ACTIVE)
            ->whereHas('class', fn ($query) => $query->where('status', 'active'))
            ->with('class')
            ->orderBy('class_id')
            ->first();

        /** @var ClassModel|null $class */
        $class = $enrollment?->class;

        if (! $class) {
            $this->command?->warn('StudentDashboardCapSeeder: the demo student has no active class.');

            return;
        }

        $instructorId = $class->instructor_id
            ?? User::where('email', 'instructor@lms.local')->value('id');

        if (! $instructorId) {
            $this->command?->warn('StudentDashboardCapSeeder: no instructor is available for the demo class.');

            return;
        }

        for ($index = 1; $index <= 6; $index++) {
            Assignment::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'title' => sprintf('%s %02d', self::ASSIGNMENT_PREFIX, $index),
                ],
                [
                    'slug' => sprintf('student-demo-todo-%02d', $index),
                    'instructions' => 'Local demo to-do used to verify the student dashboard cap hint.',
                    'points' => 100,
                    'submission_type' => Assignment::TYPE_TEXT,
                    'due_date' => now()->addDays($index)->setTime(17, 0),
                    'allow_late' => true,
                    'late_submission_deduction_percent' => 10,
                    'max_attempts' => 1,
                    'allow_resubmission' => false,
                    'availability_from' => now()->subDay(),
                    'availability_until' => now()->addDays(30),
                    'status' => Assignment::STATUS_PUBLISHED,
                    'created_by' => $instructorId,
                ]
            );

            Announcement::updateOrCreate(
                [
                    'class_id' => $class->id,
                    'title' => sprintf('%s %02d', self::ANNOUNCEMENT_PREFIX, $index),
                ],
                [
                    'body' => 'Local demo announcement used to verify the student dashboard cap hint.',
                    'audience_type' => Announcement::AUDIENCE_CLASS,
                    'course_id' => $class->course_id,
                    'created_by' => $instructorId,
                    'publish_at' => now()->subHours($index),
                    'status' => Announcement::STATUS_PUBLISHED,
                ]
            );
        }

        $this->command?->info('StudentDashboardCapSeeder: 6 to-dos and 6 announcements ready for '.$student->email.'.');
    }
}

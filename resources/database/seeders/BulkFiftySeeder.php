<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementView;
use App\Models\AttendanceRecord;
use App\Models\CalendarEvent;
use App\Models\ClassModel;
use App\Models\CourseProgress;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\ModuleProgress;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\VirtualClass;
use App\Models\VirtualClassAttendee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds ~50 extra students and fills every previously-empty feature area
 * (announcements, calendar, attendance, virtual classes,
 * grades, quiz attempts) so the whole system has demo data for the capstone.
 *
 * Safe to re-run: keyed on stable emails/identifiers + firstOrCreate.
 */
class BulkFiftySeeder extends Seeder
{
    public function run(): void
    {
        $studentRole = Role::where('slug', Role::STUDENT)->firstOrFail();
        $instructorRole = Role::where('slug', Role::INSTRUCTOR)->firstOrFail();

        $instructor = User::where('email', 'instructor@lms.local')->first()
            ?? User::where('role_id', $instructorRole->id)->firstOrFail();

        $admin = User::where('email', 'admin@lms.local')->first()
            ?? User::where('role_id', Role::where('slug', Role::ADMIN)->value('id'))->first();

        $deptId = \DB::table('departments')->value('id');
        $programId = \DB::table('programs')->where('code', 'BSIT')->value('id')
            ?? \DB::table('programs')->value('id');
        $sectionIds = \DB::table('sections')->pluck('id')->all();

        // Skip seeding if sections don't exist (PhaseTwoSeeder should create them)
        if (empty($sectionIds)) {
            $this->command?->warn('BulkFifty: No sections found, skipping student creation.');
            return;
        }

        // Skip seeding if departments or programs don't exist
        if (!$deptId || !$programId) {
            $this->command?->warn('BulkFifty: No departments or programs found, skipping student creation.');
            return;
        }

        $firstNames = ['Maria', 'Jose', 'Juan', 'Ana', 'Rosa', 'Carlo', 'Dennis', 'Erika', 'Paolo', 'Liza', 'Marco', 'Bianca', 'Rafael', 'Sofia', 'Gabriel', 'Andrea', 'Miguel', 'Camille', 'Luis', 'Patricia', 'Andres', 'Nina', 'Ramon', 'Jasmine', 'Victor', 'Kyla', 'Emilio', 'Teresa', 'Noah', 'Angela', 'Ivan', 'Bea', 'Samuel', 'Clarissa', 'Oscar', 'Diane', 'Felix', 'Giselle', 'Hector', 'Iris', 'Jerome', 'Katrina', 'Leo', 'Mia', 'Nestor', 'Olivia', 'Pedro', 'Queen', 'Rico', 'Samantha'];
        $lastNames = ['Santos', 'Reyes', 'Garcia', 'Cruz', 'Mendoza', 'Bautista', 'Villanueva', 'Torres', 'Flores', 'Ramos', 'Aquino', 'Navarro', 'Domingo', 'Del Rosario', 'Salazar', 'Manalo', 'Pascual', 'Ocampo', 'Valdez', 'Corpuz', 'Lopez', 'Rivera', 'Castillo', 'Gutierrez', 'Ferrer', 'Marcelo', 'Alvarez', 'Padilla', 'Villamor', 'Dizon', 'Sy', 'Tan', 'Lim', 'Chua', 'Co', 'Uy', 'Ang', 'Yu', 'Go', 'Ong', 'Castro', 'Luna', 'Roxas', 'Morales', 'Agbayani', 'Tolentino', 'Buenaventura', 'Cortez', 'Mallari', 'Sison'];

        $this->command?->info('BulkFifty: creating 50 students...');

        $students = [];
        foreach ($firstNames as $i => $first) {
            $last = $lastNames[$i % count($lastNames)];
            $email = 'student'.sprintf('%02d', $i + 1).'@lms.local';
            $identifier = 'STU-'.sprintf('%04d', 101 + $i);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'identifier' => $identifier,
                    'password' => Hash::make('Password123!'),
                    'role_id' => $studentRole->id,
                    'department_id' => $deptId,
                    'program_id' => $programId,
                    'section_id' => !empty($sectionIds) ? $sectionIds[$i % max(1, count($sectionIds))] : null,
                    'status' => 'active',
                    'email_verified_at' => now()->subDays(rand(5, 40)),
                ]
            );
            $students[] = $user;
        }

        $this->command?->info('BulkFifty: '.count($students).' students ready.');

        // ── Enroll every student into 3–5 classes ─────────────────────────
        $classes = ClassModel::where('status', 'active')->get();
        if ($classes->isEmpty()) {
            $classes = ClassModel::all();
        }
        $classIds = $classes->pluck('id')->all();

        $this->command?->info('BulkFifty: enrolling students...');
        foreach ($students as $student) {
            if (empty($classIds)) {
                continue;
            }
            $pick = array_rand($classIds, min(4, count($classIds))); // up to 4 classes
            foreach ((array) $pick as $key) {
                Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'class_id' => $classIds[$key]],
                    [
                        'status' => 'active',
                        'enrolled_at' => now()->subDays(rand(10, 40)),
                    ]
                );
            }
        }

        $this->command?->info('BulkFifty: enrollments done.');

        // ── Attendance records across the classes ─────────────────────────
        $this->command?->info('BulkFifty: attendance...');
        $dates = collect(range(0, 9))->map(fn ($d) => now()->subDays($d)->toDateString());
        foreach ($classes as $class) {
            $enrolled = Enrollment::where('class_id', $class->id)->pluck('student_id')->all();
            if (empty($enrolled)) {
                continue;
            }
            foreach ($dates as $date) {
                foreach (array_slice($enrolled, 0, 12) as $sid) {
                    $status = ['present', 'present', 'present', 'present', 'late', 'absent', 'excused'][rand(0, 6)];
                    AttendanceRecord::firstOrCreate(
                        ['class_id' => $class->id, 'student_id' => $sid, 'attendance_date' => $date],
                        [
                            'session_title' => 'Session '.$date,
                            'status' => $status,
                            'duration_minutes' => $status === 'present' ? rand(50, 90) : 0,
                            'joined_at' => $status === 'present' ? now()->parse($date)->setTime(8, rand(0, 59)) : null,
                            'left_at' => $status === 'present' ? now()->parse($date)->setTime(10, rand(0, 59)) : null,
                            'notes' => null,
                            'recorded_by' => $instructor->id,
                        ]
                    );
                }
            }
        }

        // ── Announcements ─────────────────────────────────────────────────
        $this->command?->info('BulkFifty: announcements...');
        $announcements = [
            'Reminder: Midterm exam schedule is now posted.',
            'Library extended hours this week for review.',
            'New optional practice materials uploaded.',
            'Class suspended on Friday due to holiday.',
            'Final project guidelines have been updated.',
            'Online consultation hours added this week.',
        ];
        foreach ($announcements as $a) {
            $ann = Announcement::firstOrCreate(
                ['title' => $a],
                [
                    'body' => $a.' — please check the portal for full details.',
                    'audience_type' => ['institution', 'course', 'class'][rand(0, 2)],
                    'course_id' => $course?->id,
                    'class_id' => $firstClass?->id,
                    'target_role_id' => $studentRole->id,
                    'attachment_media_id' => null,
                    'is_pinned' => false,
                    'publish_at' => now()->subDays(rand(1, 12)),
                    'unpin_at' => null,
                    'created_by' => $instructor->id,
                    'status' => 'published',
                ]
            );

            foreach (array_slice($students, 0, 8) as $stu) {
                AnnouncementView::firstOrCreate(
                    ['announcement_id' => $ann->id, 'user_id' => $stu->id],
                    ['viewed_at' => now()->subDays(rand(0, 6))]
                );
            }
        }

        // ── Calendar events ───────────────────────────────────────────────
        $this->command?->info('BulkFifty: calendar...');
        $eventDefs = [
            ['event_type' => 'exam', 'title' => 'Midterm Examination', 'offset' => 5],
            ['event_type' => 'quiz', 'title' => 'Module Quiz', 'offset' => 2],
            ['event_type' => 'assignment', 'title' => 'Assignment Due', 'offset' => 3],
            ['event_type' => 'virtual_class', 'title' => 'Online Meetup', 'offset' => 1],
            ['event_type' => 'announcement', 'title' => 'Announcement Posted', 'offset' => 6],
        ];
        foreach ($eventDefs as $ev) {
            foreach ($classes->take(4) as $class) {
                $start = now()->addDays($ev['offset'])->setTime(9, 0);
                CalendarEvent::firstOrCreate(
                    ['user_id' => $instructor->id, 'class_id' => $class->id, 'title' => $ev['title'].' - '.$class->code],
                    [
                        'course_id' => $class->course_id,
                        'description' => $ev['title'].' for '.$class->code,
                        'event_type' => $ev['event_type'],
                        'related_type' => \App\Models\ClassModel::class,
                        'related_id' => $class->id,
                        'start_at' => $start,
                        'end_at' => $start->copy()->addHours(1),
                        'is_all_day' => false,
                        'location' => 'Online / Room '.rand(101, 305),
                        'recurrence' => 'none',
                        'visibility' => 'class',
                        'created_by' => $instructor->id,
                    ]
                );
            }
        }

        // ── Virtual classes + attendees ───────────────────────────────────
        $this->command?->info('BulkFifty: virtual classes...');
        $vcDefs = [
            ['title' => 'Week Review Session', 'offset' => 0, 'status' => 'completed'],
            ['title' => 'Live Q&A', 'offset' => 2, 'status' => 'scheduled'],
            ['title' => 'Hands-on Workshop', 'offset' => -3, 'status' => 'completed'],
        ];
        foreach ($classes->take(3) as $class) {
            foreach ($vcDefs as $v) {
                $date = now()->addDays($v['offset'])->toDateString();
                $vc = VirtualClass::firstOrCreate(
                    ['class_id' => $class->id, 'title' => $v['title']],
                    [
                        'course_id' => $class->course_id,
                        'instructor_id' => $instructor->id,
                        'description' => $v['title'].' for '.$class->code,
                        'meeting_date' => $date,
                        'start_time' => '10:00:00',
                        'end_time' => '11:30:00',
                        'meeting_provider' => 'google_meet',
                        'meeting_url' => 'https://meet.google.com/demo-'.Str::lower(Str::random(6)),
                        'meeting_id' => null,
                        'meeting_password' => null,
                        'recurrence' => 'none',
                        'status' => $v['status'],
                        'created_by' => $instructor->id,
                    ]
                );

                if ($v['status'] === 'completed') {
                    $enrolled = Enrollment::where('class_id', $class->id)->pluck('student_id')->all();
                    foreach (array_slice($enrolled, 0, 10) as $sid) {
                        VirtualClassAttendee::firstOrCreate(
                            ['virtual_class_id' => $vc->id, 'user_id' => $sid],
                            [
                                'joined_at' => now()->parse($date)->setTime(10, rand(0, 5)),
                                'left_at' => now()->parse($date)->setTime(11, rand(10, 30)),
                                'duration_minutes' => rand(45, 80),
                                'attendance_status' => ['present', 'present', 'late', 'absent'][rand(0, 3)],
                            ]
                        );
                    }
                }
            }
        }

        // ── Grades: give each enrolled student a grade per class item ─────
        $this->command?->info('BulkFifty: grades...');
        foreach ($classes as $class) {
            $items = GradeItem::where('class_id', $class->id)->get();
            if ($items->isEmpty()) {
                continue;
            }
            $enrolled = Enrollment::where('class_id', $class->id)->pluck('student_id')->all();
            foreach ($enrolled as $sid) {
                foreach ($items->take(3) as $item) {
                    $pct = rand(62, 96);
                    Grade::firstOrCreate(
                        ['grade_item_id' => $item->id, 'student_id' => $sid],
                        [
                            'points' => round($pct / 100 * $item->max_points, 2),
                            'score_percent' => $pct,
                            'letter_grade' => $this->letterGrade($pct),
                            'graded_by' => $instructor->id,
                            'graded_at' => now()->subDays(rand(1, 10)),
                            'feedback' => ['Keep going!', 'Good progress — review the missed items.', 'Solid work. Keep it up.', 'Review chapters 3–5 for the next exam.'][rand(0, 3)],
                        ]
                    );
                }
            }
        }

        // ── Quiz attempts: each student attempts 1–2 quizzes per class ────
        $this->command?->info('BulkFifty: quiz attempts...');
        foreach ($classes as $class) {
            $quizzes = Quiz::where('class_id', $class->id)->get();
            if ($quizzes->isEmpty()) {
                continue;
            }
            $enrolled = Enrollment::where('class_id', $class->id)->pluck('student_id')->all();
            foreach (array_slice($enrolled, 0, 15) as $sid) {
                foreach ($quizzes->take(2) as $quiz) {
                    $qCount = $quiz->questions()->count();
                    if ($qCount === 0) {
                        continue;
                    }
                    $correct = rand(intdiv($qCount, 2), $qCount);
                    $pct = round($correct / $qCount * 100);
                    $attempt = QuizAttempt::firstOrCreate(
                        ['quiz_id' => $quiz->id, 'student_id' => $sid, 'attempt_number' => 1],
                        [
                            'started_at' => now()->subDays(rand(1, 9)),
                            'ended_at' => now()->subDays(rand(1, 9))->addMinutes(rand(5, 20)),
                            'submitted_at' => now()->subDays(rand(1, 9))->addMinutes(rand(5, 20)),
                            'time_spent_seconds' => rand(300, 900),
                            'score' => $correct,
                            'score_percent' => $pct,
                            'is_passed' => $pct >= 60,
                            'status' => 'graded',
                            'graded_by' => $instructor->id,
                            'graded_at' => now()->subDays(rand(1, 9)),
                        ]
                    );

                    foreach ($quiz->questions()->get() as $i => $q) {
                        if ($i >= $correct) {
                            break;
                        }
                        QuizAnswer::firstOrCreate(
                            ['quiz_attempt_id' => $attempt->id, 'question_id' => $q->id],
                            [
                                'answer_text' => $q->question_type === 'true_false' ? 'True' : null,
                                'points_awarded' => 1,
                                'is_correct' => true,
                                'graded_by' => $instructor->id,
                                'graded_at' => now()->subDays(rand(1, 9)),
                            ]
                        );
                    }
                }
            }
        }

        // ── Progress: lesson/module/course progress for the 50 students ───
        $this->command?->info('BulkFifty: progress...');
        foreach ($students as $stu) {
            $enrolls = Enrollment::where('student_id', $stu->id)->get();
            foreach ($enrolls as $en) {
                $class = $en->class()->first();
                if (! $class) {
                    continue;
                }
                $modules = Module::where('course_id', $class->course_id)->get();
                $totalLessons = 0;
                $completedLessons = 0;
                foreach ($modules as $mod) {
                    $lessons = Lesson::where('module_id', $mod->id)->get();
                    $totalLessons += $lessons->count();
                    foreach ($lessons as $lsn) {
                        $done = (bool) rand(0, 1);
                        LessonProgress::firstOrCreate(
                            ['lesson_id' => $lsn->id, 'student_id' => $stu->id],
                            [
                                'started_at' => now()->subDays(rand(3, 15)),
                                'completed_at' => $done ? now()->subDays(rand(1, 6)) : null,
                                'last_accessed_at' => now()->subHours(rand(1, 72)),
                                'progress_percent' => $done ? 100 : rand(0, 40),
                                'status' => $done ? 'completed' : 'in_progress',
                            ]
                        );
                        if ($done) {
                            $completedLessons++;
                        }
                    }
                    $modDone = rand(0, 1) === 1;
                    ModuleProgress::firstOrCreate(
                        ['module_id' => $mod->id, 'student_id' => $stu->id],
                        [
                            'started_at' => now()->subDays(rand(3, 15)),
                            'completed_at' => $modDone ? now()->subDays(rand(1, 6)) : null,
                            'lessons_completed' => $modDone ? $lessons->count() : rand(0, max(0, $lessons->count() - 1)),
                            'total_lessons' => $lessons->count(),
                            'progress_percent' => $modDone ? 100 : rand(10, 70),
                            'status' => $modDone ? 'completed' : 'in_progress',
                        ]
                    );
                }

                $overall = $totalLessons > 0 ? round($completedLessons / $totalLessons * 100) : 0;
                CourseProgress::firstOrCreate(
                    ['class_id' => $class->id, 'student_id' => $stu->id],
                    [
                        'started_at' => now()->subDays(rand(3, 15)),
                        'completed_at' => null,
                        'modules_completed' => 0,
                        'total_modules' => $modules->count(),
                        'lessons_completed' => $completedLessons,
                        'total_lessons' => $totalLessons,
                        'progress_percent' => $overall,
                        'final_grade' => null,
                        'status' => $overall >= 90 ? 'completed' : 'in_progress',
                    ]
                );
            }
        }

        $this->command?->info('BulkFifty: done. 50 students + full demo data created.');
    }

    protected function letterGrade(float $percent): string
    {
        return match (true) {
            $percent >= 90 => 'A',
            $percent >= 85 => 'B+',
            $percent >= 80 => 'B',
            $percent >= 75 => 'C+',
            $percent >= 70 => 'C',
            $percent >= 60 => 'D',
            default => 'F',
        };
    }
}

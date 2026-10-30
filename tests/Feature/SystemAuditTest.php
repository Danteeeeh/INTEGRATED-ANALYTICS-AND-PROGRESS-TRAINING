<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\ExamAttempt;
use App\Models\GradeItem;
use App\Models\LoginVerification;
use App\Models\Module;
use App\Models\Program;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuizAttempt;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Models\VirtualClass;
use App\Services\BackupService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route as Router;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Whole-system audit.
 *
 * Two passes:
 *  1. Probe every GET route in the application, as the role its middleware
 *     actually demands, against real seeded entities.
 *  2. For every page that renders, scrape the HTML for every link, form and
 *     button, and confirm the target really exists and accepts the HTTP method
 *     the markup claims. A button pointing at a dead route is a 404 the user
 *     discovers by clicking, which is exactly what this is meant to catch.
 */
class SystemAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, User> */
    protected array $actors = [];

    /** @var array<string, mixed> */
    protected array $ids = [];

    /** @var array<string, mixed> */
    protected array $lazyCache = [];

    /**
     * Set true to let exceptions escape instead of being rendered as a 4xx/5xx.
     * Off by default; flip it locally when you need the real stack trace of a
     * route the audit flags as a server error.
     */
    protected bool $renderExceptions = false;

    /**
     * A broad route/link sweep is only useful while it remains broad. Keep a
     * deliberate lower bound so a fixture, middleware, or route-list change
     * cannot silently reduce the audit to a handful of pages.
     */
    protected const MINIMUM_AUDITED_PAGES = 50;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $admin      = User::factory()->admin()->create(['status' => 'active', 'email' => 'audit.admin@lms.local']);
        $instructor = User::factory()->instructor()->create(['status' => 'active', 'email' => 'audit.instructor@lms.local']);
        $student    = User::factory()->student()->create(['status' => 'active', 'email' => 'audit.student@lms.local']);
        // The AA panel is a real role; without an actor its routes only ever
        // answer 403 and the audit cannot tell a broken page from a guarded one.
        $aaStaff = User::factory()->create([
            'status' => 'active',
            'email' => 'audit.aa@lms.local',
            'role_id' => Role::where('slug', Role::AA_STAFF)->firstOrFail()->id,
        ]);

        $this->actors = [
            'admin' => $admin,
            'instructor' => $instructor,
            'student' => $student,
            'aa_staff' => $aaStaff,
        ];

        // Media rows are only downloadable while their bytes exist on the disk.
        Storage::fake('local');
        Storage::disk('local')->put('audit/audit.txt', 'audit file');

        // A backup download is only reachable when the file really exists.
        File::put(app(BackupService::class)->directory().'/audit.sql', "-- audit backup\n");

        $period = AcademicPeriod::create([
            'name' => 'Audit Term',
            'code' => 'AT-2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => true,
            'status' => 'active',
        ]);

        $department = Department::create(['name' => 'Audit Dept', 'code' => 'AUD', 'status' => 'active']);
        $program    = Program::create([
            'name' => 'Audit Program',
            'code' => 'AUD-P',
            'department_id' => $department->id,
            'status' => 'active',
        ]);
        $section = Section::create([
            'name' => 'Audit Section',
            'code' => 'AUD-1',
            'program_id' => $program->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create([
            'status' => 'published',
            'created_by' => $admin->id,
            'academic_period_id' => $period->id,
            'program_id' => $program->id,
            'code' => 'AUD-C1',
            'title' => 'Audit Course',
        ]);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'section_id' => $section->id,
            'academic_period_id' => $period->id,
            'status' => 'active',
            'code' => 'AUD-C1-S1',
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $module = Module::create([
            'course_id' => $course->id,
            'title' => 'Audit Module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $instructor->id,
        ]);

        $lesson = \App\Models\Lesson::create([
            'module_id' => $module->id,
            'title' => 'Audit Lesson',
            'content' => 'Audit lesson body',
            'status' => 'published',
            'position' => 1,
            'lesson_type' => 'text',
            'created_by' => $instructor->id,
        ]);

        // A submission fixture already exists by the time the audit opens the
        // submit form (an instructor route creates it first), so the form only
        // renders when resubmission is allowed. That is a real mode, not a
        // concession: without it the page is a redirect and never audited.
        $assignment = Assignment::create([
            'class_id' => $class->id,
            'module_id' => $module->id,
            'title' => 'Audit Assignment',
            'slug' => 'audit-assign-'.uniqid(),
            'points' => 100,
            'allow_resubmission' => true,
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDays(5),
            'created_by' => $instructor->id,
        ]);

        // attempt_limit defaults to 1 and the audit already holds one submitted
        // attempt for these, so Start would answer "no attempts left" instead
        // of opening a paper.
        $quiz = \App\Models\Quiz::create([
            'class_id' => $class->id,
            'module_id' => $module->id,
            'title' => 'Audit Quiz',
            'slug' => 'audit-quiz-'.uniqid(),
            'attempt_limit' => 5,
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);

        $exam = \App\Models\Exam::create([
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Audit Exam',
            'slug' => 'audit-exam-'.uniqid(),
            'exam_type' => 'midterm',
            'duration_minutes' => 60,
            'attempt_limit' => 5,
            'status' => 'published',
            'created_by' => $instructor->id,
        ]);

        // The built-in classroom is LiveKit-only: without a provider and room
        // name its GET room page answers 404 for a reason that has nothing to
        // do with the page being broken. Keep it open around "now" so the
        // auto-close status sync does not end it before the probe.
        $virtualClass = VirtualClass::create([
            'class_id' => $class->id,
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Audit Virtual Class',
            'status' => VirtualClass::STATUS_ONGOING,
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'audit-room',
            'meeting_date' => today(),
            'start_time' => now()->subMinutes(5)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'created_by' => $instructor->id,
        ]);

        $questionBank = QuestionBank::create([
            'title' => 'Audit Bank',
            'course_id' => $course->id,
            'class_id' => $class->id,
            'created_by' => $instructor->id,
            'is_shared' => false,
            'status' => 'active',
        ]);

        $this->ids = compact(
            'period', 'department', 'program', 'section',
            'course', 'class', 'enrollment', 'module', 'lesson',
            'assignment', 'quiz', 'exam', 'virtualClass', 'questionBank',
            'admin', 'instructor', 'student',
        );

        $this->ids['user'] = $student->id;

        // Both Start routes refuse to open a paper with nothing on it, so each
        // assessment gets the same served question.
        $auditQuestion = $this->auditQuestion();
        $quiz->questions()->attach($auditQuestion->id, ['position' => 1]);
        $exam->questions()->attach($auditQuestion->id, ['order' => 1]);

        // /login/verify only renders while a sign-in is pending; without this
        // row the page can only ever bounce back to /login.
        LoginVerification::create([
            'user_id' => $student->id,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        File::delete(app(BackupService::class)->directory().'/audit.sql');

        parent::tearDown();
    }

    /** Run $factory once per $key and reuse the value for every later call. */
    protected function lazy(string $key, callable $factory)
    {
        $this->lazyCache[$key] ??= $factory();

        return $this->lazyCache[$key];
    }

    /**
     * The question both assessments sit, with one right and one wrong choice.
     *
     * Attached to the quiz and the exam in setUp(): their Start routes answer a
     * redirect ("no questions yet") instead of a paper when the fixture has
     * none, which used to leave the attempt pages unaudited.
     */
    protected function auditQuestion(): Question
    {
        return $this->lazy('question', function () {
            $question = Question::create([
                'question_bank_id' => $this->ids['questionBank']->id,
                'question_type' => Question::TYPE_MULTIPLE_CHOICE,
                'question_text' => 'Audit question?',
                'difficulty' => Question::DIFFICULTY_MEDIUM,
                'default_points' => 1,
                'created_by' => $this->ids['instructor']->id,
                'status' => Question::STATUS_ACTIVE,
            ]);

            $question->choices()->createMany([
                ['choice_text' => 'Correct choice', 'points' => 1, 'is_correct' => true, 'position' => 0],
                ['choice_text' => 'Wrong choice', 'points' => 0, 'is_correct' => false, 'position' => 1],
            ]);

            return $question;
        });
    }

    /**
     * Query strings the audit has to send.
     *
     * These endpoints validate their input and bounce back with errors when it
     * is missing, so the page — or the JSON the page calls — was never seen.
     */
    protected function queryFor(string $uri): string
    {
        return match ($uri) {
            'instructor/dashboard/export' => '?type=students&format=csv',
            'instructor/dashboard/search', 'student/dashboard/search' => '?query=audit&type=all',
            'instructor/dashboard/analytics' => '?period=month&type=performance&class_id='.$this->ids['class']->id,
            default => '',
        };
    }

    /**
     * A public page rendered for a signed-out visitor.
     *
     * The login, register and OTP screens sit behind the `guest` middleware:
     * every signed-in actor is redirected away from them, so an audit that only
     * ever signs in can never see them render.
     */
    protected function guestGet(string $uri, array $session = [])
    {
        $this->app['auth']->forgetGuards();

        $response = $session
            ? $this->withSession($session)->get('/'.$uri)
            : $this->get('/'.$uri);

        if ($session) {
            $this->flushSession();
        }

        $this->app['auth']->forgetGuards();

        return $response;
    }

    /** Extra session a guest-only page needs before it will render. */
    protected function guestSession(string $uri): array
    {
        if ($uri !== 'login/verify') {
            return [];
        }

        return [
            'login_otp' => [
                'user_id' => $this->ids['student']->id,
                'email' => $this->ids['student']->email,
                'created_at' => now()->timestamp,
            ],
        ];
    }

    /** Fill {placeholders} in a URI with seeded ids. */
    protected function uriFor(string $uri): string
    {
        $ids = $this->ids;

        // The edit page only accepts a *scheduled* class, while the LiveKit
        // room only opens for the *ongoing* one; the same row cannot be both.
        $virtualClassId = str_ends_with($uri, '/edit')
            ? $this->lazy('scheduledVirtualClass', fn () => VirtualClass::create([
                'class_id' => $ids['class']->id,
                'course_id' => $ids['course']->id,
                'instructor_id' => $ids['instructor']->id,
                'title' => 'Audit Scheduled Virtual Class',
                'status' => VirtualClass::STATUS_SCHEDULED,
                'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
                'livekit_room_name' => 'audit-scheduled-room',
                'meeting_date' => today()->addDay(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'created_by' => $ids['instructor']->id,
            ])->id)
            : $ids['virtualClass']->id;

        // {attempt} is an ExamAttempt on exam routes and a QuizAttempt on quiz
        // routes; one shared row cannot satisfy both bindings.
        $attemptId = str_contains($uri, '/exams/')
            ? $this->lazy('examAttempt', fn () => ExamAttempt::create([
                'exam_id'        => $ids['exam']->id,
                'student_id'     => $ids['student']->id,
                'attempt_number' => 1,
                'status'         => 'submitted',
                'started_at'     => now()->subMinutes(30),
                'submitted_at'   => now()->subMinutes(5),
            ])->id)
            : $this->lazy('attempt', fn () => QuizAttempt::create([
                'quiz_id'        => $ids['quiz']->id,
                'student_id'     => $ids['student']->id,
                'attempt_number' => 1,
                'status'         => QuizAttempt::STATUS_SUBMITTED,
                'started_at'     => now(),
                'submitted_at'   => now(),
            ])->id);

        // Bare keys: the substitution regex hands back the name without braces.
        $map = [
            'course'         => $ids['course']->id,
            'class'          => $ids['class']->id,
            'module'         => $ids['module']->id,
            'lesson'         => $ids['lesson']->id,
            'quiz'           => $ids['quiz']->id,
            'exam'           => $ids['exam']->id,
            'assignment'     => $ids['assignment']->id,
            'student'        => $ids['student']->id,
            'user'           => $ids['user'],
            'instructor'     => $ids['instructor']->id,
            'instructorId'   => $ids['instructor']->id,
            'enrollment'     => $ids['enrollment']->id,
            'academicPeriod' => $ids['period']->id,
            'period'         => $ids['period']->id,
            'department'     => $ids['department']->id,
            'program'        => $ids['program']->id,
            'section'        => $ids['section']->id,
            'questionBank'   => $ids['questionBank']->id,
            'question_bank'  => $ids['questionBank']->id,
            'virtualClass'   => $virtualClassId,

            'submission' => $this->lazy('submission', fn () => AssignmentSubmission::create([
                'assignment_id'  => $ids['assignment']->id,
                'student_id'     => $ids['student']->id,
                'attempt_number' => 1,
                'submission_text' => 'Audit text',
                'submitted_at'   => now(),
                'status'         => AssignmentSubmission::STATUS_SUBMITTED,
            ])->id),

            'gradeItem' => $this->lazy('gradeItem', fn () => GradeItem::firstOrCreate(
                ['class_id' => $ids['class']->id, 'title' => 'Audit Item'],
                ['item_type' => 'assignment', 'max_points' => 100, 'factor' => 1, 'position' => 0]
            )->id),

            // Both the instructor edit page and the student show page require
            // the announcement to belong to this course, and the instructor
            // must be its author.
            'announcement' => $this->lazy('announcement', fn () => Announcement::create([
                'title'         => 'Audit Announcement',
                'body'          => 'Audit body',
                'audience_type' => Announcement::AUDIENCE_COURSE,
                'course_id'     => $ids['course']->id,
                'created_by'    => $ids['instructor']->id,
                'status'        => 'published',
                'publish_at'    => now(),
            ])->id),

            'question' => $this->auditQuestion()->id,

            'rubric' => $this->lazy('rubric', fn () => \App\Models\Rubric::create([
                'title'      => 'Audit Rubric',
                'course_id'  => $ids['course']->id,
                'created_by' => $ids['instructor']->id,
                'status'     => 'active',
            ])->id),

            'competency' => $this->lazy('competency', fn () => \App\Models\Competency::create([
                'name'         => 'Audit Competency',
                'code'         => 'AUD-COMP',
                'description'  => 'Audit competency',
                'framework_id' => $this->lazy('framework', fn () => \App\Models\CompetencyFramework::create([
                    'name'        => 'Audit Framework',
                    'code'        => 'AUD-FW',
                    'description' => 'Audit framework',
                    'created_by'  => $ids['admin']->id,
                    'status'      => 'active',
                ])->id),
                'status'       => 'active',
            ])->id),

            'role' => $this->lazy('role', fn () => Role::where('slug', 'instructor')->value('id')),

            'event' => $this->lazy('event', fn () => \App\Models\CalendarEvent::create([
                'title'      => 'Audit Event',
                'start_at'   => now(),
                'end_at'     => now()->addDay(),
                'event_type' => 'course',
                'created_by' => $ids['admin']->id,
            ])->id),

            'attendanceRecord' => $this->lazy('attendanceRecord', fn () => \App\Models\AttendanceRecord::create([
                'class_id'        => $ids['class']->id,
                'student_id'      => $ids['student']->id,
                'attendance_date' => now()->toDateString(),
                'status'          => 'present',
                'marked_by'       => $ids['instructor']->id,
            ])->id),

            'attendance' => $this->lazy('attendance', fn () => \App\Models\AttendanceRecord::create([
                'class_id'        => $ids['class']->id,
                'student_id'      => $ids['student']->id,
                'attendance_date' => now()->addDay()->toDateString(),
                'status'          => 'late',
                'marked_by'       => $ids['instructor']->id,
            ])->id),

            'auditLog' => $this->lazy('auditLog', fn () => \App\Models\AuditLog::create([
                'user_id'       => $ids['admin']->id,
                'action'        => 'audit.probe',
                'resource_type' => 'Course',
                'resource_id'   => $ids['course']->id,
                'ip_address'    => '127.0.0.1',
                'user_agent'    => 'audit',
            ])->id),

            'learningPlan' => $this->lazy('learningPlan', fn () => \App\Models\LearningPlan::create([
                'student_id' => $ids['student']->id,
                'title'      => 'Audit Plan',
                'status'     => 'active',
                'created_by' => $ids['student']->id,
            ])->id),

            'attempt' => $attemptId,

            'termsOfService' => $this->lazy('termsOfService', fn () => \App\Models\TermsOfService::create([
                'title'      => 'Audit Terms',
                'content'    => 'Audit terms body',
                'version'    => '1.0',
                'is_active'  => true,
                'created_by' => $ids['admin']->id,
            ])->id),

            'mediaFile' => $this->lazy('mediaFile', fn () => \App\Models\MediaFile::create([
                'original_name' => 'audit.txt',
                'file_name'     => 'audit.txt',
                'path'          => 'audit/audit.txt',
                'disk'          => 'local',
                'mime_type'     => 'text/plain',
                'size'          => 4,
                'extension'     => 'txt',
                'uploader_id'   => $ids['instructor']->id,
            ])->id),

            // The backup download only resolves .sql/.json names that exist in
            // the backup directory; setUp() writes this one.
            'file'        => 'audit.sql',
            'student_id'  => $ids['student']->id,
        ];

        $uri = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => $map[$m[1]] ?? $m[0], $uri);

        return $uri.$this->queryFor($uri);
    }

    /** Which role's middleware demands this route. */
    protected function roleFor(RoutingRoute $route): ?string
    {
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $m) {
            if (str_starts_with($m, 'role:')) {
                $roles = explode(',', substr($m, 5));

                foreach (['instructor', 'admin', 'student', 'aa_staff'] as $known) {
                    if (in_array($known, $roles, true)) {
                        return $known;
                    }
                }

                return null;
            }
        }

        return null;
    }

    /** GET routes only, api/auth/console excluded, optional params skipped. */
    protected function gettableRoutes(): array
    {
        $out = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '_') || str_starts_with($uri, 'storage/')) {
                continue;
            }

            // Skip routes with genuinely optional params we cannot resolve.
            if (preg_match('/\{(\w+)\?\}/', $uri)) {
                continue;
            }

            $out[] = $route;
        }

        return $out;
    }

    /**
     * Pages that must continue to render in the generic sweep.
     *
     * These parameterized virtual-class routes were added after the audit's
     * first version. They are important enough to guard explicitly: an
     * accidental route or fixture change should not let the generic resolver
     * skip the student or instructor class entry points without a failure.
     *
     * @return array<int, string>
     */
    protected function requiredRenderedUris(): array
    {
        return [
            'instructor/classes/'.$this->ids['class']->id.'/virtual_classes',
            'student/classes/'.$this->ids['class']->id.'/virtual_classes',
            'student/classes/'.$this->ids['class']->id.'/virtual_classes/'.$this->ids['virtualClass']->id,
        ];
    }

    /**
     * A route that opens an output buffer and never closes it leaks bytes into
     * the *next* response. Note it, then restore the depth so one bad page
     * cannot cascade.
     */
    protected function drainBuffers(string $uri, array &$leaks, int $depth): void
    {
        while (ob_get_level() > $depth) {
            $leaks[] = sprintf('%s left %d unclosed output buffer(s)', $uri, ob_get_level() - $depth);
            ob_end_clean();
        }
    }

    public function test_every_get_route_renders_for_its_role(): void
    {
        $failures = [];
        $probed   = 0;
        $rendered   = [];
        $leaks      = [];
        $skipped    = [];
        $redirected = [];
        $depth      = ob_get_level();

        foreach ($this->gettableRoutes() as $route) {
            $uri = $this->uriFor($route->uri());

            if (str_contains($uri, '{')) {
                $skipped[] = sprintf('%s (unresolved parameter)', $route->uri());
                continue; // unresolvable parameter, nothing to probe
            }

            $role = $this->roleFor($route);
            $actor = $role ? ($this->actors[$role] ?? null) : null;

            // No role middleware: try every actor and keep the first that works,
            // so shared/guest pages are covered too.
            $candidates = $actor ? [$actor] : array_values($this->actors);

            if (! $actor) {
                $skipped[] = sprintf('%s (no role middleware, probing all actors)', $uri);
            }

            $probed++;
            $status = 0;
            $body = '';

            foreach ($candidates as $candidate) {
                if ($this->renderExceptions) {
                    $this->withoutExceptionHandling();
                }

                try {
                    $response = $this->actingAs($candidate)->get('/'.$uri);
                } catch (\Throwable $e) {
                    $failures[] = sprintf(
                        '%s  [%s]  %s: %s  @ %s:%d',
                        $uri,
                        $role ?? 'any',
                        class_basename($e),
                        trim(explode("\n", $e->getMessage())[0]),
                        basename($e->getFile()),
                        $e->getLine()
                    );
                    $this->drainBuffers($uri, $leaks, $depth);
                    continue 2;
                }

                $this->drainBuffers($uri, $leaks, $depth);
                $status = $response->getStatusCode();
                $body   = $response->getContent();

                // A redirect for this actor just means the page belongs to a
                // different role; keep looking rather than recording a failure.
                if ($status >= 300 && $status < 400) {
                    continue;
                }

                break;
            }

            // Guest-only screens (login, register, the OTP form) bounce every
            // signed-in actor; only a visitor sees them.
            if ($status >= 300 && $status < 400) {
                try {
                    $response = $this->guestGet($uri, $this->guestSession($uri));
                } catch (\Throwable $e) {
                    $failures[] = sprintf(
                        '%s  [guest]  %s: %s',
                        $uri,
                        class_basename($e),
                        trim(explode("\n", $e->getMessage())[0])
                    );
                    $this->drainBuffers($uri, $leaks, $depth);
                    continue;
                }

                $this->drainBuffers($uri, $leaks, $depth);
                $status = $response->getStatusCode();
                $body   = $response->getContent();
            }

            if ($status >= 300 && $status < 400) {
                $redirected[] = explode('?', $uri)[0];
            }

            if ($status >= 400) {
                $note  = '';

                if (preg_match('/View \[([^\]]+)\] not found/', $body, $m)) {
                    $note = 'missing view: '.$m[1];
                } elseif (preg_match('/Class "([^"]+)" not found/', $body, $m)) {
                    $note = 'missing class: '.$m[1];
                } elseif (preg_match('/Call to undefined method ([^<"\']{0,90})/', $body, $m)) {
                    $note = trim($m[1]);
                } elseif (preg_match('/SQLSTATE[^<"]{0,110}/', $body, $m)) {
                    $note = trim($m[0]);
                } elseif (preg_match('/<title>(.*?)<\/title>/s', $body, $m)) {
                    $note = trim(html_entity_decode(strip_tags($m[1])));
                }

                $failures[] = sprintf('%s  [%s]  %d  %s', $uri, $role ?? 'any', $status, $note);
            } elseif ($status === 200) {
                $rendered[$uri] = $body;
            }
        }

        $this->assertSame([], $failures, sprintf(
            "Probed %d GET routes, %d rendered OK, %d skipped.\nGET route failures:\n",
            $probed,
            count($rendered),
            count($skipped)
        ).implode("\n", $failures));

        $this->assertSame([], $leaks, "Unclosed output buffers:\n".implode("\n", $leaks));

        $this->assertGreaterThanOrEqual(
            self::MINIMUM_AUDITED_PAGES,
            count($rendered),
            sprintf('The GET-route sweep rendered only %d pages; expected at least %d.', count($rendered), self::MINIMUM_AUDITED_PAGES)
        );

        $this->assertSame(
            [],
            array_values(array_diff($this->requiredRenderedUris(), array_keys($rendered))),
            "Required pages missing from the GET-route sweep:\n"
        );

        // "/" is a forwarding stub to the login screen; everything else must
        // now land on a page or a download. A new redirect here means a route
        // the audit silently stopped checking.
        sort($redirected);
        $this->assertSame(
            ['/'],
            $redirected,
            "Routes that still redirect instead of rendering:\n".implode("\n", $redirected)
        );
    }

    public function test_every_link_and_button_on_rendered_pages_points_at_a_real_route(): void
    {
        $problems = [];
        $checked  = 0;
        $pages    = 0;
        $raw      = 0;
        $seen     = [];

        foreach ($this->gettableRoutes() as $route) {
            $uri = $this->uriFor($route->uri());

            if (str_contains($uri, '{')) {
                continue;
            }

            $role = $this->roleFor($route);
            $actor = $role ? ($this->actors[$role] ?? null) : null;
            $candidates = $actor ? [$actor] : array_values($this->actors);

            $html = null;

            foreach ($candidates as $candidate) {
                try {
                    $response = $this->actingAs($candidate)->get('/'.$uri);
                } catch (\Throwable) {
                    continue;
                }

                if ($response->getStatusCode() === 200) {
                    $html = $response->getContent();
                    break;
                }
            }

            if ($html === null) {
                continue;
            }

            $pages++;
            $base = explode('?', $uri)[0];

            foreach ($this->extractTargets($html) as $target) {
                $raw++;
                $url   = $target['url'];
                $path  = parse_url($url, PHP_URL_PATH);
                $query = parse_url($url, PHP_URL_QUERY);

                if (! $path || $path === '/') {
                    continue;
                }

                // Only internal, app-owned targets.
                if (str_starts_with($url, 'http') && ! str_contains($url, request()->getHost())) {
                    continue;
                }

                if (str_starts_with($path, '/storage/') || str_starts_with($path, '/images/') || str_starts_with($path, '/css/') || str_starts_with($path, '/js/')) {
                    continue;
                }

                // Client-side placeholders and in-page anchors are not targets.
                if (str_contains($url, '${') || str_contains($url, '#{') || $path === '#' || str_starts_with($url, '#')) {
                    continue;
                }

                // Resolve against the current page so relative links work too.
                if (! str_starts_with($path, '/')) {
                    $path = rtrim(dirname('/'.$base), '/').'/'.$path;
                }

                $claimed = strtoupper($target['method']);

                // The markup claims a verb; resolve with that verb so a POST
                // form is not mistaken for a dead GET link.
                $probe = $path.($query ? '?'.$query : '');

                if (isset($seen[$claimed.' '.$probe])) {
                    continue;
                }

                $seen[$claimed.' '.$probe] = true;
                $checked++;

                try {
                    $match = Router::getRoutes()->match(
                        \Illuminate\Http\Request::create($probe, $claimed)
                    );
                } catch (\Throwable $e) {
                    $problems[] = sprintf(
                        '%s -> %s "%s" claimed %s: %s',
                        $base,
                        $target['kind'],
                        $url,
                        $claimed,
                        class_basename($e).': '.trim(explode("\n", $e->getMessage())[0])
                    );
                    continue;
                }

                $accepted = implode('/', array_diff($match->methods(), ['HEAD']));

                if ($claimed === 'GET' && ! in_array('GET', $match->methods(), true)) {
                    $problems[] = sprintf('%s -> link "%s" is not GET-able (route allows %s)', $base, $url, $accepted);
                }
            }
        }

        $this->assertGreaterThanOrEqual(
            self::MINIMUM_AUDITED_PAGES,
            $pages,
            sprintf('The link audit rendered only %d pages; expected at least %d.', $pages, self::MINIMUM_AUDITED_PAGES)
        );

        $summary = sprintf(
            "Audited %d pages, %d link/form targets (%d unique).\n",
            $pages,
            $raw,
            $checked
        );

        $this->assertSame([], $problems, $summary."Broken links / wrong verbs:\n".implode("\n", $problems));
    }

    /**
     * Pull every navigable target out of the markup with the verb it claims.
     */
    protected function extractTargets(string $html): array
    {
        $targets = [];

        // <a href> — always claims GET.
        if (preg_match_all('/<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            foreach ($m[1] as $url) {
                $targets[] = ['url' => html_entity_decode($url), 'method' => 'GET', 'kind' => 'link'];
            }
        }

        // <form action method> plus any _method override inside.
        if (preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $html, $m, PREG_SET_ORDER)) {
            foreach ($m as $form) {
                $attrs = $form[1];
                $body  = $form[2];

                if (! preg_match('/\baction\s*=\s*["\']([^"\']+)["\']/i', $attrs, $a)) {
                    continue;
                }

                $url    = html_entity_decode($a[1]);
                $method = 'GET';

                if (preg_match('/\bmethod\s*=\s*["\']?(\w+)/i', $attrs, $mm)) {
                    $method = strtoupper($mm[1]);
                }

                if (preg_match('/\bname\s*=\s*["\']_method["\']\s+value\s*=\s*["\'](\w+)/i', $body, $ov)) {
                    $method = strtoupper($ov[1]);
                }

                $targets[] = ['url' => $url, 'method' => $method, 'kind' => 'form'];
            }
        }

        // <button formaction> overrides the enclosing form's target.
        if (preg_match_all('/<button\b[^>]*\bformaction\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $m)) {
            foreach ($m[1] as $url) {
                $targets[] = ['url' => html_entity_decode($url), 'method' => 'POST', 'kind' => 'button'];
            }
        }

        return $targets;
    }
}
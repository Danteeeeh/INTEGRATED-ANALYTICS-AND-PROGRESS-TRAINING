<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LearningPlan;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Full-app audit: probes every GET route for every role with real seeded
 * entities (courses, classes, enrollments, learning plans, assistant,
 * suggest-feedback) and reports the real HTTP status for each.
 */
class FullAppFunctionAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $gmailAdmin;
    private User $instructor;
    private User $student;
    private User $registrar;

    private Course $course;
    private ClassModel $class;
    private Enrollment $enrollment;
    private Assignment $assignment;
    private Module $module;
    private Quiz $quiz;
    private LearningPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class, UserSeeder::class]);

        $this->admin = User::where('email', 'admin@lms.local')->firstOrFail();
        $this->gmailAdmin = User::where('email', 'johncedrickdayandante6@gmail.com')->firstOrFail();
        $this->instructor = User::where('email', 'instructor@lms.local')->firstOrFail();
        $this->student = User::where('email', 'student@lms.local')->firstOrFail();
        $this->registrar = User::where('email', 'registrar@lms.local')->firstOrFail();

        // Real entities owned by the instructor, enrolled by the student.
        $this->course = Course::factory()->create(['status' => 'published', 'created_by' => $this->instructor->id]);
        $this->class = ClassModel::factory()->create([
            'course_id' => $this->course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);
        $this->enrollment = Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $this->module = Module::create([
            'course_id' => $this->course->id,
            'title' => 'Audit Module',
            'status' => Module::STATUS_PUBLISHED,
            'position' => 1,
            'created_by' => $this->instructor->id,
        ]);
        $this->assignment = Assignment::create([
            'class_id' => $this->class->id,
            'title' => 'Audit Assignment',
            'slug' => 'audit-assignment-'.uniqid(),
            'points' => 100,
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDays(5),
            'created_by' => $this->instructor->id,
        ]);
        $this->quiz = Quiz::create([
            'class_id' => $this->class->id,
            'title' => 'Audit Quiz',
            'slug' => 'audit-quiz-'.uniqid(),
            'status' => 'published',
            'created_by' => $this->instructor->id,
        ]);
        $this->plan = app(\App\Services\LearningPlanService::class)->generateForStudent($this->student);
    }

    public function test_all_get_routes_probe_without_errors(): void
    {
        $failures = [];

        $probes = [
            // ---------- public / auth ----------
            ['GET', '/', 'guest'],
            ['GET', '/login', 'guest'],
            ['GET', '/register', 'guest'],

            // ---------- admin (both admin accounts) ----------
            ['GET', '/admin/dashboard', 'admin'],
            ['GET', '/admin/users', 'admin'],
            ['GET', '/admin/users/'.$this->student->id, 'admin'],
            ['GET', '/admin/students', 'admin'],
            ['GET', '/admin/students/'.$this->student->id, 'admin'],
            ['GET', '/admin/instructors', 'admin'],
            ['GET', '/admin/instructors/'.$this->instructor->id, 'admin'],
            ['GET', '/admin/courses', 'admin'],
            ['GET', '/admin/courses/'.$this->course->id, 'admin'],
            ['GET', '/admin/classes', 'admin'],
            ['GET', '/admin/classes/'.$this->class->id, 'admin'],
            ['GET', '/admin/enrollments', 'admin'],
            ['GET', '/admin/enrollments/'.$this->enrollment->id, 'admin'],
            ['GET', '/admin/assignments', 'admin'],
            ['GET', '/admin/quizzes', 'admin'],
            ['GET', '/admin/rubrics', 'admin'],
            ['GET', '/admin/competencies', 'admin'],
            ['GET', '/admin/attendance', 'admin'],
            ['GET', '/admin/calendar', 'admin'],
            ['GET', '/admin/announcements', 'admin'],
            ['GET', '/admin/virtual_classes', 'admin'],
            ['GET', '/admin/gradebook', 'admin'],
            ['GET', '/admin/reports', 'admin'],
            ['GET', '/admin/settings', 'admin'],
            ['GET', '/admin/audit-logs', 'admin'],
            ['GET', '/admin/academic-periods', 'admin'],
            ['GET', '/admin/notifications', 'admin'],
            ['GET', '/admin/search?q=audit', 'admin'],
            ['GET', '/admin/dashboard', 'gmail'],

            // ---------- feature picker pages (sidebar routing fix) ----------
            ['GET', '/student/courses?feature=assignments', 'student'],
            ['GET', '/student/courses?feature=quizzes', 'student'],
            ['GET', '/student/courses?feature=announcements', 'student'],
            ['GET', '/instructor/courses?feature=assignments', 'instructor'],
            ['GET', '/instructor/courses?feature=quizzes', 'instructor'],
            ['GET', '/instructor/courses?feature=announcements', 'instructor'],

            // ---------- instructor ----------
            ['GET', '/instructor/dashboard', 'instructor'],
            ['GET', '/instructor/courses', 'instructor'],
            ['GET', '/instructor/classes', 'instructor'],
            ['GET', '/instructor/classes/'.$this->class->id, 'instructor'],
            ['GET', '/instructor/classes/'.$this->class->id.'/roster', 'instructor'],
            ['GET', '/instructor/classes/'.$this->class->id.'/gradebook', 'instructor'],
            ['GET', '/instructor/classes/'.$this->class->id.'/learning-plans', 'instructor'],
            ['GET', '/instructor/enrollments', 'instructor'],
            ['GET', '/instructor/enrollments/'.$this->enrollment->id, 'instructor'],
            ['GET', '/instructor/courses/'.$this->course->id, 'instructor'],

            // ---------- student ----------
            ['GET', '/student/dashboard', 'student'],
            ['GET', '/student/courses', 'student'],
            ['GET', '/student/classes', 'student'],
            ['GET', '/student/enrollments', 'student'],
            ['GET', '/student/learning-plans', 'student'],
            ['GET', '/student/learning-plans/'.$this->plan->id, 'student'],
            ['GET', '/student/assistant', 'student'],
            ['GET', '/student/courses/'.$this->course->id, 'student'],
            ['GET', '/student/classes/'.$this->class->id, 'student'],
            ['GET', '/student/dashboard/analytics', 'student'],
        ];

        $actors = [
            'admin' => $this->admin,
            'gmail' => $this->gmailAdmin,
            'instructor' => $this->instructor,
            'student' => $this->student,
            'guest' => null,
        ];

        foreach ($probes as [$method, $uri, $as]) {
            $response = $as === 'guest'
                ? $this->call($method, $uri)
                : $this->actingAs($actors[$as])->call($method, $uri);

            $status = $response->getStatusCode();
            $line = sprintf('%-5s %-58s as=%-11s => %d', $method, $uri, $as, $status);

            if ($status >= 400) {
                $body = $response->getContent();
                if (preg_match('/<title>(.*?)<\/title>/s', $body, $m)) {
                    $line .= '  || '.trim(html_entity_decode(strip_tags($m[1])));
                }
                if (preg_match('/(View \[[^\]]+\] not found|Call to undefined method [^<"\']{0,80}|Class "[^"]+" not found|Target class [^<]+ does not exist|SQLSTATE[^<"]{0,120})/', $body, $m)) {
                    $line .= '  >> '.$m[1];
                }
                $failures[] = $line;
            }
        }

        $this->assertSame([], $failures, "Full-app audit found error responses:\n".implode("\n", $failures));
    }
    public function test_write_actions_work_for_each_role(): void
    {
        // ---- student write actions ----
        $studentResponse = $this->actingAs($this->student)->post('/student/learning-plans/generate');
        $this->assertTrue(in_array($studentResponse->getStatusCode(), [200, 302]), 'student generate plan got '.$studentResponse->getStatusCode());

        // Plan item toggle
        $item = $this->plan->items()->first();
        $patch = $this->actingAs($this->student)->patch('/student/learning-plans/'.$this->plan->id.'/items/'.$item->id, [
            'status' => 'in_progress',
        ]);
        $this->assertTrue(in_array($patch->getStatusCode(), [200, 302]), 'student item toggle got '.$patch->getStatusCode());

        // ---- instructor write actions ----
        // Suggest a plan for the enrolled student (idempotent, should not 500)
        $suggest = $this->actingAs($this->instructor)->post('/instructor/classes/'.$this->class->id.'/learning-plans/'.$this->student->id);
        $this->assertTrue(in_array($suggest->getStatusCode(), [200, 302]), 'instructor suggest plan got '.$suggest->getStatusCode());

        // Suggest feedback (needs a submission with grade item)
        $submission = \App\Models\AssignmentSubmission::create([
            'assignment_id' => $this->assignment->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'submission_text' => 'Audit submission',
            'submitted_at' => now(),
            'status' => \App\Models\AssignmentSubmission::STATUS_SUBMITTED,
        ]);
        \App\Models\GradeItem::create([
            'class_id' => $this->class->id,
            'title' => 'Audit Assignment Grade',
            'max_points' => 100,
            'item_type' => \App\Models\GradeItem::TYPE_ASSIGNMENT,
            'related_type' => \App\Models\Assignment::class,
            'related_id' => $this->assignment->id,
        ]);
        $feedback = $this->actingAs($this->instructor)->post('/instructor/submissions/'.$submission->id.'/suggest-feedback');
        $this->assertTrue(in_array($feedback->getStatusCode(), [200, 302]), 'instructor suggest feedback got '.$feedback->getStatusCode());

        // ---- student assistant (JSON) ----
        $assistant = $this->actingAs($this->student)->post('/student/assistant', ['message' => 'Ano uunahin ko?']);
        $this->assertTrue(in_array($assistant->getStatusCode(), [200, 302]), 'assistant reply got '.$assistant->getStatusCode());
        if ($assistant->getStatusCode() === 200) {
            $this->assertArrayHasKey('reply', $assistant->json());
            $this->assertNotEmpty($assistant->json('reply'));
        }
    }
}

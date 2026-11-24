<?php

namespace Tests\Feature;

use App\Models\AaSyncLog;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\GradeSubmission;
use App\Models\Quiz;
use App\Models\User;
use App\Models\VirtualClass;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Canvas-style dashboard zones:
 *  - Faculty dashboard: to-check queue, completion graph, AA report export.
 *  - Student dashboard: My Courses cards, progress graph, To-Do list.
 */
class CanvasDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    public function test_faculty_dashboard_shows_the_to_check_queue_and_aa_export(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('To check');
        $response->assertSee('Export Report for AA');
        $response->assertSee('Students completed vs. active per class');
    }

    public function test_faculty_can_download_the_aa_report_as_csv(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'MATH101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'MATH101-A',
            'status' => 'active',
        ]);

        $student = User::factory()->student()->create(['status' => 'active']);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($instructor)
            ->get(route('instructor.dashboard.export', ['type' => 'aa', 'format' => 'csv']));

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Student ID', $content);
        $this->assertStringContainsString('LMS Score %', $content);
        $this->assertStringContainsString('MATH101-A', $content);
        // LMS scores only — the final grade column must never appear in the AA export.
        $this->assertStringNotContainsString('Final Grade', $content);
    }

    public function test_faculty_real_time_to_do_total_matches_the_dashboard_total(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'TODO101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        Assignment::create([
            'class_id' => $class->id,
            'title' => 'Upcoming assignment',
            'slug' => 'upcoming-assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDays(2),
            'created_by' => $instructor->id,
        ]);
        Quiz::create([
            'class_id' => $class->id,
            'title' => 'Upcoming quiz',
            'slug' => 'upcoming-quiz-'.uniqid(),
            'status' => 'published',
            'availability_from' => now()->addDays(3),
            'created_by' => $instructor->id,
        ]);
        Exam::create([
            'class_id' => $class->id,
            'course_id' => $course->id,
            'title' => 'Upcoming exam',
            'slug' => 'upcoming-exam-'.uniqid(),
            'exam_type' => 'midterm',
            'status' => 'published',
            'starts_at' => now()->addDays(4),
            'created_by' => $instructor->id,
        ]);
        VirtualClass::create([
            'class_id' => $class->id,
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'Upcoming virtual class',
            'meeting_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
            'livekit_room_name' => 'lms-class-'.$class->id.'-'.uniqid(),
            'status' => VirtualClass::STATUS_SCHEDULED,
            'created_by' => $instructor->id,
        ]);

        $dashboard = $this->actingAs($instructor)->get('/instructor/dashboard');
        $dashboard->assertOk();

        $stats = $dashboard->viewData('stats');
        $this->assertSame(4, $stats['upcoming_activities_total']);

        $response = $this->actingAs($instructor)
            ->getJson(route('instructor.dashboard.real-time-stats'));

        $response->assertOk();
        $this->assertSame(
            $stats['upcoming_activities_total'],
            $response->json('data.upcoming_activities_total'),
            'The auto-refresh must report the same To-do total as the panel badge and hint.'
        );
    }

    public function test_faculty_recent_enrollments_hint_uses_the_uncapped_total(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->enrollStudents($class, 5);

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');
        $response->assertOk();
        $response->assertDontSee('Showing 5 of 5');
        $this->assertSame(5, $response->viewData('stats')['recent_enrollments_total']);

        $this->enrollStudents($class, 1);

        $this->actingAs($instructor)
            ->post(route('instructor.dashboard.clear-cache'))
            ->assertRedirect();

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');
        $response->assertOk();
        $response->assertSee('Showing 5 of 6');

        $stats = $response->viewData('stats');
        $this->assertSame(6, $stats['recent_enrollments_total'], 'The hint must count all enrollments, not only the five listed.');
        $this->assertCount(5, $stats['recent_enrollments']);
    }

    public function test_faculty_upcoming_activities_hint_uses_the_uncapped_total(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->addUpcomingAssignments($class, $instructor, 5);

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');
        $response->assertOk();
        $response->assertDontSee('Showing 5 of 5');
        $this->assertSame(5, $response->viewData('stats')['upcoming_activities_total']);

        $this->addUpcomingAssignments($class, $instructor, 1);

        $this->actingAs($instructor)
            ->post(route('instructor.dashboard.clear-cache'))
            ->assertRedirect();

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');
        $response->assertOk();
        $response->assertSee('Showing 5 of 6');

        $stats = $response->viewData('stats');
        $this->assertSame(6, $stats['upcoming_activities_total'], 'The hint must count all upcoming activities, not only the five listed.');
        $this->assertCount(5, $stats['upcoming_activities']);
    }

    public function test_faculty_dashboard_hint_links_point_to_their_list_pages(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'CAP101', 'status' => 'published']);

        // Seven classes exceed both the six-row class-performance cap and the
        // three-card My courses cap.
        $classes = collect(range(1, 7))->map(fn ($index) => ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'CAP101-'.$index,
            'status' => 'active',
        ]));
        $class = $classes->first();

        $students = collect(range(1, 6))->map(function ($index) use ($class) {
            $student = User::factory()->student()->create(['status' => 'active']);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'enrolled_at' => now()->subMinutes($index),
            ]);

            return $student;
        });

        // Five graded students below passing make the Needs attention hint render.
        $gradeItem = GradeItem::create([
            'class_id' => $class->id,
            'title' => 'Cap test grade',
            'max_points' => 100,
            'factor' => 1,
            'item_type' => GradeItem::TYPE_ASSIGNMENT,
            'is_released' => true,
            'released_at' => now(),
        ]);

        foreach ($students->take(5) as $student) {
            Grade::create([
                'grade_item_id' => $gradeItem->id,
                'student_id' => $student->id,
                'points' => 30,
                'score_percent' => 30,
                'letter_grade' => 'F',
                'graded_by' => $instructor->id,
                'graded_at' => now(),
            ]);
        }

        $this->addUpcomingAssignments($class, $instructor, 6);

        $assignment = Assignment::where('class_id', $class->id)->orderBy('id')->firstOrFail();

        foreach ($students as $index => $student) {
            AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'attempt_number' => 1,
                'submission_text' => 'Cap test submission '.$index,
                'submitted_at' => now()->subHours($index + 1),
                'status' => AssignmentSubmission::STATUS_SUBMITTED,
            ]);
        }

        foreach (range(1, 6) as $index) {
            GradeSubmission::create([
                'class_id' => $class->id,
                'instructor_id' => $instructor->id,
                'submitted_by' => $instructor->id,
                'status' => GradeSubmission::STATUS_SUBMITTED,
                'student_count' => 6,
                'graded_count' => 6,
                'missing_count' => 0,
                'submitted_at' => now()->subMinutes($index),
            ]);
        }

        foreach (range(1, 5) as $index) {
            VirtualClass::create([
                'class_id' => $class->id,
                'course_id' => $course->id,
                'instructor_id' => $instructor->id,
                'title' => 'Cap test virtual class '.$index,
                'meeting_date' => now()->addDays($index)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
                'livekit_room_name' => 'cap-test-'.$class->id.'-'.$index,
                'status' => VirtualClass::STATUS_SCHEDULED,
                'created_by' => $instructor->id,
            ]);
        }

        foreach (range(1, 6) as $index) {
            Announcement::create([
                'title' => 'Cap test announcement '.$index,
                'body' => 'Cap test announcement body',
                'audience_type' => Announcement::AUDIENCE_CLASS,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'publish_at' => now()->subMinutes($index),
                'status' => Announcement::STATUS_PUBLISHED,
                'created_by' => $instructor->id,
            ]);
        }

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');
        $response->assertOk();

        $previousErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new \DOMXPath($dom);

        $hints = [];
        foreach ($xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " panel-cap-note ")]') as $anchor) {
            $panel = $anchor->parentNode;

            while ($panel && ! ($panel instanceof \DOMElement && str_contains($panel->getAttribute('class'), 'dash-panel'))) {
                $panel = $panel->parentNode;
            }

            $heading = $panel ? $xpath->query('.//h4', $panel)->item(0) : null;

            if ($heading) {
                $hints[preg_replace('/\s+/', ' ', trim($heading->textContent))] = $anchor->getAttribute('href');
            }
        }

        $expected = [
            'Class performance' => route('instructor.dashboard.performance'),
            'Needs attention' => route('instructor.dashboard.at-risk'),
            'My courses' => route('instructor.dashboard.performance'),
            'Recent course activity' => route('instructor.enrollments.index'),
            'Extract & submit grades' => route('instructor.grade-submissions.index'),
            'To-do list' => route('instructor.dashboard.activities'),
            'To check' => route('instructor.dashboard.to-check'),
            'Virtual classes' => route('instructor.virtual-classes.index'),
            'Recent notifications' => route('instructor.announcements.index'),
        ];

        ksort($expected);
        ksort($hints);

        $this->assertSame($expected, $hints, 'Every rendered cap hint must link to its expected list, report, or calendar page.');

        $atRiskPage = $this->actingAs($instructor)->get(route('instructor.dashboard.at-risk'));
        $atRiskPage->assertOk();
        $atRiskPage->assertSee('At-risk Students');
        $atRiskPage->assertViewHas('enrollments', fn ($enrollments) => $enrollments->count() === 5);
        $atRiskPage->assertSee($students->first()->name);

        $toCheckPage = $this->actingAs($instructor)->get(route('instructor.dashboard.to-check'));
        $toCheckPage->assertOk();
        $toCheckPage->assertSee('To-check Queue');
        $toCheckPage->assertViewHas('items', fn ($items) => $items->count() === 6);
        $toCheckPage->assertSee($assignment->title);

        $this->actingAs($instructor)->get(route('instructor.dashboard.activities'))
            ->assertOk()
            ->assertSee('All Upcoming Activities');
        $this->actingAs($instructor)->get(route('instructor.dashboard.performance'))
            ->assertOk()
            ->assertSee('Class Performance')
            ->assertSee('CAP101-1');
        $this->actingAs($instructor)->get(route('instructor.virtual-classes.index'))
            ->assertOk()
            ->assertSee('All Virtual Classes')
            ->assertSee('Cap test virtual class 1');
        $this->actingAs($instructor)->get(route('instructor.announcements.index'))
            ->assertOk()
            ->assertSee('All Announcements')
            ->assertSee('Cap test announcement 1');
    }

    public function test_student_dashboard_hint_links_point_to_full_lists(): void
    {
        $student = User::factory()->student()->create(['status' => 'active']);
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'CAP201', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'CAP201-1',
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        foreach (range(1, 4) as $index) {
            VirtualClass::create([
                'class_id' => $class->id,
                'course_id' => $course->id,
                'instructor_id' => $instructor->id,
                'title' => 'Cap list virtual class '.$index,
                'meeting_date' => now()->addDays($index)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '10:00:00',
                'meeting_provider' => VirtualClass::PROVIDER_LIVEKIT,
                'livekit_room_name' => 'cap-list-'.$class->id.'-'.$index,
                'status' => VirtualClass::STATUS_SCHEDULED,
                'created_by' => $instructor->id,
            ]);

            Announcement::create([
                'title' => 'Cap list announcement '.$index,
                'body' => 'Cap list announcement body',
                'audience_type' => Announcement::AUDIENCE_CLASS,
                'class_id' => $class->id,
                'course_id' => $course->id,
                'publish_at' => now()->subMinutes($index),
                'status' => Announcement::STATUS_PUBLISHED,
                'created_by' => $instructor->id,
            ]);
        }

        $response = $this->actingAs($student)->get('/student/dashboard');
        $response->assertOk();
        $response->assertSee('Showing 3 of 4');
        $response->assertSee('href="'.route('student.virtual-classes.index').'"', false);
        $response->assertSee('href="'.route('student.announcements.index').'"', false);

        $this->actingAs($student)->get(route('student.virtual-classes.index'))
            ->assertOk()
            ->assertSee('All Virtual Classes')
            ->assertSee('Cap list virtual class 1');
        $this->actingAs($student)->get(route('student.announcements.index'))
            ->assertOk()
            ->assertSee('All Announcements')
            ->assertSee('Cap list announcement 1');
    }

    public function test_student_dashboard_shows_canvas_three_zones(): void
    {
        $student = User::factory()->student()->create(['status' => 'active']);
        $course = Course::factory()->create([
            'title' => 'Mathematics in the Modern World',
            'code' => 'MATH101',
            'status' => 'published',
        ]);
        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertSee('My courses');
        $response->assertSee('Mathematics in the Modern World');
        $response->assertSee('My progress');
        $response->assertSee('To-do list');
    }

    public function test_student_dashboard_empty_state_points_to_aa_sync(): void
    {
        $student = User::factory()->student()->create(['status' => 'active']);

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertSee('subjects synced from AA');
    }

    public function test_faculty_reports_page_lists_exports_with_aa_labelling(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['status' => 'published']);
        ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor)->get('/instructor/reports');

        $response->assertOk();
        $response->assertSee('LMS assessment reports');
        $response->assertSee('For AA');
        $response->assertSee('hindi official final grade');
    }

    public function test_admin_dashboard_shows_sync_status_and_primary_action(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('AA Synchronization');
        $response->assertSee('Sync from AA');
        // Redesigned sync status band + outcome chips
        $response->assertSee('Last sync');
        $response->assertSee('failed runs');
        $response->assertSee('records applied');
        // Quick access + system status sections
        $response->assertSee('Course management');
        $response->assertSee('Sync logs & errors');
        $response->assertSee('System settings');
        $response->assertSee('System status');
    }

    public function test_admin_sync_cap_hint_uses_aa_sync_total(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        foreach (range(1, 6) as $i) {
            AaSyncLog::create([
                'performed_by' => $admin->id,
                'entities' => ['departments'],
                'status' => AaSyncLog::STATUS_SUCCESS,
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 0,
                'started_at' => now()->subMinutes($i),
                'finished_at' => now()->subMinutes($i)->addSeconds(5),
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Recent sync activity');
        // The table is capped at five rows, but the hint must use the full
        // aa_sync_total count rather than the capped collection.
        $response->assertSee('Showing 5 of 6');
        $response->assertSee('All sync logs');
    }

    public function test_admin_sync_cap_hint_only_appears_after_the_cap_is_exceeded(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        foreach (range(1, 5) as $i) {
            AaSyncLog::create([
                'performed_by' => $admin->id,
                'entities' => ['departments'],
                'status' => AaSyncLog::STATUS_SUCCESS,
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 0,
                'started_at' => now()->subMinutes($i),
                'finished_at' => now()->subMinutes($i)->addSeconds(5),
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertDontSee('Showing 5 of 5');
        $response->assertDontSee('All sync logs');

        AaSyncLog::create([
            'performed_by' => $admin->id,
            'entities' => ['departments'],
            'status' => AaSyncLog::STATUS_SUCCESS,
            'records_processed' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_skipped' => 0,
            'records_failed' => 0,
            'started_at' => now(),
            'finished_at' => now()->addSeconds(5),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Showing 5 of 6');
        $response->assertSee('All sync logs');
    }

    public function test_admin_dashboard_hides_sync_cap_hint_at_five_logs(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        foreach (range(1, 5) as $i) {
            AaSyncLog::create([
                'performed_by' => $admin->id,
                'entities' => ['departments'],
                'status' => AaSyncLog::STATUS_SUCCESS,
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 0,
                'started_at' => now()->subMinutes($i),
                'finished_at' => now()->subMinutes($i)->addSeconds(5),
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Recent sync activity');
        $response->assertDontSee('Showing 5 of 5');
        $response->assertDontSee('All sync logs');
    }

    public function test_dashboard_showing_hints_are_links_to_their_full_list_pages(): void
    {
        // Admin sync-history hint.
        $admin = User::factory()->admin()->create(['status' => 'active']);

        foreach (range(1, 6) as $i) {
            AaSyncLog::create([
                'performed_by' => $admin->id,
                'entities' => ['departments'],
                'status' => AaSyncLog::STATUS_SUCCESS,
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 0,
                'started_at' => now()->subMinutes($i),
                'finished_at' => now()->subMinutes($i)->addSeconds(5),
            ]);
        }

        $adminResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $adminResponse->assertOk();
        $this->assertSame(
            ['Showing 5 of 6' => route('admin.aa-sync.index')],
            $this->renderedShowingHints($adminResponse),
            'The admin sync-history hint must be a link to the sync log history.'
        );

        // Faculty at-risk and to-check hints need five and six rows respectively
        // to exceed their four- and five-row display caps.
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'HINT101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'HINT101-A',
            'status' => 'active',
        ]);

        $students = collect(range(1, 6))->map(function ($index) use ($class) {
            $student = User::factory()->student()->create(['status' => 'active']);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'enrolled_at' => now()->subMinutes($index),
            ]);

            return $student;
        });

        $gradeItem = GradeItem::create([
            'class_id' => $class->id,
            'title' => 'Hint test grade',
            'max_points' => 100,
            'factor' => 1,
            'item_type' => GradeItem::TYPE_ASSIGNMENT,
            'is_released' => true,
            'released_at' => now(),
        ]);

        foreach ($students->take(5) as $student) {
            Grade::create([
                'grade_item_id' => $gradeItem->id,
                'student_id' => $student->id,
                'points' => 30,
                'score_percent' => 30,
                'letter_grade' => 'F',
                'graded_by' => $instructor->id,
                'graded_at' => now(),
            ]);
        }

        $assignment = Assignment::create([
            'class_id' => $class->id,
            'title' => 'Hint test assignment',
            'slug' => 'hint-test-assignment-'.uniqid(),
            'status' => Assignment::STATUS_PUBLISHED,
            'due_date' => now()->addDay(),
            'created_by' => $instructor->id,
        ]);

        foreach ($students as $index => $student) {
            AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
                'attempt_number' => 1,
                'submission_text' => 'Hint test submission '.$index,
                'submitted_at' => now()->subHours($index + 1),
                'status' => AssignmentSubmission::STATUS_SUBMITTED,
            ]);
        }

        $facultyResponse = $this->actingAs($instructor)->get('/instructor/dashboard');
        $facultyResponse->assertOk();

        $expectedFacultyHints = [
            'Showing 4 of 5' => route('instructor.dashboard.at-risk'),
            'Showing 5 of 6' => route('instructor.dashboard.to-check'),
        ];
        $facultyHints = $this->renderedShowingHints($facultyResponse);
        ksort($expectedFacultyHints);
        ksort($facultyHints);

        $this->assertSame(
            $expectedFacultyHints,
            $facultyHints,
            'Faculty showing hints must link to the full at-risk and to-check pages.'
        );
    }

    public function test_admin_dashboard_surfaces_grade_submission_monitoring_and_livekit_status(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Grade submission monitoring');
        $response->assertSee('Virtual classes & LiveKit');
        $response->assertSee('LiveKit integration');
        $response->assertSee(route('admin.grade-submissions.index'), false);
        $response->assertSee(route('admin.virtual_classes.index'), false);
        // The credential itself is never rendered, only its presence.
        $response->assertSee('credential values never reach the page');
    }

    public function test_admin_dashboard_monitors_recent_grade_submissions(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        $course = Course::factory()->create(['code' => 'MON101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'code' => 'MON101-A',
            'status' => 'active',
        ]);

        GradeSubmission::create([
            'class_id' => $class->id,
            'instructor_id' => $instructor->id,
            'submitted_by' => $instructor->id,
            'status' => GradeSubmission::STATUS_SUBMITTED,
            'student_count' => 3,
            'graded_count' => 2,
            'missing_count' => 1,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('MON101-A');
        $response->assertSee('missing grades');
    }

    public function test_faculty_dashboard_surfaces_extract_and_submit_grades(): void
    {
        $instructor = User::factory()->instructor()->create(['status' => 'active']);
        ClassModel::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($instructor)->get('/instructor/dashboard');

        $response->assertOk();
        $response->assertSee('Extract & submit grades');
        $response->assertSee('Extract grades');
        $response->assertSee('Submission history');
        $response->assertSee(route('instructor.grade-submissions.index'), false);
    }

    public function test_faculty_dashboard_only_lists_its_own_grade_submissions(): void
    {
        $owner = User::factory()->instructor()->create(['status' => 'active']);
        $other = User::factory()->instructor()->create(['status' => 'active']);

        $course = Course::factory()->create(['code' => 'OWN101', 'status' => 'published']);
        $class = ClassModel::factory()->create([
            'instructor_id' => $owner->id,
            'course_id' => $course->id,
            'code' => 'OWN101-A',
            'status' => 'active',
        ]);

        GradeSubmission::create([
            'class_id' => $class->id,
            'instructor_id' => $owner->id,
            'submitted_by' => $owner->id,
            'status' => GradeSubmission::STATUS_SUBMITTED,
            'student_count' => 2,
            'graded_count' => 2,
            'missing_count' => 0,
            'submitted_at' => now(),
        ]);

        // The class owner sees the batch…
        $this->actingAs($owner)->get('/instructor/dashboard')
            ->assertOk()
            ->assertSee('OWN101-A');

        // …and another instructor's dashboard never reveals it.
        $this->actingAs($other)->get('/instructor/dashboard')
            ->assertOk()
            ->assertDontSee('OWN101-A');
    }

    private function enrollStudents(ClassModel $class, int $count): void
    {
        foreach (range(1, $count) as $index) {
            $student = User::factory()->student()->create(['status' => 'active']);

            Enrollment::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'status' => 'active',
                'enrolled_at' => now()->subMinutes($index),
            ]);
        }
    }

    private function addUpcomingAssignments(ClassModel $class, User $instructor, int $count): void
    {
        foreach (range(1, $count) as $index) {
            Assignment::create([
                'class_id' => $class->id,
                'title' => 'Upcoming assignment '.$index,
                'slug' => 'upcoming-assignment-'.uniqid(),
                'status' => Assignment::STATUS_PUBLISHED,
                'due_date' => now()->addDays($index),
                'created_by' => $instructor->id,
            ]);
        }
    }

    /**
     * Map every rendered "Showing X of Y" anchor to its href.
     */
    private function renderedShowingHints($response): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new \DOMXPath($dom);

        $hints = [];
        foreach ($xpath->query('//a') as $anchor) {
            $text = preg_replace('/\s+/', ' ', trim($anchor->textContent));

            if (preg_match('/^Showing \d+ of \d+$/', $text)) {
                $hints[$text] = $anchor->getAttribute('href');
            }
        }

        return $hints;
    }
}

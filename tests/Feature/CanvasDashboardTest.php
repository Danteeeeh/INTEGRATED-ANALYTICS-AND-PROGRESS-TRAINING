<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
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
}

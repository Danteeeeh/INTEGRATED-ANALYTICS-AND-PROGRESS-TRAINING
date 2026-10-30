<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin attendance Export button used to flash a "queued" message and
 * redirect back to the list, so no file was ever produced. These tests pin the
 * replacement: a streamed CSV whose rows match the filters on the export link.
 */
class AttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    private ClassModel $class;

    private ClassModel $otherClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->admin()->create(['status' => 'active']);
        $this->student = User::factory()->student()->create([
            'status' => 'active',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'identifier' => 'STU-2026-001',
        ]);

        $course = Course::factory()->create([
            'code' => 'IT301',
            'title' => 'Systems Integration',
            'status' => 'published',
        ]);

        $otherCourse = Course::factory()->create([
            'code' => 'IT302',
            'title' => 'Capstone Project',
            'status' => 'published',
        ]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'code' => 'IT301-A',
            'status' => 'active',
        ]);

        $this->otherClass = ClassModel::factory()->create([
            'course_id' => $otherCourse->id,
            'code' => 'IT302-B',
            'status' => 'active',
        ]);

        AttendanceRecord::create([
            'class_id' => $this->class->id,
            'attendance_date' => '2026-10-05',
            'week_number' => 3,
            'session_title' => 'Integration Lab',
            'student_id' => $this->student->id,
            'status' => AttendanceRecord::STATUS_PRESENT,
            'duration_minutes' => 90,
            'notes' => 'On time',
            'recorded_by' => $this->admin->id,
        ]);

        AttendanceRecord::create([
            'class_id' => $this->otherClass->id,
            'attendance_date' => '2026-10-06',
            'week_number' => 4,
            'session_title' => 'Capstone Standup',
            'student_id' => $this->student->id,
            'status' => AttendanceRecord::STATUS_LATE,
            'duration_minutes' => 45,
            'recorded_by' => $this->admin->id,
        ]);
    }

    public function test_export_downloads_a_csv_instead_of_redirecting_back(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.export'));

        // A redirect here is the old bug: no file, just a flash message.
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString(
            '.csv',
            (string) $response->headers->get('content-disposition'),
            'The export must be offered as a file download.'
        );

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($csv))));

        $this->assertStringContainsString('Class Code', $lines[0]);
        $this->assertStringContainsString('Week', $lines[0]);
        $this->assertCount(3, $lines, 'Header plus one row per attendance record.');

        $this->assertStringContainsString('IT301-A', $csv);
        $this->assertStringContainsString('IT302-B', $csv);
        $this->assertStringContainsString('Maria Santos', $csv);
        $this->assertStringContainsString('Integration Lab', $csv);
        $this->assertStringContainsString('STU-2026-001', $csv);
    }

    public function test_export_honours_the_filters_handed_over_by_the_export_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.export', [
            'class_id' => $this->class->id,
        ]));

        $response->assertOk();

        $lines = array_values(array_filter(explode("\n", trim($response->streamedContent()))));

        $this->assertCount(2, $lines, 'Only the filtered class should be exported.');
        $this->assertStringContainsString('IT301-A', $lines[1]);
        $this->assertStringNotContainsString('IT302-B', $lines[1]);
    }

    public function test_students_cannot_export_attendance(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.attendance.export'))
            ->assertForbidden();
    }
}

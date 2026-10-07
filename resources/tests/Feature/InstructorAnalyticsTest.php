<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeItem;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->instructor = User::factory()->create([
            'role_id' => Role::where('slug', Role::INSTRUCTOR)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    protected function seedActivity(): ClassModel
    {
        $course = Course::factory()->create(['status' => 'published']);

        $class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $this->instructor->id,
            'status' => 'active',
        ]);

        foreach ([95.0, 82.0, 71.0] as $score) {
            $student = User::factory()->create([
                'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
                'status' => 'active',
            ]);

            Enrollment::create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
                'enrolled_at' => now(),
            ]);

            $item = GradeItem::create([
                'class_id' => $class->id,
                'title' => 'Week 1',
                'item_type' => 'assignment',
                'max_points' => 100,
                'is_released' => true,
                'released_at' => now(),
                'position' => 0,
            ]);

            Grade::create([
                'grade_item_id' => $item->id,
                'student_id' => $student->id,
                'points' => $score,
                'score_percent' => $score,
            ]);
        }

        return $class;
    }

    public function test_performance_analytics_endpoint_returns_data(): void
    {
        $this->seedActivity();

        $response = $this->actingAs($this->instructor)
            ->getJson('/instructor/dashboard/analytics?period=month&type=performance');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');

        $this->assertIsArray($data['grade_distribution']);
        $this->assertSame(1, $data['grade_distribution']['A'], '95% should land in the A band.');
        $this->assertSame(1, $data['grade_distribution']['B'], '82% should land in the B band.');
        $this->assertSame(1, $data['grade_distribution']['C'], '71% should land in the C band.');
        $this->assertSame(0, $data['grade_distribution']['D']);
        $this->assertSame(0, $data['grade_distribution']['F']);

        $this->assertSame(3, $data['graded_enrollments']);
        $this->assertGreaterThan(0, $data['average_grade']);

        // Each row now carries the student count so the view can stay in sync.
        $this->assertArrayHasKey('active_students', $data['class_performance'][0]);
    }

    public function test_all_analytics_types_return_success_payload(): void
    {
        $this->seedActivity();

        foreach (['enrollment', 'performance', 'attendance', 'engagement'] as $type) {
            $response = $this->actingAs($this->instructor)
                ->getJson("/instructor/dashboard/analytics?period=month&type={$type}");

            $response->assertOk();
            $this->assertTrue(
                $response->json('success'),
                "Analytics type '{$type}' did not return success."
            );
            $this->assertIsArray($response->json('data'), "Analytics type '{$type}' returned no data array.");
        }
    }

    public function test_dashboard_view_renders_analytics_cards(): void
    {
        $this->seedActivity();

        $response = $this->actingAs($this->instructor)->get('/instructor/dashboard');

        $response->assertOk()
            ->assertSee('Performance Analytics', false)
            ->assertSee('gradeDistributionChart', false)
            ->assertSee('enrollmentTrendsChart', false)
            ->assertSee('attendanceOverviewChart', false)
            ->assertSee('engagementMetricsChart', false);

        // The auto-load path must reuse loadAnalytics() so unselected cards get
        // reset to an idle state instead of staying on "Loading analytics…".
        $html = $response->getContent();

        $this->assertStringContainsString('resetAnalyticsCards', $html);
        $this->assertStringContainsString('loadAnalytics(true)', $html, 'Auto-load must call the shared loader silently.');

        // No chart ships a hard-coded loading placeholder in the static markup.
        $this->assertStringNotContainsString(
            '<p>Loading analytics</p>',
            preg_replace('/<script.*?<\/script>/s', '', $html) ?? '',
            'Static markup should not hard-code a permanent loading placeholder.'
        );
    }
}
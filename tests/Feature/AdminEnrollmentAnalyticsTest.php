<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin dashboard "Enrollment Status" donut rendered as one solid green
 * ring with a four-item legend and no numbers, so nothing on the card could be
 * checked against reality.
 *
 * The status breakdown must be counted from the data, must say how many
 * enrollments it covers, must name the statuses it found, and must say so
 * plainly when there is nothing to show.
 */
class AdminEnrollmentAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', 'admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $course = Course::factory()->create();

        $this->class = ClassModel::factory()->create(['course_id' => $course->id]);

        $this->actingAs($this->admin);
    }

    private function enrol(string $status, int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            Enrollment::create([
                'class_id' => $this->class->id,
                'student_id' => User::factory()->create([
                    'role_id' => Role::where('slug', 'student')->firstOrFail()->id,
                    'status' => 'active',
                ])->id,
                'status' => $status,
                'enrolled_at' => now()->subDays(3),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function analytics(): array
    {
        $response = $this->get(route('admin.dashboard.analytics-json'));

        $response->assertOk();

        return $response->json('data');
    }

    public function test_each_status_is_counted_separately(): void
    {
        $this->enrol('active', 3);
        $this->enrol('pending', 2);
        $this->enrol('completed', 1);
        $this->enrol('dropped', 4);

        $enrollment = $this->analytics()['enrollment'];

        $this->assertSame(3, $enrollment['active']);
        $this->assertSame(2, $enrollment['pending']);
        $this->assertSame(1, $enrollment['completed']);
        $this->assertSame(4, $enrollment['dropped']);

        $this->assertSame(
            10,
            $enrollment['total'],
            'The card must state how many enrollments the breakdown covers.'
        );
    }

    public function test_the_total_is_the_sum_of_the_statuses(): void
    {
        $this->enrol('active', 5);
        $this->enrol('dropped', 2);

        $enrollment = $this->analytics()['enrollment'];

        $this->assertSame(
            $enrollment['active'] + $enrollment['pending']
                + $enrollment['completed'] + $enrollment['dropped'],
            $enrollment['total']
        );
    }

    public function test_an_all_active_institution_still_reports_its_real_total(): void
    {
        // This is what the seeded data looks like: every enrollment active, so
        // the donut is one solid ring. The total makes that legible instead of
        // leaving the card looking broken.
        $this->enrol('active', 12);

        $enrollment = $this->analytics()['enrollment'];

        $this->assertSame(12, $enrollment['active']);
        $this->assertSame(0, $enrollment['pending']);
        $this->assertSame(12, $enrollment['total']);
    }

    public function test_no_enrollments_reports_zero_rather_than_failing(): void
    {
        $enrollment = $this->analytics()['enrollment'];

        $this->assertSame(0, $enrollment['total']);
        $this->assertSame(0, $enrollment['active']);
    }

    public function test_the_dashboard_explains_the_breakdown_in_words(): void
    {
        $this->enrol('active', 2);
        $this->enrol('dropped', 1);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();

        // Figures on the card, so the chart can be checked rather than trusted.
        $response->assertSee('data-enrollment-total="3"', false);
        $response->assertSee('data-enrollment-active="2"', false);
        $response->assertSee('data-enrollment-dropped="1"', false);
    }

    public function test_the_dashboard_says_so_when_there_is_nothing_to_chart(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No enrollments yet', false);
    }

    public function test_the_dashboard_does_not_claim_an_empty_chart_has_data(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();

        // It must admit there is nothing, rather than leaving an empty ring and
        // a four-item legend to be interpreted.
        $response->assertSee('Nothing to chart until students are enrolled.', false);
        $response->assertDontSee('0 enrollments', false);
    }
}
<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dropped enrollments must not drag any rate down. A student who left the
 * class cannot submit work, so counting them in a denominator is a permanent,
 * invisible tax on every metric.
 */
class DroppedEnrollmentMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected ClassModel $class;

    protected Program $program;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->program = Program::create([
            'name' => 'Bachelor of Science',
            'code' => 'BSCS',
        ]);
        $course = Course::factory()->create(['status' => 'published', 'program_id' => $this->program->id]);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->course = $course;
    }

    protected function enroll(string $status): ?User
    {
        // enrollments has a UNIQUE(student_id, class_id) constraint, so every
        // row in these tests needs its own student.
        $student = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'status' => $status,
            'enrolled_at' => now(),
        ]);

        return $student;
    }

    protected Course $course;

    public function test_countable_scope_excludes_dropped(): void
    {
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_COMPLETED);
        $this->enroll(Enrollment::STATUS_PENDING);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $this->assertSame(3, Enrollment::countable()->count());
        $this->assertSame(1, Enrollment::dropped()->count());
    }

    public function test_retention_rate_ignores_dropped_enrollments(): void
    {
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_COMPLETED);
        $this->enroll(Enrollment::STATUS_DROPPED);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $response = $this->actingAs($this->admin)->get('/admin/analytics')->assertOk();

        $rate = $response->viewData('stats')['learning_analytics']['student_retention_rate'];

        // Both countable enrollments (1 active + 1 completed) are retained, so
        // the rate is 100%. Leaving the two drops in the denominator would have
        // permanently reported 50% instead.
        $this->assertEqualsWithDelta(100.0, $rate, 0.01);
    }

    public function test_retention_rate_is_untouched_by_drops_in_both_ways(): void
    {
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_COMPLETED);

        $before = $this->actingAs($this->admin)
            ->get('/admin/analytics')->viewData('stats')['learning_analytics']['student_retention_rate'];

        $this->enroll(Enrollment::STATUS_DROPPED);
        $this->enroll(Enrollment::STATUS_DROPPED);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $after = $this->actingAs($this->admin)
            ->get('/admin/analytics')->viewData('stats')['learning_analytics']['student_retention_rate'];

        $this->assertEqualsWithDelta($before, $after, 0.001, 'Drops must not move the retention rate.');
    }

    public function test_completion_by_program_ignores_dropped_enrollments(): void
    {
        $this->enroll(Enrollment::STATUS_COMPLETED);
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $stats = $this->actingAs($this->admin)->get('/admin/analytics')->viewData('stats');

        $row = collect($stats['learning_analytics']['completion_by_program'])
            ->first(fn ($r) => $r['name'] === $this->program->name);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['total'], 'Dropped rows must stay out of the program total.');
        $this->assertSame(1, $row['completed']);
        $this->assertEqualsWithDelta(50.0, $row['rate'], 0.01);
    }

    public function test_most_popular_courses_ignores_dropped_enrollments(): void
    {
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_DROPPED);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $stats = $this->actingAs($this->admin)->get('/admin/analytics')->viewData('stats');

        $row = collect($stats['learning_analytics']['most_popular_courses'])
            ->first(fn ($r) => $r['title'] === $this->course->title);

        $this->assertNotNull($row);
        $this->assertSame(1, $row['enrollments'], 'Enrollment headline count must exclude drops.');
        $this->assertEqualsWithDelta(0.0, $row['completion_rate'], 0.01);
    }

    public function test_popular_courses_rate_agrees_with_program_rate(): void
    {
        // Both numbers describe the same course, so they must use the same
        // denominator.
        $this->enroll(Enrollment::STATUS_COMPLETED);
        $this->enroll(Enrollment::STATUS_ACTIVE);
        $this->enroll(Enrollment::STATUS_DROPPED);

        $stats = $this->actingAs($this->admin)->get('/admin/analytics')->viewData('stats');

        $popular = collect($stats['learning_analytics']['most_popular_courses'])
            ->first(fn ($r) => $r['title'] === $this->course->title);

        $byProgram = collect($stats['learning_analytics']['completion_by_program'])
            ->first(fn ($r) => $r['name'] === $this->program->name);

        $this->assertSame($byProgram['total'], $popular['enrollments']);
        $this->assertEqualsWithDelta($byProgram['rate'], $popular['completion_rate'], 0.01);
    }

    public function test_analytics_page_still_renders_with_only_dropped_rows(): void
    {
        $this->enroll(Enrollment::STATUS_DROPPED);

        $this->actingAs($this->admin)->get('/admin/analytics')->assertOk();
    }
}
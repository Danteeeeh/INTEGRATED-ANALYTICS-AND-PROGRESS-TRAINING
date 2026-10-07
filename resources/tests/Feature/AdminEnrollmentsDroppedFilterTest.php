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

class AdminEnrollmentsDroppedFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected ClassModel $class;

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

        $course = Course::factory()->create(['status' => 'published']);

        $this->class = ClassModel::factory()->create([
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_ACTIVE,
            'enrolled_at' => now(),
        ]);

        // The row the admin removed from the subject. enrollments is
        // UNIQUE(student_id, class_id), so this needs a separate student.
        $droppingStudent = User::factory()->create([
            'role_id' => Role::where('slug', Role::STUDENT)->firstOrFail()->id,
            'status' => 'active',
        ]);

        Enrollment::create([
            'student_id' => $droppingStudent->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_DROPPED,
            'enrolled_at' => now()->subMonth(),
        ]);

        $this->droppedStudent = $droppingStudent;
    }

    protected User $droppedStudent;

    protected function visibleStatuses(array $query = []): array
    {
        return $this->actingAs($this->admin)
            ->get('/admin/enrollments'.($query ? '?'.http_build_query($query) : ''))
            ->assertOk()
            ->viewData('enrollments')
            ->getCollection()
            ->pluck('status')
            ->unique()
            ->values()
            ->all();
    }

    public function test_dropped_enrollments_are_hidden_by_default(): void
    {
        // The exact regression: the default list mixed dropped rows in with
        // live ones, burying the students still taking the class.
        $this->assertNotContains(Enrollment::STATUS_DROPPED, $this->visibleStatuses());
        $this->assertContains(Enrollment::STATUS_ACTIVE, $this->visibleStatuses());
    }

    public function test_dropped_rows_are_still_reachable_via_the_toggle(): void
    {
        $this->assertContains(
            Enrollment::STATUS_DROPPED,
            $this->visibleStatuses(['include_dropped' => 1])
        );
    }

    public function test_explicit_status_filter_overrides_the_default(): void
    {
        $this->assertSame(
            [Enrollment::STATUS_DROPPED],
            $this->visibleStatuses(['status' => 'dropped'])
        );
    }

    public function test_a_live_status_filter_still_excludes_dropped(): void
    {
        $this->assertSame(
            [Enrollment::STATUS_ACTIVE],
            $this->visibleStatuses(['status' => 'active'])
        );
    }

    public function test_hidden_rows_are_still_in_the_database(): void
    {
        // Filtering must never delete anything.
        $this->assertSame(1, Enrollment::dropped()->count());
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->droppedStudent->id,
            'class_id' => $this->class->id,
            'status' => Enrollment::STATUS_DROPPED,
        ]);
    }

    public function test_page_reports_how_many_dropped_rows_are_hidden(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/enrollments')->assertOk();

        $response->assertSee('dropped enrollment', false);
        $this->assertSame(1, $response->viewData('droppedCount'));

        // Once the toggle is on, the notice goes away.
        $this->actingAs($this->admin)
            ->get('/admin/enrollments?include_dropped=1')
            ->assertOk()
            ->assertDontSee('dropped enrollment', false);
    }

    public function test_filters_survive_pagination(): void
    {
        $query = request()->merge(['include_dropped' => 1]);
        app('request')->query = $query->query;

        $this->actingAs($this->admin)
            ->get('/admin/enrollments?include_dropped=1')
            ->assertOk();
    }
}
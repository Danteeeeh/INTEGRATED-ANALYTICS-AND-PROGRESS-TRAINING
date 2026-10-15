<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AA Staff / Academic Officer is VIEW-ONLY: it may read the LMS panel, and
 * nothing else. Official records and final grades stay in the AA system, so
 * the role deliberately has no write routes and no access to the admin,
 * instructor, or student panels.
 */
class AaStaffAccessTest extends TestCase
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

    protected function aaStaff(): User
    {
        return User::factory()->aaStaff()->create(['status' => 'active']);
    }

    public function test_aa_staff_role_is_seeded(): void
    {
        $this->assertDatabaseHas('roles', ['slug' => Role::AA_STAFF, 'name' => 'AA Staff']);
    }

    public function test_aa_staff_can_view_their_dashboard(): void
    {
        $response = $this->actingAs($this->aaStaff())->get('/aa/dashboard');

        $response->assertOk();
    }

    public function test_aa_staff_can_view_academic_records(): void
    {
        $course = Course::factory()->create();
        $user = $this->aaStaff();

        $this->actingAs($user)->get('/aa/courses')->assertOk();
        $this->actingAs($user)->get('/aa/students')->assertOk();
        $this->actingAs($user)->get('/aa/enrollments')->assertOk();
        $this->actingAs($user)->get('/aa/progress')->assertOk();
        $this->actingAs($user)->get("/aa/courses/{$course->id}")->assertOk();
    }

    public function test_aa_staff_has_no_write_routes(): void
    {
        $user = $this->aaStaff();

        // The panel is registered GET-only, so write attempts hit 405.
        $this->actingAs($user)->post('/aa/courses')->assertStatus(405);
        $this->actingAs($user)->post('/aa/students')->assertStatus(405);
        $this->actingAs($user)->post('/aa/enrollments')->assertStatus(405);
    }

    public function test_aa_staff_cannot_reach_other_panels(): void
    {
        $user = $this->aaStaff();

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/instructor/dashboard')->assertForbidden();
        $this->actingAs($user)->get('/student/dashboard')->assertForbidden();
    }

    public function test_aa_staff_lands_on_aa_dashboard_after_login(): void
    {
        User::factory()->aaStaff()->create([
            'email' => 'aa-staff@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => 'aa-staff@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('aa.dashboard'));
    }

    public function test_only_admin_can_open_the_sync_center(): void
    {
        $admin = User::factory()->admin()->create(['status' => 'active']);
        $this->actingAs($admin)->get('/admin/aa-sync')->assertOk();

        $this->actingAs($this->aaStaff())->get('/admin/aa-sync')->assertForbidden();
    }

    public function test_aa_staff_can_view_and_export_lms_reports(): void
    {
        $course = Course::factory()->create(['status' => 'published']);
        $user = $this->aaStaff();

        $this->actingAs($user)->get('/aa/reports')->assertOk();
        $this->actingAs($user)->get("/aa/reports/{$course->id}")->assertOk();

        $export = $this->actingAs($user)->get('/aa/reports/export');
        $export->assertOk();
        $this->assertStringContainsString('Student ID', $export->streamedContent());
        $this->assertStringContainsString('LMS Score %', $export->streamedContent());
    }

    public function test_aa_staff_reports_are_read_only(): void
    {
        $course = Course::factory()->create();
        $user = $this->aaStaff();

        // Read-only: no POST routes exist on the reports panel.
        $this->actingAs($user)->post('/aa/reports')->assertStatus(405);
        $this->actingAs($user)->post("/aa/reports/{$course->id}")->assertStatus(405);
    }
}

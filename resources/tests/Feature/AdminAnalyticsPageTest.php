<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, PermissionSeeder::class]);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('slug', Role::ADMIN)->firstOrFail()->id,
            'status' => 'active',
        ]);
    }

    public function test_analytics_page_renders_on_an_empty_database(): void
    {
        // No courses, grades, enrollments or lesson progress at all. Every tile
        // must still render — this is where the missing forum_participation key
        // used to throw an ErrorException and 500 the page.
        $response = $this->actingAs($this->admin)->get('/admin/analytics');

        $response->assertOk();
    }

    public function test_analytics_page_renders_with_activity(): void
    {
        \App\Models\Course::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->admin)->get('/admin/analytics');

        $response->assertOk()
            ->assertSee('Engagement Metrics', false)
            ->assertSee('Daily Active Users', false);
    }

    public function test_view_never_references_a_key_the_controller_does_not_return(): void
    {
        $view = file_get_contents(base_path('resources/views/admin/analytics.blade.php'));

        // 'forum_participation' has no backing feature anywhere in the app, so
        // referencing it again would reintroduce the 500.
        $this->assertStringNotContainsString(
            'forum_participation',
            $view,
            'Analytics view still reads forum_participation, which is never returned.'
        );

        // Every other $stats['a']['b'] read must exist in the controller payload.
        preg_match_all("/\\\$stats\['([a-z_]+)'\]\['([a-z_]+)'\]/", $view, $matches, PREG_SET_ORDER);

        $this->assertNotEmpty($matches, 'Expected the view to read nested stats keys.');

        $controller = file_get_contents(base_path('app/Http/Controllers/Admin/DashboardController.php'));

        foreach ($matches as [, $group, $key]) {
            $this->assertMatchesRegularExpression(
                "/'$key'\s*=>|\\\"$key\\\"\\s*=>/",
                $controller,
                "\$stats['{$group}']['{$key}'] is read by the view but never produced by the controller."
            );
        }
    }

    public function test_active_courses_tile_is_not_fed_by_the_classes_count(): void
    {
        $view = file_get_contents(base_path('resources/views/admin/analytics.blade.php'));

        // active_classes counts ClassModel rows, not courses. Labelling it
        // "Courses" showed the wrong number.
        $this->assertStringContainsString("label=\"Published Courses\"", $view);
        $this->assertStringContainsString("\$stats['published_courses']", $view);
    }
}
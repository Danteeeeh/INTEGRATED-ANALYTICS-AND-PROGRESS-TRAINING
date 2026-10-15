<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every parameterless `.index` page as each seeded role with the full
 * demo dataset in place.
 *
 * The SystemAuditTest route sweep runs against empty fixtures, so anything the
 * Blade templates only evaluate inside a populated `@foreach` never runs there.
 * This probe exists to catch those data-bearing 500s.
 */
class SeededPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_index_pages_render_with_real_data(): void
    {
        $actors = [
            'admin' => 'admin@lms.local',
            'instructor' => 'instructor@lms.local',
            'student' => 'student@lms.local',
        ];

        $names = collect(app('router')->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->unique()
            ->filter(fn (string $name) => str_ends_with($name, '.index'))
            ->values();

        $this->assertGreaterThan(20, $names->count(), 'Expected a broad set of index routes to probe.');

        // Guard against a vacuous probe: the dataset must actually be populated,
        // otherwise the @foreach bodies this test exists to exercise stay empty.
        $this->assertGreaterThan(0, \App\Models\Course::count(), 'demo courses missing');
        $this->assertGreaterThan(0, \App\Models\ClassModel::count(), 'demo classes missing');
        $this->assertGreaterThan(0, \App\Models\Enrollment::count(), 'demo enrollments missing');
        $this->assertGreaterThan(0, \App\Models\Module::count(), 'demo modules missing');

        $failures = [];
        $probed = 0;

        foreach ($actors as $label => $email) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $failures[] = "{$label}: seed user {$email} is missing";
                continue;
            }

            foreach ($names as $name) {
                try {
                    $uri = route($name);
                } catch (\Throwable) {
                    continue; // needs route params; not reachable as a plain GET
                }

                $probed++;

                try {
                    $code = $this->actingAs($user)->get($uri)->getStatusCode();
                } catch (\Throwable $e) {
                    $failures[] = "{$label} {$name} [{$uri}] -> ".get_class($e).': '.$e->getMessage();
                    continue;
                }

                if ($code >= 500) {
                    $failures[] = "{$label} {$name} [{$uri}] -> HTTP {$code}";
                }
            }
        }

        $this->assertGreaterThan(20, $probed, 'The probe rendered too few pages to be meaningful.');

        $this->assertSame(
            [],
            $failures,
            sprintf("Probed %d index pages with demo data.\nFailing pages:\n", $probed).implode("\n", $failures)
        );
    }
}

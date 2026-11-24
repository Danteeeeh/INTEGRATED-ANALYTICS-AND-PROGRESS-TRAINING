<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesLmsUsers;
use Tests\TestCase;

class DiagnosticRouteSecurityTest extends TestCase
{
    use CreatesLmsUsers;

    public function test_guests_cannot_open_the_diagnostic_route_even_with_the_app_key_in_the_query_string(): void
    {
        $this->get('/diagnostic')->assertRedirect(route('login'));

        $this->get('/diagnostic?secret=' . urlencode((string) config('app.key')))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_open_the_diagnostic_route(): void
    {
        $student = $this->makeUser('student');

        $this->actingAs($student)
            ->get('/diagnostic')
            ->assertForbidden();
    }

    public function test_an_admin_can_open_the_diagnostic_route_without_a_query_string_token(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get('/diagnostic')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('environment.app_env', 'testing')
            ->assertJsonPath('database_connection.status', 'success');
    }

    public function test_deployed_diagnostics_redact_sensitive_details_and_do_not_list_users(): void
    {
        $admin = $this->makeUser('admin');
        $student = $this->makeUser('student', [
            'email' => 'private.student@example.test',
        ]);

        foreach (['production', 'staging'] as $environment) {
            config(['app.env' => $environment]);

            $response = $this->actingAs($admin)
                ->get('/diagnostic')
                ->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')
                ->assertJsonPath('environment.app_env', $environment)
                ->assertJsonPath('environment.db_host', '[redacted]')
                ->assertJsonPath('environment.db_database', '[redacted]')
                ->assertJsonPath('database_connection.database', '[redacted]')
                ->assertJsonMissingPath('user_list')
                ->assertJsonMissingPath('seeded_accounts');

            $content = $response->getContent();

            $this->assertStringNotContainsString($student->email, $content);
            $this->assertStringNotContainsString('Password123!', $content);
        }
    }
}

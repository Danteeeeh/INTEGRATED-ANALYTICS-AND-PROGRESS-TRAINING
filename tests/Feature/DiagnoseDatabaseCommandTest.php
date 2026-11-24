<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DiagnoseDatabaseCommandTest extends TestCase
{
    public function test_it_reports_only_non_sensitive_connection_schema_and_count_details(): void
    {
        $active = User::factory()->admin()->create([
            'email' => 'private.admin@example.test',
            'password' => 'diagnostic-test-password',
            'status' => 'active',
        ]);
        $inactive = User::factory()->student()->create([
            'email' => 'private.student@example.test',
            'password' => 'diagnostic-test-password',
            'status' => 'inactive',
        ]);

        $exitCode = Artisan::call('db:diagnose');
        $output = Artisan::output();
        $lines = preg_split('/\R/', $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Database connection successful', $output);
        $this->assertStringContainsString('users table exists', $output);
        $this->assertStringContainsString('roles table exists', $output);
        $this->assertStringContainsString('Total users in database: 2', $output);
        $this->assertContains('   Active: 1', $lines);
        $this->assertContains('   Inactive: 1', $lines);
        $this->assertContains('   Pending: 0', $lines);

        $this->assertStringNotContainsString($active->email, $output);
        $this->assertStringNotContainsString($inactive->email, $output);
        $this->assertStringNotContainsString('diagnostic-test-password', $output);
        $this->assertStringNotContainsString('Password Hash', $output);
        $this->assertStringNotContainsString('Email:', $output);
        $this->assertStringNotContainsString(substr((string) $active->password, 0, 20), $output);
    }
}

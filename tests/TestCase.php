<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions for tests
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        // Never let a test talk to a real LiveKit project. The local .env can
        // hold production credentials, and ending a class now calls the
        // RoomService API — a stray request would hit LiveKit Cloud for real.
        // Tests that exercise LiveKit set fake values plus Http::fake().
        config([
            'services.livekit.url' => null,
            'services.livekit.api_key' => null,
            'services.livekit.api_secret' => null,
        ]);
    }
}

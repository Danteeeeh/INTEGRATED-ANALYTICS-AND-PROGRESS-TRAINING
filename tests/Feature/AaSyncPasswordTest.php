<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\LmsPasswordReset;
use App\Services\AaSyncService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AaSyncPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /**
     * Expose the protected user upsert without needing a real AA connection.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function service(array $rows): AaSyncService
    {
        $this->rows = $rows;

        return new class($this) extends AaSyncService
        {
            public function __construct(private AaSyncPasswordTest $test) {}

            protected function aaRows(string $table): array
            {
                return array_map(fn (array $row) => (object) $row, $this->test->rowsForTest());
            }

            public function import(array $map, int $roleId): array
            {
                return $this->upsertUsers($map, $roleId, []);
            }

            public function passwordFor(): string
            {
                return $this->newAccountPassword();
            }
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rowsForTest(): array
    {
        return $this->rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function studentMap(): array
    {
        return [
            'table' => 'students',
            'columns' => [
                'identifier' => 'student_no',
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'email' => 'email',
                'status' => 'status',
            ],
            'status_map' => ['active' => 'active'],
        ];
    }

    private function studentRoleId(): int
    {
        return (int) Role::where('slug', Role::STUDENT)->value('id');
    }

    private function instructorRoleId(): int
    {
        return (int) Role::where('slug', Role::INSTRUCTOR)->value('id');
    }

    public function test_new_accounts_get_unique_random_passwords_not_the_public_fallback(): void
    {
        $service = $this->service([
            [
                'student_no' => 'S-001',
                'first_name' => 'AA',
                'last_name' => 'One',
                'email' => 'aa-one@example.com',
                'status' => 'active',
            ],
            [
                'student_no' => 'S-002',
                'first_name' => 'AA',
                'last_name' => 'Two',
                'email' => 'aa-two@example.com',
                'status' => 'active',
            ],
        ]);

        $first = $service->passwordFor();
        $second = $service->passwordFor();

        $this->assertNotSame('Welcome@123', $first);
        $this->assertNotSame($first, $second);
        $this->assertSame(32, strlen($first));

        $counts = $service->import($this->studentMap(), $this->studentRoleId());

        $this->assertSame(2, $counts['created']);

        foreach (User::whereIn('email', ['aa-one@example.com', 'aa-two@example.com'])->get() as $user) {
            $this->assertFalse(Hash::check('Welcome@123', $user->password));
            $this->assertNotSame('', $user->password);
        }
    }

    public function test_new_faculty_accounts_also_get_unique_random_passwords(): void
    {
        $service = $this->service([
            [
                'student_no' => 'F-001',
                'first_name' => 'AA',
                'last_name' => 'Faculty One',
                'email' => 'aa-faculty-one@example.com',
                'status' => 'active',
            ],
            [
                'student_no' => 'F-002',
                'first_name' => 'AA',
                'last_name' => 'Faculty Two',
                'email' => 'aa-faculty-two@example.com',
                'status' => 'active',
            ],
        ]);

        $counts = $service->import($this->studentMap(), $this->instructorRoleId());

        $this->assertSame(2, $counts['created']);

        $users = User::whereIn('email', ['aa-faculty-one@example.com', 'aa-faculty-two@example.com'])->get();
        $this->assertCount(2, $users);

        foreach ($users as $user) {
            $this->assertNotSame('Welcome@123', $user->password);
            $this->assertFalse(Hash::check('Welcome@123', $user->password));
        }

        $this->assertNotSame($users[0]->password, $users[1]->password);
    }

    public function test_new_accounts_receive_an_activation_email_with_a_password_set_link(): void
    {
        $service = $this->service([[
            'student_no' => 'S-400',
            'first_name' => 'Activation',
            'last_name' => 'Email',
            'email' => 'activation-email@example.com',
            'status' => 'active',
        ]]);

        $service->import($this->studentMap(), $this->studentRoleId());

        $user = User::where('email', 'activation-email@example.com')->firstOrFail();

        Notification::assertSentTo($user, LmsPasswordReset::class, function (LmsPasswordReset $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/reset-password/')
                && str_contains(implode(' ', $mail->introLines), 'account has been created');
        });

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_existing_accounts_do_not_receive_another_activation_email(): void
    {
        $existing = User::factory()->student()->create([
            'email' => 'already-active@example.com',
            'password' => 'ExistingPass!23',
        ]);

        $service = $this->service([[
            'student_no' => 'S-401',
            'first_name' => 'Already',
            'last_name' => 'Active',
            'email' => $existing->email,
            'status' => 'active',
        ]]);

        $service->import($this->studentMap(), $this->studentRoleId());

        Notification::assertNothingSent();
    }

    public function test_a_new_aa_account_can_activate_through_the_password_reset_flow(): void
    {
        $service = $this->service([[
            'student_no' => 'S-300',
            'first_name' => 'Activation',
            'last_name' => 'Student',
            'email' => 'activation@example.com',
            'status' => 'active',
        ]]);

        $service->import($this->studentMap(), $this->studentRoleId());
        $user = User::where('email', 'activation@example.com')->firstOrFail();

        $token = null;
        Notification::assertSentTo($user, LmsPasswordReset::class, function (LmsPasswordReset $notification) use (&$token): bool {
            $token = $notification->token;

            return $token !== null;
        });

        $this->assertNotNull($token);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewAA!Password23',
            'password_confirmation' => 'NewAA!Password23',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewAA!Password23', $user->fresh()->password));
    }

    public function test_legacy_shared_password_config_keys_are_ignored(): void
    {
        // These keys no longer exist in config/aa_sync.php. An old cached .env
        // or config value must not make new accounts share a password.
        config([
            'aa_sync.default_student_password' => 'Welcome@123',
            'aa_sync.default_faculty_password' => 'ExplicitShared!23',
        ]);

        $service = $this->service([[
            'student_no' => 'S-150',
            'first_name' => 'Legacy',
            'last_name' => 'Config',
            'email' => 'legacy-config@example.com',
            'status' => 'active',
        ]]);

        $counts = $service->import($this->studentMap(), $this->studentRoleId());

        $this->assertSame(1, $counts['created']);
        $user = User::where('email', 'legacy-config@example.com')->firstOrFail();
        $this->assertFalse(Hash::check('Welcome@123', $user->password));
        $this->assertFalse(Hash::check('ExplicitShared!23', $user->password));
        $this->assertSame(32, strlen($service->passwordFor()));
    }

    public function test_syncing_an_existing_account_never_rotates_its_password(): void
    {
        config([
            'aa_sync.reset_passwords' => true,
            'aa_sync.default_student_password' => 'ExplicitShared!23',
        ]);

        $existing = User::factory()->student()->create([
            'email' => 'existing@example.com',
            'password' => 'ExistingPass!23',
        ]);

        $service = $this->service([[
            'student_no' => 'S-200',
            'first_name' => 'Existing',
            'last_name' => 'Student',
            'email' => $existing->email,
            'status' => 'active',
        ]]);

        $counts = $service->import($this->studentMap(), $this->studentRoleId());

        $this->assertSame(1, $counts['updated']);
        $this->assertTrue(Hash::check('ExistingPass!23', $existing->fresh()->password));
    }
}

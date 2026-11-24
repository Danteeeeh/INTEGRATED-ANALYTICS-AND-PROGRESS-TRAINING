<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

/**
 * Cleans up the shared demo logins that older deployments seeded.
 *
 * UserSeeder no longer creates them on a production site, but a database that
 * was seeded before that rule still holds four accounts whose password is
 * published in this repository and which skip the login one-time code. This
 * command removes that access without touching any other user.
 *
 * Safety rails:
 *   - only the four addresses below, matched exactly — never by role, id or a
 *     LIKE pattern, so a real account cannot be swept up;
 *   - `preview` is the default and writes nothing;
 *   - every write action stops and asks before changing anything (--force
 *     skips the prompt for scripted runs);
 *   - the last active administrator is never removed;
 *   - `delete` is a soft delete, so the rows stay recoverable;
 *   - `rotate-password` gives each account its own random password, shows it
 *     once, and never writes it to the logs;
 *   - open sessions and API tokens of the affected accounts are revoked.
 *
 * Usage, from the project root on the server:
 *
 *   php artisan lms:demo-accounts                 # list them, change nothing
 *   php artisan lms:demo-accounts rotate-password # new random password per account
 *   php artisan lms:demo-accounts deactivate      # block logins, keep the rows
 *   php artisan lms:demo-accounts delete          # soft-delete the rows
 */
class ManageDemoAccounts extends Command
{
    /** The demo logins UserSeeder used to create. Exact addresses only. */
    public const EMAILS = [
        'admin@lms.local',
        'instructor@lms.local',
        'student@lms.local',
        'aa-staff@lms.local',
    ];

    protected $signature = 'lms:demo-accounts
                            {action=preview : preview, rotate-password, deactivate or delete}
                            {--force : Skip the confirmation prompt (for scripts)}';

    protected $description = 'Preview, rotate the password of, deactivate or delete the seeded demo accounts and nothing else';

    public function handle(AuditService $audit): int
    {
        $action = strtolower(trim((string) $this->argument('action')));

        if (! in_array($action, ['preview', 'rotate-password', 'deactivate', 'delete'], true)) {
            $this->error("Unknown action \"{$action}\". Use: preview, rotate-password, deactivate or delete.");

            return self::INVALID;
        }

        $users = User::query()
            ->whereIn('email', self::EMAILS)
            ->with('role')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $trashed = User::onlyTrashed()->whereIn('email', self::EMAILS)->count();

            $this->info($trashed > 0
                ? "No demo accounts found — {$trashed} of them are already soft-deleted."
                : 'No demo accounts found — nothing to do.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Email', 'Role', 'Status', 'Created'],
            $users->map(fn (User $user) => [
                $user->id,
                $user->email,
                $user->role?->name ?? '—',
                $user->status ?? '—',
                $user->created_at?->toDateString() ?? '—',
            ])->all()
        );

        if ($action === 'preview') {
            $this->newLine();
            $this->line('Preview only — nothing was changed.');
            $this->line('  php artisan lms:demo-accounts rotate-password   new random password per account, shown once');
            $this->line('  php artisan lms:demo-accounts deactivate        block logins, keep the rows');
            $this->line('  php artisan lms:demo-accounts delete            soft-delete, recoverable in the database');

            return self::SUCCESS;
        }

        if ($action === 'rotate-password') {
            return $this->rotatePasswords($users, $audit);
        }

        $targets = $action === 'deactivate'
            ? $users->reject(fn (User $user) => $user->status === 'inactive')
            : $users;

        if ($targets->isEmpty()) {
            $this->info('Every demo account is already inactive — nothing to do.');

            return self::SUCCESS;
        }

        if ($blocked = $this->lastActiveAdministrator($targets)) {
            $this->error("Refusing to continue: {$blocked->email} is the last active administrator.");
            $this->line('Give another account the administrator role first, then run this again.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirmChange($action, $targets->count())) {
            $this->warn('Cancelled — nothing was changed.');

            return self::SUCCESS;
        }

        $changed = $this->apply($targets, $action, $audit);

        $this->newLine();
        $this->info(($action === 'delete' ? 'Soft-deleted ' : 'Deactivated ')."{$changed} demo account(s).");
        $this->line('Only the addresses listed above were touched; every other user is unchanged.');

        if ($action === 'delete') {
            $this->line('The rows are still in the database and can be restored with');
            $this->line('  User::withTrashed()->find(<id>)->restore()');
        }

        return self::SUCCESS;
    }

    private function confirmChange(string $action, int $count): bool
    {
        $this->newLine();

        return $this->confirm(
            $action === 'delete'
                ? "Soft-delete {$count} demo account(s)? They leave Admin → Users but stay recoverable in the database."
                : "Deactivate {$count} demo account(s)? You can re-activate them from Admin → Users later.",
            false
        );
    }

    private function apply(Collection $targets, string $action, AuditService $audit): int
    {
        $hasSessions = Schema::hasTable('sessions');
        $hasTokens = Schema::hasTable('personal_access_tokens');
        $changed = 0;

        DB::transaction(function () use ($targets, $action, $audit, $hasSessions, $hasTokens, &$changed) {
            foreach ($targets as $user) {
                $this->revokeAccess($user, $hasSessions, $hasTokens);

                if ($action === 'delete') {
                    $audit->log(null, 'user.deleted', User::class, $user->id, [
                        'email' => $user->email,
                        'status' => $user->status,
                    ]);

                    $user->delete();
                } else {
                    $audit->log(null, 'user.deactivated', User::class, $user->id, [
                        'status' => $user->status,
                    ], [
                        'status' => 'inactive',
                    ]);

                    $user->update(['status' => 'inactive']);
                }

                $changed++;
            }
        });

        return $changed;
    }

    /**
     * Give every matched account its own random password and show it once.
     *
     * The rows keep their status: only the credential changes, so an account
     * that must keep working can be handed the new password while the one
     * published in this repository stops working.
     *
     * @param  Collection<int, User>  $users
     */
    private function rotatePasswords(Collection $users, AuditService $audit): int
    {
        if (! $this->option('force') && ! $this->confirm(
            "Rotate the password of {$users->count()} demo account(s)? Their current sessions and API tokens will be signed out.",
            false
        )) {
            $this->warn('Cancelled — nothing was changed.');

            return self::SUCCESS;
        }

        $hasSessions = Schema::hasTable('sessions');
        $hasTokens = Schema::hasTable('personal_access_tokens');
        $rotated = [];

        DB::transaction(function () use ($users, $audit, $hasSessions, $hasTokens, &$rotated) {
            foreach ($users as $user) {
                $password = Str::random(24);

                $this->revokeAccess($user, $hasSessions, $hasTokens);

                // Never log the password itself.
                $audit->log(null, 'user.password_rotated', User::class, $user->id, null, ['rotated' => true]);

                $user->update(['password' => $password]);

                $rotated[] = [$user->email, $password];
            }
        });

        $this->newLine();
        $this->table(['Email', 'New password'], $rotated);
        $this->info('Rotated '.count($rotated).' demo password(s). Only the accounts listed above were touched.');
        $this->warn('Shown once — store them now. They are not written to the log.');

        return self::SUCCESS;
    }

    private function revokeAccess(User $user, bool $hasSessions, bool $hasTokens): void
    {
        // A blocked or re-keyed account must lose every key it still holds, or
        // the browser session it already has would keep working.
        if ($hasSessions) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if ($hasTokens) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', $user->getMorphClass())
                ->where('tokenable_id', $user->id)
                ->delete();
        }
    }

    /**
     * The administrator we must not remove, or null when another active
     * administrator would remain. Mirrors UserService::deactivateUser().
     *
     * @param  Collection<int, User>  $targets
     */
    private function lastActiveAdministrator(Collection $targets): ?User
    {
        $admin = $targets->first(fn (User $user) => $user->isAdmin());

        if ($admin === null) {
            return null;
        }

        $anotherAdminRemains = User::query()
            ->where('status', 'active')
            ->whereNotIn('id', $targets->pluck('id')->all())
            ->whereHas('role', fn ($query) => $query->where('slug', Role::ADMIN))
            ->exists();

        return $anotherAdminRemains ? null : $admin;
    }
}

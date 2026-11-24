<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Verify that HostForge is actually giving the app durable storage.
 *
 * A write/read probe proves the filesystem works right now; it cannot prove the
 * volume survives a redeploy. The marker options cover that gap: write it
 * before a redeploy and verify it afterwards. A missing marker means the path
 * is ephemeral (or LMS_STORAGE_PATH changed), so publishing would lose uploads
 * and backups.
 */
class StorageHealth extends Command
{
    protected $signature = 'lms:storage-health
        {--write-marker : Write a marker that must survive the next redeploy}
        {--verify-marker : Fail when the marker from the previous deploy is gone}
        {--require-persistent : Fail when storage is inside the release directory or the public link is wrong}';

    protected $description = 'Verify upload/backup storage roots, permissions, public link and persistence';

    public function handle(): int
    {
        if ($this->option('write-marker') && $this->option('verify-marker')) {
            $this->error('Use either --write-marker or --verify-marker, not both.');

            return self::FAILURE;
        }

        $root = $this->normalise((string) config('filesystems.disks.local.root'));
        $public = $this->normalise((string) config('filesystems.disks.public.root'));
        $backups = $this->normalise(app(BackupService::class)->directory());
        $base = $this->normalise(base_path());

        $this->components->info('Storage roots');
        $this->line('  uploads:        '.$root);
        $this->line('  public uploads: '.$public);
        $this->line('  backups:        '.$backups);
        $this->line('  default disk:   '.config('filesystems.default'));
        $this->newLine();

        $ok = true;
        $ok = $this->probe('Uploads', $root) && $ok;
        $ok = $this->probe('Public uploads', $public) && $ok;
        $ok = $this->probe('Backups', $backups) && $ok;

        $insideRelease = $root === $base || str_starts_with($root, $base.'/');
        $requirePersistent = (bool) $this->option('require-persistent');

        if ($insideRelease) {
            $message = 'Uploads are inside the release directory. That is not proof of persistence; '
                .'set LMS_STORAGE_PATH to the HostForge persistent volume.';

            if ($requirePersistent) {
                $this->components->error($message);
                $ok = false;
            } else {
                $this->components->warn($message);
            }
        } else {
            $this->components->info('Uploads are outside the release directory.');
        }

        $link = public_path('storage');
        $linkTarget = is_link($link) ? readlink($link) : null;
        $linkTarget = $linkTarget !== false ? $this->normalise((string) $linkTarget) : '';

        $resolvedLink = realpath($link);
        $resolvedPublic = realpath($public);
        $linkMatches = ($resolvedLink !== false && $resolvedPublic !== false)
            ? $this->normalise($resolvedLink) === $this->normalise($resolvedPublic)
            : $linkTarget !== '' && $linkTarget === $public;

        if (! $linkMatches) {
            $message = 'public/storage does not point at the public upload root. Run: php artisan storage:link --force';

            if ($requirePersistent) {
                $this->components->error($message);
                $ok = false;
            } else {
                $this->components->warn($message);
            }
        } else {
            $this->components->info('public/storage points at the public upload root.');
        }

        $marker = $root.'/.lms-storage-marker';

        if ($this->option('write-marker')) {
            $payload = json_encode([
                'id' => (string) Str::uuid(),
                'created_at' => now()->toIso8601String(),
                'app_url' => (string) config('app.url'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            try {
                File::put($marker, $payload);
                $this->components->info('Persistence marker written: '.$marker);
                $this->line('Redeploy the app, then run: php artisan lms:storage-health --verify-marker');
            } catch (\Throwable $e) {
                $this->components->error('Could not write the persistence marker: '.$e->getMessage());
                $ok = false;
            }
        } elseif ($this->option('verify-marker')) {
            if (! File::exists($marker)) {
                $this->components->error(
                    'Persistence marker is missing at '.$marker.'. The storage root did not survive '
                    .'the redeploy, or LMS_STORAGE_PATH changed. Do not publish with ephemeral storage.'
                );
                $ok = false;
            } else {
                try {
                    $this->components->info('Persistence marker survived: '.$marker);
                    $this->line(File::get($marker));
                } catch (\Throwable $e) {
                    $this->components->error('The persistence marker exists but cannot be read: '.$e->getMessage());
                    $ok = false;
                }
            }
        }

        $this->newLine();

        if (! $ok) {
            $this->components->error('Storage preflight failed.');

            return self::FAILURE;
        }

        $this->components->info('Storage preflight passed.');

        return self::SUCCESS;
    }

    /**
     * Create the directory, prove it is writable, and clean up the probe.
     */
    private function probe(string $label, string $path): bool
    {
        try {
            File::ensureDirectoryExists($path);
        } catch (\Throwable $e) {
            $this->components->error($label.' cannot be created at '.$path.': '.$e->getMessage());

            return false;
        }

        if (! is_writable($path)) {
            $this->components->error($label.' is not writable: '.$path);

            return false;
        }

        $probe = $path.'/.lms-health-'.Str::random(16).'.txt';

        try {
            File::put($probe, 'ok');

            if (File::get($probe) !== 'ok') {
                throw new \RuntimeException('read-back did not match');
            }

            File::delete($probe);
        } catch (\Throwable $e) {
            File::delete($probe);
            $this->components->error($label.' write/read probe failed: '.$e->getMessage());

            return false;
        }

        $this->components->info($label.' is writable: '.$path);

        return true;
    }

    private function normalise(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));

        return $path === '/' ? $path : rtrim($path, '/');
    }
}

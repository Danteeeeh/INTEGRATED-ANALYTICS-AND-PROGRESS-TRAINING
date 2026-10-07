<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Reports the three ceilings that govern whether an upload can succeed.
 *
 * A "413 Request Entity Too Large" is answered by nginx before Laravel ever
 * runs, so the browser shows a bare error page and the file size in the message
 * is misleading — a 19KB PDF can be refused just as readily as a 19MB one. This
 * command makes the mismatch visible instead of leaving it to guesswork.
 */
class CheckUploads extends Command
{
    protected $signature = 'lms:check-uploads';

    protected $description = 'Compare the app, PHP and web server upload limits';

    public function handle(): int
    {
        $this->line('Upload limits — the smallest one wins.');
        $this->newLine();

        // ── The application ceiling ──────────────────────────────
        $this->line('  <fg=cyan>Application (Laravel validation)</>');
        $this->newLine();

        $smallestApp = null;

        foreach (config('lms.uploads') as $key => $kb) {
            $this->line(sprintf('      %-24s %s', $key, $this->humanise($kb)));

            if ($smallestApp === null || $kb < $smallestApp) {
                $smallestApp = $kb;
            }
        }

        // ── PHP's ceiling ────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=cyan>PHP (php.ini)</>');

        $postMax = $this->iniBytes('post_max_size');
        $uploadMax = $this->iniBytes('upload_max_filesize');

        $this->line(sprintf('      %-24s %s', 'post_max_size', $this->fromBytes($postMax)));
        $this->line(sprintf('      %-24s %s', 'upload_max_filesize', $this->fromBytes($uploadMax)));
        $this->line(sprintf('      %-24s %s', 'memory_limit', ini_get('memory_limit')));

        // ── Web server ───────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=cyan>Web server (nginx)</>');
        $this->line('      <fg=yellow>client_max_body_size  not readable from PHP</>');
        $this->line('      A 413 here means the request never reached Laravel.');

        // ── Verdict ──────────────────────────────────────────────
        $this->newLine();

        $effective = min(array_filter([$smallestApp, $postMax, $uploadMax]));

        $this->line(sprintf(
            '  Smallest ceiling PHP can see: <fg=%s>%s</>',
            $effective === $uploadMax ? 'yellow' : 'green',
            $this->humanise((int) $effective)
        ));

        if ($postMax !== null && $smallestApp !== null && $postMax < $smallestApp) {
            $this->newLine();
            $this->warn(sprintf(
                'post_max_size (%s) is below the largest app limit (%s). Requests past that are',
                $this->humanise($postMax),
                $this->humanise($smallestApp)
            ));
            $this->warn('rejected before validation runs. Raise post_max_size in php.ini.');

            return self::SUCCESS;
        }

        if ($uploadMax !== null && $smallestApp !== null && $uploadMax < $smallestApp) {
            $this->newLine();
            $this->warn(sprintf(
                'upload_max_filesize (%s) is below the largest app limit (%s).',
                $this->humanise($uploadMax),
                $this->humanise($smallestApp)
            ));
            $this->warn('Raise upload_max_filesize in php.ini, or lower the config value.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('PHP and the application agree.');
        $this->line('If uploads still fail with 413, the limit is nginx\'s client_max_body_size.');
        $this->line('Set it in the server block and reload:');
        $this->newLine();
        $this->line('    client_max_body_size 200M;');
        $this->line('    # then: nginx -t && systemctl reload nginx');
        $this->newLine();
        $this->line('Then run: php artisan optimize:clear');

        return self::SUCCESS;
    }

    /**
     * A php.ini size as bytes, or null when it is not set.
     */
    private function iniBytes(string $key): ?int
    {
        $value = trim((string) ini_get($key));

        if ($value === '' || strtolower($value) === '0') {
            return null;
        }

        if (! preg_match('/^(\d+(?:\.\d+)?)\s*([kmg])?b?$/i', $value, $m)) {
            return null;
        }

        $multipliers = ['' => 1, 'k' => 1024, 'm' => 1024 ** 2, 'g' => 1024 ** 3];

        return (int) ((float) $m[1] * $multipliers[strtolower($m[2] ?? '')]);
    }

    private function humanise(int $kilobytes): string
    {
        if ($kilobytes >= 1024) {
            return rtrim(rtrim(number_format($kilobytes / 1024, 1), '0'), '.').' MB';
        }

        return $kilobytes.' KB';
    }

    /** Render a byte count for humans, or "not reported" when PHP has none. */
    private function fromBytes(?int $bytes): string
    {
        return $bytes === null ? 'not reported' : $this->humanise((int) round($bytes / 1024));
    }
}
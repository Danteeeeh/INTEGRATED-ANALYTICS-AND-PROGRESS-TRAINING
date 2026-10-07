<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Repairs lesson material rows whose recorded path no longer matches disk.
 *
 * Two layouts existed for the same upload:
 *
 *   disk=public, path=public/lesson_materials/x.pdf
 *       -> storage/app/public/public/lesson_materials/x.pdf   (doubled)
 *   disk=local,  path=public/lesson_materials/x.pdf
 *       -> storage/app/public/lesson_materials/x.pdf          (intended)
 *
 * Only the second matches what is actually on disk, which is why these rows
 * 404. This command locates the bytes wherever they really are and repoints
 * the row at that location, instead of trusting the recorded path.
 *
 * Dry-run by default; pass --apply to write.
 */
class RepairLessonMaterials extends Command
{
    protected $signature = 'lms:repair-lesson-materials
                            {--apply : Actually update media_files.path (default is a dry run)}';

    protected $description = 'Repoint lesson material rows at the location their bytes actually occupy';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $rows = DB::table('media_files')
            ->join('lesson_materials', 'lesson_materials.media_file_id', '=', 'media_files.id')
            ->select(
                'media_files.id',
                'media_files.path',
                'media_files.disk',
                'media_files.original_name'
            )
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No lesson materials found — nothing to repair.');

            return self::SUCCESS;
        }

        $fixed = 0;
        $samples = [];
        $unresolved = [];

        foreach ($rows as $row) {
            [$resolvedPath, $resolvedDisk] = $this->locate($row->path, $row->disk);

            if ($resolvedPath === null) {
                $unresolved[] = sprintf(
                    'media_files#%s %s (disk=%s, path=%s)',
                    $row->id,
                    $row->original_name,
                    $row->disk,
                    $row->path
                );

                continue;
            }

            if ($resolvedPath === $row->path && $resolvedDisk === $row->disk) {
                continue;
            }

            $fixed++;
            $samples[] = sprintf(
                'media_files#%s  %s: %s -> [%s] %s',
                $row->id,
                $row->original_name,
                $row->path,
                $resolvedDisk,
                $resolvedPath
            );

            if ($apply) {
                MediaFile::withoutEvents(fn () => MediaFile::whereKey($row->id)->update([
                    'path' => $resolvedPath,
                    'disk' => $resolvedDisk,
                ]));
            }
        }

        $this->line(sprintf('Scanned %d lesson material row(s).', $rows->count()));

        if ($fixed === 0 && $unresolved === []) {
            $this->info('All lesson materials already point at their real bytes.');

            return self::SUCCESS;
        }

        if ($fixed > 0) {
            $this->newLine();
            $this->line('Would repoint:');

            foreach ($samples as $sample) {
                $this->line('  '.$sample);
            }
        }

        if ($unresolved !== []) {
            $this->newLine();
            $this->warn(sprintf('%d row(s) have no bytes anywhere I looked:', count($unresolved)));

            foreach ($unresolved as $sample) {
                $this->warn('  '.$sample);
            }

            $this->warn('  Re-upload these from the lesson edit page; the blob is genuinely gone.');
        }

        $this->newLine();

        if ($apply) {
            $this->info("Repointed {$fixed} row(s).");

            return self::SUCCESS;
        }

        $this->warn("Dry run — {$fixed} row(s) would change. Re-run with --apply to write them.");

        return self::SUCCESS;
    }

    /**
     * Find the bytes for a lesson material, wherever they actually live.
     *
     * @return array{0: ?string, 1: string} [path, disk] - path null when no bytes found
     */
    private function locate(string $path, string $disk): array
    {
        // Candidate (disk, path) pairs, in preference order: the recorded
        // location first, then the layouts this bug has produced.
        $candidates = [[$disk, $path]];

        $bare = str_starts_with($path, 'public/') ? substr($path, 7) : $path;

        $candidates[] = ['public', $bare];
        $candidates[] = ['local', $path];
        $candidates[] = ['local', $bare];

        foreach ($candidates as [$candidateDisk, $candidatePath]) {
            if ($candidatePath === '' || $candidatePath === null) {
                continue;
            }

            try {
                if (Storage::disk($candidateDisk)->exists($candidatePath)) {
                    return [$candidatePath, $candidateDisk];
                }
            } catch (\Throwable) {
                // An unusable disk is not a match; keep looking.
                continue;
            }
        }

        return [null, $disk];
    }
}
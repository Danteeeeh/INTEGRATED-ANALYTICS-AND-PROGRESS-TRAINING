<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reports submission attachments the instructor cannot open.
 *
 * A SubmissionFile row can outlive the file it points at, in two ways:
 *
 *   deleted   — the MediaFile row is soft-deleted, so route model binding
 *               never matches it and files.download returns a bare 404.
 *   no-bytes  — the MediaFile row is intact but the blob is gone from the
 *               disk (container rebuilt, backup restored without storage, or
 *               the file was cleaned up by hand).
 *
 * Both look identical to an instructor: a 404 with no explanation.
 */
class DiagnoseSubmissionFiles extends Command
{
    protected $signature = 'lms:diagnose-submission-files
                            {--fix : Soft-delete the SubmissionFile rows that point at nothing, so the UI stops offering the dead attachment}';

    protected $description = 'Find submission attachments whose file record or stored bytes are missing';

    public function handle(): int
    {
        $rows = DB::table('submission_files')
            ->leftJoin('media_files', 'submission_files.media_file_id', '=', 'media_files.id')
            ->select(
                'submission_files.id as submission_file_id',
                'submission_files.assignment_submission_id',
                'submission_files.original_name as submission_name',
                'media_files.id as media_file_id',
                'media_files.path',
                'media_files.disk',
                'media_files.deleted_at',
                'assignment_submissions.assignment_id',
                'assignments.class_id',
                'assignments.title as assignment_title'
            )
            ->leftJoin('assignment_submissions', 'submission_files.assignment_submission_id', '=', 'assignment_submissions.id')
            ->leftJoin('assignments', 'assignment_submissions.assignment_id', '=', 'assignments.id')
            ->orderBy('submission_files.id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No submission attachments found.');

            return self::SUCCESS;
        }

        $broken = [];

        foreach ($rows as $row) {
            $reason = match (true) {
                $row->media_file_id === null => 'no-media-row (dangling foreign key)',
                $row->deleted_at !== null => 'deleted (media_files row is soft-deleted)',
                default => $this->bytesMissing($row) ? 'no-bytes (record exists, file not on disk)' : null,
            };

            if ($reason !== null) {
                $broken[] = [$row, $reason];
            }
        }

        $this->info(sprintf('Checked %d submission attachment(s).', $rows->count()));

        if ($broken === []) {
            $this->info('All attachments are intact.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d attachment(s) cannot be opened:', count($broken)));

        foreach ($broken as [$row, $reason]) {
            // Plain lines rather than a table: the disk:path column is wide
            // enough that a table would wrap every row and become unreadable.
            $this->line(sprintf(
                '  - [%s] %s | %s | %s | %s',
                $row->submission_file_id,
                $reason,
                $row->submission_name ?? 'unnamed',
                $row->assignment_title ?? 'unknown assignment',
                ($row->disk ?? '?').':'.($row->path ?? '?')
            ));
        }

        if (! $this->option('fix')) {
            $this->line('');
            $this->comment('Re-run with --fix to soft-delete the dead attachment links.');

            return self::SUCCESS;
        }

        foreach ($broken as [$row, $reason]) {
            // 'no-bytes' keeps its MediaFile row: only the link is dead, and
            // an admin may still be able to restore the blob from a backup.
            // Compare on the reason *prefix* — $reason carries a parenthetical
            // detail suffix, so an exact match would never hit.
            if (! str_starts_with($reason, 'no-bytes')) {
                $media = MediaFile::withTrashed()->find($row->media_file_id);
                $media?->delete();
            }
        }

        $this->info(sprintf('Cleaned %d dead attachment link(s).', count($broken)));

        return self::SUCCESS;
    }

    private function bytesMissing(object $row): bool
    {
        $media = MediaFile::find($row->media_file_id);

        return $media ? ! $media->fileExists() : false;
    }
}
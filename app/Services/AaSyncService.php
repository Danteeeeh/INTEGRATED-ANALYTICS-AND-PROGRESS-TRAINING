<?php

namespace App\Services;

use App\Models\AaSyncLog;
use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * "Sync from AA" — pulls departments, programs, sections, students, faculty,
 * classes, and enrollments from the Academic Affairs (AA) database into the
 * LMS. Read-only against AA; every write lands on the default LMS connection.
 *
 * The AA table/column mapping lives in config/aa_sync.php; the AA connection
 * itself is the "aa" connection in config/database.php (AA_DB_* env values).
 */
class AaSyncService
{
    public const CONNECTION = 'aa';

    /**
     * Run the sync. Returns the AaSyncLog row for this run.
     *
     * @param  array<int, string>|null  $entities  Entities to sync (defaults to config sync_order).
     */
    public function run(?array $entities = null, ?int $performedBy = null): AaSyncLog
    {
        $config = config('aa_sync');
        $order = $config['sync_order'] ?? [];

        // Drop unknown entities but keep the configured order — parents
        // (departments) must always be upserted before children (students).
        $entities = array_values(array_intersect($order, $entities ?: $order));

        $log = AaSyncLog::create([
            'performed_by' => $performedBy ?? auth()->id(),
            'entities' => $entities,
            'status' => AaSyncLog::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        try {
            $summary = [];

            foreach ($entities as $entity) {
                $method = 'sync'.Str::studly($entity);
                if (! method_exists($this, $method)) {
                    continue;
                }

                // One transaction per entity: if an entity fails halfway it
                // rolls back cleanly, earlier entities stay applied, and the
                // failure lands in the log (spec §3: handle failed sync safely
                // and allow authorized retries). Upserts are idempotent, so a
                // retry does not duplicate records.
                $summary[$entity] = DB::transaction(
                    fn () => $this->{$method}($config['tables'][$entity] ?? [])
                );
            }

            $totals = [
                'records_processed' => array_sum(array_column($summary, 'processed')),
                'records_created' => array_sum(array_column($summary, 'created')),
                'records_updated' => array_sum(array_column($summary, 'updated')),
                'records_skipped' => array_sum(array_column($summary, 'skipped')),
                'records_failed' => array_sum(array_column($summary, 'failed')),
            ];

            $log->update(array_merge($totals, [
                'status' => AaSyncLog::STATUS_SUCCESS,
                'summary' => $summary,
                'finished_at' => now(),
            ]));
        } catch (\Throwable $e) {
            $log->update([
                'status' => AaSyncLog::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'records_processed' => 0,
                'records_created' => 0,
                'records_updated' => 0,
                'records_skipped' => 0,
                'records_failed' => 0,
                'finished_at' => now(),
            ]);
        }

        return $log;
    }

    /**
     * Can the LMS reach the AA database right now?
     */
    public function available(): bool
    {
        return $this->connectionError() === null;
    }

    public function connectionError(): ?string
    {
        try {
            DB::connection(self::CONNECTION)->getPdo();

            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /* --------------------------------------------------------------------
     | Entity syncs — each returns per-entity counts.
     | -------------------------------------------------------------------- */

    protected function syncDepartments(array $map): array
    {
        return $this->upsertLookup($map, function (array $values) {
            $existing = Department::withTrashed()->where('code', $values['code'])->first();

            if ($existing) {
                $existing->fill($values);
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->save();

                return 'updated';
            }

            Department::create($values);

            return 'created';
        });
    }

    protected function syncPrograms(array $map): array
    {
        $departments = Department::pluck('id', 'code');

        return $this->upsertLookup($map, function (array $values, object $row) use ($departments, $map) {
            $departmentCode = $this->value($row, $map['columns']['department_code'] ?? null);

            if ($departmentCode !== null && ! $departments->has($departmentCode)) {
                return 'skipped'; // department not synced yet
            }

            $values['department_id'] = $departmentCode !== null ? $departments->get($departmentCode) : null;

            $existing = Program::withTrashed()->where('code', $values['code'])->first();

            if ($existing) {
                $existing->fill($values);
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->save();

                return 'updated';
            }

            Program::create($values);

            return 'created';
        });
    }

    protected function syncSections(array $map): array
    {
        $programs = Program::pluck('id', 'code');

        return $this->upsertLookup($map, function (array $values, object $row) use ($programs, $map) {
            $programCode = $this->value($row, $map['columns']['program_code'] ?? null);

            if ($programCode !== null && ! $programs->has($programCode)) {
                return 'skipped';
            }

            $values['program_id'] = $programCode !== null ? $programs->get($programCode) : null;

            $existing = Section::withTrashed()->where('code', $values['code'])->first();

            if ($existing) {
                $existing->fill($values);
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->save();

                return 'updated';
            }

            Section::create($values);

            return 'created';
        });
    }

    protected function syncStudents(array $map): array
    {
        $studentRoleId = Role::where('slug', Role::STUDENT)->value('id');
        $departments = Department::pluck('id', 'code');
        $programs = Program::pluck('id', 'code');
        $sections = Section::pluck('id', 'code');

        return $this->upsertUsers($map, $studentRoleId, [
            'departments' => $departments,
            'programs' => $programs,
            'sections' => $sections,
        ], 'default_student_password');
    }

    protected function syncFaculty(array $map): array
    {
        $instructorRoleId = Role::where('slug', Role::INSTRUCTOR)->value('id');

        return $this->upsertUsers($map, $instructorRoleId, [], 'default_faculty_password');
    }

    protected function syncClasses(array $map): array
    {
        $courses = Course::pluck('id', 'code');
        $facultyByEmail = User::whereHas('role', fn ($q) => $q->where('slug', Role::INSTRUCTOR))
            ->pluck('id', 'email');
        $periods = AcademicPeriod::pluck('id', 'code');
        $columns = $map['columns'] ?? [];

        $counts = $this->blankCounts();

        foreach ($this->aaRows($map['table']) as $row) {
            $counts['processed']++;

            try {
                $code = trim((string) $this->value($row, $columns['code'] ?? 'code'));
                $courseCode = $this->value($row, $columns['course_code'] ?? null);

                if ($code === '' || $courseCode === null || ! $courses->has($courseCode)) {
                    $counts['skipped']++; // no matching LMS course — course is managed by the Super Admin
                    $this->noteSkip($counts, $code ?: '(no class code)', 'Course not found in LMS: '.($courseCode ?? 'n/a'));
                    continue;
                }

                $instructorEmail = $this->value($row, $columns['instructor_email'] ?? null);

                if ($instructorEmail === null || ! $facultyByEmail->has($instructorEmail)) {
                    $counts['skipped']++;
                    $this->noteSkip($counts, $code, 'Instructor not found in LMS: '.($instructorEmail ?? 'n/a'));
                    continue;
                }

                $periodCode = $this->value($row, $columns['academic_period_code'] ?? null);

                $attributes = [
                    'course_id' => $courses->get($courseCode),
                    'instructor_id' => $facultyByEmail->get($instructorEmail),
                    'academic_period_id' => $periodCode !== null ? $periods->get($periodCode) : null,
                    'schedule' => $this->value($row, $columns['schedule'] ?? null),
                    'room' => $this->value($row, $columns['room'] ?? null),
                    'capacity' => $this->value($row, $columns['capacity'] ?? null),
                    'status' => $this->mapStatus($this->value($row, $columns['status'] ?? null), ['active' => 'active', 'inactive' => 'inactive'], 'active'),
                ];

                $existing = ClassModel::withTrashed()->where('code', $code)->first();

                if ($existing) {
                    $existing->fill($attributes);
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->save();
                    $counts['updated']++;
                } else {
                    ClassModel::create($attributes + ['code' => $code]);
                    $counts['created']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                $this->noteError($counts, $e);
            }
        }

        return $counts;
    }

    protected function syncEnrollments(array $map): array
    {
        $users = User::pluck('id', 'identifier');
        $classes = ClassModel::withTrashed()->pluck('id', 'code');
        $columns = $map['columns'] ?? [];

        $counts = $this->blankCounts();

        foreach ($this->aaRows($map['table']) as $row) {
            $counts['processed']++;

            try {
                $studentNo = $this->value($row, $columns['student_no'] ?? null);
                $classCode = $this->value($row, $columns['class_code'] ?? null);

                if ($studentNo === null || $classCode === null || ! $users->has($studentNo) || ! $classes->has($classCode)) {
                    $counts['skipped']++;
                    $this->noteSkip($counts, $studentNo.' → '.$classCode, 'Student or class not found in LMS');
                    continue;
                }

                $attributes = [
                    'student_id' => $users->get($studentNo),
                    'class_id' => $classes->get($classCode),
                    'status' => $this->mapStatus(
                        $this->value($row, $columns['status'] ?? null),
                        $map['status_map'] ?? [],
                        'active'
                    ),
                ];

                $enrolledAt = $this->value($row, $columns['enrolled_at'] ?? null);
                if ($enrolledAt !== null) {
                    $attributes['enrolled_at'] = $enrolledAt;
                }

                // Deliberately NOT syncing final_grade: ang official grade ay
                // hawak sa AA. The LMS only carries its own activity scores.
                $existing = Enrollment::where('student_id', $attributes['student_id'])
                    ->where('class_id', $attributes['class_id'])
                    ->first();

                if ($existing) {
                    $existing->fill($attributes);
                    $existing->save();
                    $counts['updated']++;
                } else {
                    Enrollment::create($attributes);
                    $counts['created']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                $this->noteError($counts, $e);
            }
        }

        return $counts;
    }

    /* --------------------------------------------------------------------
     | Shared helpers
     | -------------------------------------------------------------------- */

    /**
     * Generic upsert for simple lookup tables (departments, programs, sections).
     *
     * @param  callable(array, object): string  $apply  Returns 'created' or 'updated'; may throw.
     */
    protected function upsertLookup(array $map, callable $apply): array
    {
        $columns = $map['columns'] ?? [];
        $counts = $this->blankCounts();

        foreach ($this->aaRows($map['table']) as $row) {
            $counts['processed']++;

            try {
                $code = trim((string) $this->value($row, $columns['code'] ?? 'code'));

                if ($code === '') {
                    $counts['skipped']++;
                    continue;
                }

                $values = ['code' => $code];

                foreach (['name', 'description', 'max_students'] as $field) {
                    $value = $this->value($row, $columns[$field] ?? null);
                    if ($value !== null) {
                        $values[$field] = $value;
                    }
                }

                $result = $apply($values, $row);
                $counts[$result]++;
            } catch (\Throwable $e) {
                $counts['failed']++;
                $this->noteError($counts, $e);
            }
        }

        return $counts;
    }

    /**
     * Upsert students/faculty users keyed by email, with org fields resolved
     * from the code lookups passed in.
     *
     * @param  array<string, \Illuminate\Support\Collection>  $lookups
     */
    protected function upsertUsers(array $map, int $roleId, array $lookups, string $passwordConfigKey): array
    {
        $columns = $map['columns'] ?? [];
        $statusMap = $map['status_map'] ?? ['active' => 'active'];
        $skipEmails = config('aa_sync.skip_emails', []);
        $resetPasswords = (bool) config('aa_sync.reset_passwords', false);
        $defaultPassword = (string) config('aa_sync.'.$passwordConfigKey, 'Welcome@123');

        $counts = $this->blankCounts();

        foreach ($this->aaRows($map['table']) as $row) {
            $counts['processed']++;

            try {
                $email = strtolower(trim((string) $this->value($row, $columns['email'] ?? null)));

                if ($email === '' || in_array($email, $skipEmails, true)) {
                    $counts['skipped']++;
                    continue;
                }

                $first = trim((string) $this->value($row, $columns['first_name'] ?? null));
                $last = trim((string) $this->value($row, $columns['last_name'] ?? null));
                $identifier = $this->value($row, $columns['identifier'] ?? null);

                $values = [
                    'first_name' => $first !== '' ? $first : 'AA',
                    'last_name' => $last !== '' ? $last : 'User',
                    'role_id' => $roleId,
                    'status' => $this->mapStatus($this->value($row, $columns['status'] ?? null), $statusMap, 'active'),
                ];

                if ($identifier !== null) {
                    $values['identifier'] = $identifier;
                }

                foreach (['department' => 'departments', 'program' => 'programs', 'section' => 'sections'] as $field => $lookupKey) {
                    $code = $this->value($row, $columns[$field.'_code'] ?? null);
                    if ($code !== null && isset($lookups[$lookupKey]) && $lookups[$lookupKey]->has($code)) {
                        $values[$field.'_id'] = $lookups[$lookupKey]->get($code);
                    }
                }

                $user = User::withTrashed()->where('email', $email)->first();

                if ($user) {
                    $user->fill($values);
                    if ($user->trashed()) {
                        $user->restore();
                    }
                    if ($resetPasswords) {
                        $user->password = Hash::make($defaultPassword);
                    }
                    $user->save();
                    $counts['updated']++;
                } else {
                    User::create($values + [
                        'email' => $email,
                        'password' => $defaultPassword,
                    ]);
                    $counts['created']++;
                }
            } catch (\Throwable $e) {
                $counts['failed']++;
                $this->noteError($counts, $e);
            }
        }

        return $counts;
    }

    /**
     * @return array<int, object> Rows from the AA database (as stdClass objects).
     */
    protected function aaRows(string $table): array
    {
        if ($table === '') {
            return [];
        }

        return DB::connection(self::CONNECTION)->table($table)->get()->all();
    }

    /**
     * Read a mapped AA column from a row, null when absent.
     */
    protected function value(object $row, ?string $column): mixed
    {
        if ($column === null) {
            return null;
        }

        $value = data_get($row, $column);

        return $value === '' ? null : $value;
    }

    protected function mapStatus(mixed $status, array $map, string $default): string
    {
        $key = strtolower(trim((string) $status));

        return $map[$key] ?? $default;
    }

    protected function blankCounts(): array
    {
        return [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'skips' => [],
        ];
    }

    protected function noteError(array &$counts, \Throwable $e): void
    {
        if (count($counts['errors']) < 20) {
            $counts['errors'][] = $e->getMessage();
        }
    }

    protected function noteSkip(array &$counts, string $key, string $reason): void
    {
        if (count($counts['skips']) < 20) {
            $counts['skips'][$key] = $reason;
        }
    }
}

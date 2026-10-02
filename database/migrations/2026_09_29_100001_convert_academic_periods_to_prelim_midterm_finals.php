<?php

use App\Models\AcademicPeriod;
use App\Models\ClassModel;
use App\Models\Course;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Phase 1: Re-map real records to the Preliminary / Midterm / Finals terms.
        $map = [
            1 => ['name' => 'Preliminary', 'code' => 'PRELIM', 'start_date' => '2024-09-01', 'end_date' => '2024-12-20', 'description' => 'Preliminary grading period.'],
            2 => ['name' => 'Midterm', 'code' => 'MIDTERM', 'start_date' => '2025-01-15', 'end_date' => '2025-05-15', 'description' => 'Midterm grading period.'],
            3 => ['name' => 'Finals', 'code' => 'FINALS', 'start_date' => '2025-05-16', 'end_date' => '2025-08-15', 'description' => 'Final grading period.'],
        ];

        foreach ($map as $id => $attrs) {
            $period = AcademicPeriod::withTrashed()->find($id);
            if ($period) {
                $period->forceFill([
                    'name' => $attrs['name'],
                    'code' => $attrs['code'],
                    'start_date' => $attrs['start_date'],
                    'end_date' => $attrs['end_date'],
                    'description' => $attrs['description'],
                    'deleted_at' => null,
                ])->save();
            }
        }

        // Phase 2: Preliminary (id 1) is the period that owns all the real data -> current + enrollment open.
        AcademicPeriod::query()->update(['is_current' => false, 'is_enrollment_open' => false]);
        if ($prelim = AcademicPeriod::find(1)) {
            $prelim->update(['is_current' => true, 'is_enrollment_open' => true]);
        }

        // Phase 3: Remove junk test records (kept as soft deletes so nothing breaks).
        Course::withTrashed()->where('academic_period_id', 3)->get()
            ->each(fn (Course $c) => $c->delete());

        ClassModel::withTrashed()->where('academic_period_id', 4)->get()
            ->each(fn (ClassModel $c) => $c->delete());

        // Junk period 4 ("Summer 2015" test data) -> soft delete.
        if ($junk = AcademicPeriod::find(4)) {
            $junk->delete();
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive reverse; original names are documented here for manual restore:
        // 1 = Fall Semester 2024 (FALL-2024), 2 = Spring Semester 2025 (SPRING-2025),
        // 3 = Fall 1971 (DAYS2006), 4 = Summer 2015 (UKZS1996).
    }
};

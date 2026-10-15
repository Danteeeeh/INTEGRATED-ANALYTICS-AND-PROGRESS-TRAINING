<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Complete the registrar role retirement.
 *
 * The registrar role was already retired from the UI (routes/controllers/views
 * removed). This is the DATA side: existing registrar accounts are re-mapped to
 * the AA Staff / Academic Officer role — the closest business match (read-only
 * academic oversight) — and only then is the role row itself deleted.
 *
 * Safe order:
 *   1. Make sure the target role exists.
 *   2. Re-point every registrar user (nothing is deleted).
 *   3. Delete the now-unreferenced role row (users.role_id is the only FK).
 *
 * No academic records, enrollments, or grades are touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $registrarId = DB::table('roles')->where('slug', 'registrar')->value('id');

        if (! $registrarId) {
            return; // Nothing to migrate — fresh databases never had it.
        }

        // 1. Target role (matches RoleSeeder::AA_STAFF).
        $aaStaffId = DB::table('roles')->where('slug', 'aa_staff')->value('id')
            ?? DB::table('roles')->insertGetId([
                'name' => 'AA Staff',
                'slug' => 'aa_staff',
                'description' => 'Academic Officer — view-only access; official data and grades stay in AA',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 2. Re-map users (safe: no account is deleted or locked out).
        DB::table('users')->where('role_id', $registrarId)->update(['role_id' => $aaStaffId]);

        // 3. Remove the role itself — no users reference it anymore.
        DB::table('roles')->where('id', $registrarId)->delete();

        $this->removeEnrollmentWritePermissions();
    }

    /**
     * Enrollment is authoritative in AA, so the LMS keeps only
     * enrollments.view / enrollments.export. Detach and delete the stale
     * write permissions (create/update/delete/approve/reject/drop/transfer/bulk).
     */
    private function removeEnrollmentWritePermissions(): void
    {
        $stale = [
            'enrollments.create',
            'enrollments.update',
            'enrollments.delete',
            'enrollments.approve',
            'enrollments.reject',
            'enrollments.drop',
            'enrollments.transfer',
            'enrollments.bulk',
        ];

        $permissionIds = DB::table('permissions')->whereIn('name', $stale)->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
    }

    public function down(): void
    {
        // Restore the role row for rollback safety. Users that were re-mapped
        // stay on AA Staff (their accounts keep working); re-point them here
        // only if you specifically need the old assignment back.
        if (! DB::table('roles')->where('slug', 'registrar')->exists()) {
            DB::table('roles')->insert([
                'name' => 'Registrar',
                'slug' => 'registrar',
                'description' => 'Manages student records and enrollments',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};

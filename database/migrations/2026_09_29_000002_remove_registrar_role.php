<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('roles')->where('slug', 'admin')->value('id');
        $registrarId = DB::table('roles')->where('slug', 'registrar')->value('id');

        if (! $adminId || ! $registrarId) {
            return;
        }

        DB::table('users')->where('role_id', $registrarId)->update(['role_id' => $adminId]);
        DB::table('role_permissions')->where('role_id', $registrarId)->delete();
        DB::table('roles')->where('id', $registrarId)->delete();
    }

    public function down(): void
    {
        if (DB::table('roles')->where('slug', 'registrar')->exists()) {
            return;
        }

        DB::table('roles')->insert([
            'name' => 'Registrar',
            'slug' => 'registrar',
            'description' => 'Removed legacy role; user assignments are not restored automatically.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DB_CONNECTION=mariadb reports the driver name "mariadb", so a
        // `=== 'mysql'` test is false on the production database and the column
        // silently keeps its old enum, rejecting the "pending" status that
        // registration and the user API accept. Both names must be covered.
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'active'");

            return;
        }

        // SQLite enforces this enum through a CHECK constraint and PostgreSQL
        // through its own enum type, so both rebuild the column instead.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('active')->change();
        });
    }

    public function down(): void
    {
        if (in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active'");

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->change();
        });
    }
};

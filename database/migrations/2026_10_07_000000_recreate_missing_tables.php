<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-healing migration for tables that are recorded in the `migrations`
 * table as already-run but are physically absent from the database.
 *
 * This happens when a database is imported from a dump that skipped a few
 * tables, or when tables were dropped by hand. Because the original migration
 * rows already exist, `php artisan migrate` reports "Nothing to migrate" and
 * never recreates them.
 *
 * Every block is guarded by hasTable()/hasColumn(), so running this against a
 * healthy database is a no-op and it is safe on production.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createLessons();
        $this->createSessions();
        $this->createAcademicPeriods();
        $this->createActivityLogs();
        $this->createCacheTables();
    }

    public function down(): void
    {
        // Intentionally empty. Recreating a dropped table should not become a
        // destructive operation during a rollback.
    }

    private function createLessons(): void
    {
        if (Schema::hasTable('lessons')) {
            return;
        }

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->longText('content')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->enum('lesson_type', ['text', 'video', 'audio', 'pdf', 'document', 'presentation', 'external'])->default('text');
            $table->string('external_url')->nullable();
            $table->boolean('is_required')->default(true);
            $table->datetime('availability_from')->nullable();
            $table->datetime('availability_until')->nullable();
            $table->json('completion_rules')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['module_id', 'position']);
            $table->index('status');
        });
    }

    private function createSessions(): void
    {
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    private function createAcademicPeriods(): void
    {
        if (Schema::hasTable('academic_periods')) {
            return;
        }

        Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->boolean('is_enrollment_open')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_current', 'is_enrollment_open']);
        });
    }

    private function createActivityLogs(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * The cache pair, which .env requires on every request.
     *
     * CACHE_STORE=database means a missing `cache` table breaks the whole site
     * rather than one feature: cache:clear, rate limiting and anything taking a
     * lock all query it. This is the same failure a partial database import
     * produces, and the original migration row already exists, so `migrate`
     * alone would report "Nothing to migrate" and leave the site down.
     */
    private function createCacheTables(): void
    {
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }
    }
};
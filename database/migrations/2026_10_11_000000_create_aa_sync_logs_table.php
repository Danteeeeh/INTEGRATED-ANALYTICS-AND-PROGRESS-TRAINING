<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit trail for every "Sync from AA" run performed by the Super Admin.
     * One row per sync run (all entities), with per-entity counts stored as
     * JSON in the summary column.
     */
    public function up(): void
    {
        Schema::create('aa_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('entities')->nullable();          // which entities were requested
            $table->string('status')->default('running');  // running | success | failed
            $table->unsignedInteger('records_processed')->default(0);
            $table->unsignedInteger('records_created')->default(0);
            $table->unsignedInteger('records_updated')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->json('summary')->nullable();           // per-entity breakdown
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aa_sync_logs');
    }
};

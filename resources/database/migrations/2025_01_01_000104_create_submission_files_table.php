<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_submission_id')->constrained('assignment_submissions')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->unique(['assignment_submission_id', 'media_file_id'], 'submission_file_unique');
            $table->index('assignment_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};

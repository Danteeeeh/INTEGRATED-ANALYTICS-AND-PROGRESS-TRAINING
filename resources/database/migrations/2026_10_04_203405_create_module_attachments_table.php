<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->integer('position')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['module_id', 'position']);
            $table->unique(['module_id', 'media_file_id'], 'module_attachment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_attachments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained('assignments')->cascadeOnDelete();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->integer('position')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['assignment_id', 'position']);
            $table->unique(['assignment_id', 'media_file_id'], 'assignment_attachment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_attachments');
    }
};

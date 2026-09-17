<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('gradable_type');
            $table->unsignedBigInteger('gradable_id');
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->foreignId('attachment_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->boolean('is_private')->default(true);
            $table->timestamps();

            $table->index(['gradable_type', 'gradable_id']);
            $table->index('student_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};

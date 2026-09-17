<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_competency_id')->constrained('student_competencies')->cascadeOnDelete();
            $table->enum('evidence_type', ['grade', 'submission', 'quiz_attempt', 'activity', 'other'])->default('other');
            $table->string('evidence_ref_type')->nullable();
            $table->unsignedBigInteger('evidence_ref_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignId('attachment_media_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_evidence');
    }
};

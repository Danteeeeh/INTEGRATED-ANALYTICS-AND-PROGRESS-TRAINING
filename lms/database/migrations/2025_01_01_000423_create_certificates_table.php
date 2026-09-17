<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->string('certificate_number')->unique();
            $table->string('verification_code')->unique();
            $table->string('template_name')->nullable();
            $table->string('student_name_display');
            $table->string('course_name_display');
            $table->date('completion_date');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->enum('status', ['issued', 'revoked'])->default('issued');
            $table->timestamp('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'issued_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};

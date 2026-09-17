<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->text('award_reason')->nullable();
            $table->string('evidence_ref')->nullable();
            $table->timestamps();

            $table->unique(['badge_id', 'student_id'], 'badge_award_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_awards');
    }
};

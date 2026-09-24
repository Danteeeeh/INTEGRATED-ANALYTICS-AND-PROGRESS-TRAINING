<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_class_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_class_id')->constrained('virtual_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->enum('attendance_status', ['present', 'late', 'absent', 'excused'])->default('absent');
            $table->timestamps();

            $table->unique(['virtual_class_id', 'user_id'], 'virtual_class_attendee_unique');
            $table->index('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_class_attendees');
    }
};

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
        // Plural to match every other table and the model's default table name.
        Schema::create('terms_of_services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(false);
            $table->dateTime('effective_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_of_services');
    }
};

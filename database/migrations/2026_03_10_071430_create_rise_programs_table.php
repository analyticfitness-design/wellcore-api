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
        Schema::create('rise_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->default(30);
            $table->enum('experience_level', ['principiante', 'intermedio', 'avanzado'])->nullable();
            $table->enum('training_location', ['gym', 'home', 'hybrid'])->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->json('intake_data')->nullable();
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rise_programs');
    }
};

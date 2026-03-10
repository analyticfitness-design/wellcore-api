<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->integer('calories_target')->nullable();
            $table->integer('calories_actual')->nullable();
            $table->integer('protein_g')->nullable();
            $table->integer('carbs_g')->nullable();
            $table->integer('fat_g')->nullable();
            $table->tinyInteger('adherence_pct')->nullable(); // 0-100
            $table->string('meal_photo_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_logs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wellness_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->tinyInteger('energy_level')->nullable();    // 1-10
            $table->tinyInteger('stress_level')->nullable();    // 1-10
            $table->decimal('sleep_hours', 3, 1)->nullable();
            $table->tinyInteger('sleep_quality')->nullable();   // 1-10
            $table->tinyInteger('mood')->nullable();            // 1-10
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellness_logs');
    }
};

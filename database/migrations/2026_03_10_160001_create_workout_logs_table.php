<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exercise_name');
            $table->json('sets');
            $table->integer('total_sets')->default(0);
            $table->string('notes')->nullable();
            $table->date('logged_at');
            $table->timestamps();
            $table->index(['user_id', 'logged_at']);
            $table->index(['user_id', 'exercise_name']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('workout_logs');
    }
};

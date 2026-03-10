<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('exercise_name');
            $table->decimal('weight', 8, 2);
            $table->integer('reps');
            $table->date('achieved_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'exercise_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_records');
    }
};

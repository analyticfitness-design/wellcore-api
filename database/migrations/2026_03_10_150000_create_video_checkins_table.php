<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->enum('type', ['entrenamiento', 'nutricion', 'progreso', 'motivacional'])->default('entrenamiento');
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_checkins');
    }
};

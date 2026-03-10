<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exercise_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('youtube_url');
            $table->string('youtube_id', 20);
            $table->enum('gender', ['male', 'female', 'both'])->default('both');
            $table->string('category', 100)->nullable(); // chest, back, legs, etc.
            $table->string('muscle_group', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('category');
            $table->index('gender');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_videos');
    }
};

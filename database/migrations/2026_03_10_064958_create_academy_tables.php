<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academy_content', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('content_type', 50)->default('video'); // video, pdf, article
            $table->string('url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->enum('min_plan', ['esencial', 'metodo', 'elite', 'rise'])->default('esencial');
            $table->integer('duration_minutes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('academy_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_id')->constrained('academy_content')->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->integer('progress_pct')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_progress');
        Schema::dropIfExists('academy_content');
    }
};

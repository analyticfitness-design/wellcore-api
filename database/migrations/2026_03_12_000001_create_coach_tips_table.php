<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coach_tips', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['audio', 'video']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // nutrition, training, recovery, mindset
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['type', 'active', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_tips');
    }
};

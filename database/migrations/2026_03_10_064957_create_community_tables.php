<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->enum('post_type', ['text', 'workout', 'milestone'])->default('text');
            $table->enum('audience', ['all', 'rise'])->default('all');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->index(['audience', 'created_at']);
            $table->index('parent_id');
        });

        Schema::create('community_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('community_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('emoji', 10);
            $table->timestamps();
            $table->unique(['post_id', 'user_id', 'emoji']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reactions');
        Schema::dropIfExists('community_posts');
    }
};

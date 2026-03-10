<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('privacy', ['public', 'private'])->default('private');
            $table->unsignedTinyInteger('max_members')->default(8);
            $table->timestamps();
        });

        Schema::create('pod_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pod_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['pod_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pod_members');
        Schema::dropIfExists('pods');
    }
};

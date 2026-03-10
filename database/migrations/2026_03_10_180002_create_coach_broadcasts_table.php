<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coach_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->enum('target', ['all', 'esencial', 'metodo', 'elite'])->default('all');
            $table->timestamps();
            $table->index('coach_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coach_broadcasts');
    }
};

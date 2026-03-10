<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('week', 10);
            $table->date('checkin_date');
            $table->tinyInteger('bienestar')->nullable();
            $table->tinyInteger('dias_entrenados')->nullable();
            $table->enum('nutricion', ['Si', 'No', 'Parcial'])->nullable();
            $table->text('comentario')->nullable();
            $table->text('coach_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'checkin_date']);
            $table->index(['user_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkins');
    }
};

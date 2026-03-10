<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('edad')->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->string('objetivo')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('nivel', ['principiante', 'intermedio', 'avanzado'])->nullable();
            $table->enum('lugar_entreno', ['gym', 'home', 'hybrid'])->nullable();
            $table->json('dias_disponibles')->nullable();
            $table->text('restricciones')->nullable();
            $table->json('macros')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('dashboard_video_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_profiles');
    }
};

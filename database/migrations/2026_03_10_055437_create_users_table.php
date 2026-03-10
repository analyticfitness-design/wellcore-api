<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['client', 'coach', 'coach_external', 'admin', 'superadmin'])->default('client');
            $table->enum('plan', ['esencial', 'metodo', 'elite', 'rise'])->nullable();
            $table->enum('status', ['activo', 'inactivo', 'pendiente'])->default('activo');
            $table->string('client_code')->unique()->nullable();
            $table->unsignedBigInteger('coach_id')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->index(['role', 'status']);
            $table->index('coach_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

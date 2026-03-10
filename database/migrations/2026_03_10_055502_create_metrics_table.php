<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('porcentaje_grasa', 5, 2)->nullable();
            $table->decimal('porcentaje_musculo', 5, 2)->nullable();
            $table->decimal('pecho', 5, 2)->nullable();
            $table->decimal('cintura', 5, 2)->nullable();
            $table->decimal('cadera', 5, 2)->nullable();
            $table->decimal('muslo', 5, 2)->nullable();
            $table->decimal('brazo', 5, 2)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};

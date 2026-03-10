<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('photo_date');
            $table->enum('tipo', ['frente', 'lado', 'espalda']);
            $table->string('filename');
            $table->string('url');
            $table->timestamps();
            $table->index(['user_id', 'photo_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};

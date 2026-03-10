<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('client_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('entrenamiento')->nullable();
            $table->longText('nutricion')->nullable();
            $table->longText('habitos')->nullable();
            $table->longText('suplementacion')->nullable();
            $table->longText('ciclo')->nullable();
            $table->longText('bloodwork')->nullable();
            $table->string('version')->default('1.0');
            $table->timestamps();
            $table->unique('user_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('client_plans');
    }
};

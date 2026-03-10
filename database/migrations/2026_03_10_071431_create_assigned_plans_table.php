<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assigned_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('plan_type', ['entrenamiento', 'nutricion', 'habitos', 'suplementacion']);
            $table->longText('content');
            $table->unsignedTinyInteger('version')->default(1);
            $table->boolean('active')->default(true);
            $table->date('valid_from')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'plan_type', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assigned_plans');
    }
};

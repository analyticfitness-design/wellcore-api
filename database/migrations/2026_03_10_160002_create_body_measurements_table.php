<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('waist', 5, 2)->nullable();
            $table->decimal('hip', 5, 2)->nullable();
            $table->decimal('chest', 5, 2)->nullable();
            $table->decimal('arm', 5, 2)->nullable();
            $table->decimal('thigh', 5, 2)->nullable();
            $table->decimal('body_fat', 4, 2)->nullable();
            $table->date('logged_at');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'logged_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('body_measurements');
    }
};

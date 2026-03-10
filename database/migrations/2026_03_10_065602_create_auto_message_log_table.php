<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_message_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trigger_type', 50);
            $table->enum('channel', ['email', 'push', 'whatsapp'])->default('email');
            $table->date('date_sent');
            $table->timestamps();
            $table->unique(['user_id', 'trigger_type', 'date_sent'], 'unique_trigger_per_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_message_log');
    }
};

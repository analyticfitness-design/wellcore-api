<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            if (!Schema::hasColumn('checkins', 'rpe')) {
                $table->tinyInteger('rpe')->nullable()->after('dias_entrenados');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkins', function (Blueprint $table) {
            $table->dropColumn(['rpe']);
        });
    }
};

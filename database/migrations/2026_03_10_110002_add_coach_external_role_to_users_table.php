<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The 'coach_external' role was added to the users table CREATE statement.
     * This migration exists only to ALTER the column on MySQL production databases
     * that were created before 2026_03_10_110002 (i.e., without coach_external in
     * the original enum). SQLite test databases get the new enum from the create
     * table migration directly.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM('client','coach','coach_external','admin','superadmin') DEFAULT 'client'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE users MODIFY COLUMN role ENUM('client','coach','admin','superadmin') DEFAULT 'client'"
            );
        }
    }
};

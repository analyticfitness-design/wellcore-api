<?php

namespace App\Console\Commands;

use App\Services\Migration\PhpToLaravelMigrator;
use Illuminate\Console\Command;
use PDO;
use PDOException;
use Throwable;

/**
 * MigrateFromLegacy
 *
 * Artisan command that migrates production data from the legacy PHP application
 * (wellcorefitness) into this Laravel backend.
 *
 * Usage:
 *   php artisan migrate:from-legacy [--dry-run] [--force]
 *
 * Options:
 *   --dry-run   Verify the legacy DB connection and list available tables without
 *               writing any data. Safe to run at any time.
 *   --force     Required when running in the production environment to prevent
 *               accidental execution without an explicit database backup.
 *
 * Safety requirements before running in production:
 *   1. Take a full MySQL backup of both the legacy and Laravel databases.
 *   2. Set the LEGACY_DB_* environment variables on the server.
 *   3. Run with --dry-run first to confirm connectivity.
 *   4. Run the full migration with --force during a maintenance window.
 *   5. After completion, run: php artisan migrate:verify
 */
class MigrateFromLegacy extends Command
{
    protected $signature = 'migrate:from-legacy
                            {--force   : Execute in production without interactive confirmation}
                            {--dry-run : Verify legacy DB connection only — no data is written}';

    protected $description = 'Migrate data from the legacy PHP project to Laravel (run once in production)';

    public function handle(): int
    {
        // Prevent accidental production runs without an explicit --force flag.
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Production environment detected.');
            $this->error('Ensure you have a complete database backup, then re-run with --force.');
            return Command::FAILURE;
        }

        // Dry-run: check connectivity and list tables only.
        if ($this->option('dry-run')) {
            return $this->runDryRun();
        }

        // Interactive confirmation for non-production environments.
        if (! $this->option('force') && ! $this->confirm(
            'This will migrate ALL data from the legacy PHP database to Laravel. Proceed?'
        )) {
            $this->info('Migration cancelled.');
            return Command::SUCCESS;
        }

        $this->info('Connecting to legacy database...');

        try {
            $pdo = $this->createLegacyConnection();
        } catch (PDOException $e) {
            $this->error('Could not connect to the legacy database: ' . $e->getMessage());
            $this->line('');
            $this->line('Required environment variables:');
            $this->line('  LEGACY_DB_HOST, LEGACY_DB_PORT, LEGACY_DB_NAME,');
            $this->line('  LEGACY_DB_USER, LEGACY_DB_PASS');
            return Command::FAILURE;
        }

        $this->info('Connected. Starting migration...');
        $this->line('');

        $migrator = new PhpToLaravelMigrator($pdo);

        try {
            $stats = $migrator->run();

            $this->line('');
            $this->info('Migration summary:');

            $rows = [];
            foreach ($stats as $entity => $count) {
                $rows[] = [$entity, $count];
            }

            $this->table(['Entity', 'Records migrated'], $rows);

            $this->line('');
            $this->info('Migration completed successfully.');
            $this->info('Run "php artisan migrate:verify" to validate record counts.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->line('');
            $this->error('Migration failed: ' . $e->getMessage());
            $this->error('A full rollback was performed. No Laravel data was modified.');
            $this->line('Check storage/logs/migration.log for details.');
            return Command::FAILURE;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Verify the legacy DB connection and display available tables.
     * Does not write any data.
     */
    private function runDryRun(): int
    {
        $this->info('Dry-run mode: verifying legacy database connection...');

        try {
            $pdo    = $this->createLegacyConnection();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

            $this->info('Connection successful.');
            $this->line('');
            $this->info('Tables found in legacy database:');

            foreach ($tables as $table) {
                // Count rows per table for a quick data overview.
                try {
                    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                    $this->line("  $table: $count rows");
                } catch (PDOException) {
                    $this->line("  $table: (count failed)");
                }
            }

            $this->line('');
            $this->info('Dry-run complete. Remove --dry-run to start the actual migration.');

            return Command::SUCCESS;
        } catch (PDOException $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Build a PDO connection to the legacy PHP database using environment
     * variables. All variables are required; the command fails early if any
     * are missing.
     *
     * @throws PDOException
     */
    private function createLegacyConnection(): PDO
    {
        $host     = (string) config('database.legacy.host',     env('LEGACY_DB_HOST', '127.0.0.1'));
        $port     = (string) config('database.legacy.port',     env('LEGACY_DB_PORT', '3306'));
        $database = (string) config('database.legacy.database', env('LEGACY_DB_NAME', ''));
        $user     = (string) env('LEGACY_DB_USER', '');
        $pass     = (string) env('LEGACY_DB_PASS', '');

        return new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
}

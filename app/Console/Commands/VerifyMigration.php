<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

/**
 * VerifyMigration
 *
 * Artisan command that compares record counts between the legacy PHP database
 * and the Laravel database to verify the migration completed without data loss.
 *
 * Usage:
 *   php artisan migrate:verify
 *
 * When the legacy DB environment variables are not set (e.g. after the legacy
 * instance has been decommissioned), the command falls back to displaying only
 * the current Laravel record counts.
 *
 * Exit codes:
 *   0 (SUCCESS) — all legacy/Laravel counts match, or legacy DB is unreachable.
 *   1 (FAILURE) — one or more count mismatches detected.
 */
class VerifyMigration extends Command
{
    protected $signature   = 'migrate:verify';
    protected $description = 'Verify that record counts between the legacy PHP DB and Laravel match';

    /**
     * Entity verification definitions.
     * Each entry declares:
     *   label   — human-readable name shown in the output table
     *   legacy  — SQL query against the legacy database
     *   laravel — SQL query against the Laravel database
     *
     * @var array<int, array{label: string, legacy: string, laravel: string}>
     */
    private array $checks = [
        [
            'label'   => 'Clients',
            'legacy'  => "SELECT COUNT(*) FROM clients",
            'laravel' => "SELECT COUNT(*) FROM users WHERE role='client'",
        ],
        [
            'label'   => 'Coaches',
            'legacy'  => "SELECT COUNT(*) FROM admins WHERE role IN ('coach','coaches','coach_manager')",
            'laravel' => "SELECT COUNT(*) FROM users WHERE role='coach'",
        ],
        [
            'label'   => 'Admins',
            'legacy'  => "SELECT COUNT(*) FROM admins WHERE role IN ('admin','superadmin','jefe')",
            'laravel' => "SELECT COUNT(*) FROM users WHERE role IN ('admin','superadmin')",
        ],
        [
            'label'   => 'Client profiles',
            'legacy'  => "SELECT COUNT(*) FROM client_profiles",
            'laravel' => "SELECT COUNT(*) FROM client_profiles",
        ],
        [
            'label'   => 'Check-ins',
            'legacy'  => "SELECT COUNT(*) FROM checkins",
            'laravel' => "SELECT COUNT(*) FROM checkins",
        ],
        [
            'label'   => 'Metrics',
            'legacy'  => "SELECT COUNT(*) FROM metrics",
            'laravel' => "SELECT COUNT(*) FROM metrics",
        ],
        [
            'label'   => 'XP records',
            'legacy'  => "SELECT COUNT(*) FROM client_xp",
            'laravel' => "SELECT COUNT(*) FROM client_xp",
        ],
        [
            'label'   => 'Payments',
            'legacy'  => "SELECT COUNT(*) FROM payments WHERE client_id IS NOT NULL",
            'laravel' => "SELECT COUNT(*) FROM payments",
        ],
    ];

    public function handle(): int
    {
        $this->info('Verifying migration integrity...');
        $this->newLine();

        // Attempt legacy DB connection.
        try {
            $pdo = $this->createLegacyConnection();
        } catch (PDOException $e) {
            $this->warn('Could not connect to legacy database: ' . $e->getMessage());
            $this->warn('Showing Laravel-only record counts.');
            $this->newLine();
            $this->showLaravelStats();
            return Command::SUCCESS;
        }

        $headers  = ['Entity', 'Legacy (PHP)', 'Laravel', 'Status'];
        $rows     = [];
        $allMatch = true;

        foreach ($this->checks as $check) {
            try {
                $legacyCount  = (int) $pdo->query($check['legacy'])->fetchColumn();
                $laravelCount = (int) DB::selectOne(
                    "SELECT ({$check['laravel']}) AS c"
                )->c;

                $match = ($legacyCount === $laravelCount);

                if (! $match) {
                    $allMatch = false;
                }

                $rows[] = [
                    $check['label'],
                    number_format($legacyCount),
                    number_format($laravelCount),
                    $match ? 'OK' : 'MISMATCH',
                ];
            } catch (PDOException $e) {
                // Table may not exist in the legacy DB (e.g. client_xp before v9).
                $rows[]   = [$check['label'], 'N/A', 'N/A', 'SKIP: ' . $e->getMessage()];
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($allMatch) {
            $this->info('All counts match — migration verified.');
        } else {
            $this->warn('Count mismatches detected. Review storage/logs/migration.log for details.');
        }

        return $allMatch ? Command::SUCCESS : Command::FAILURE;
    }

    // ─────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Display current record counts from the Laravel database only.
     * Used as fallback when the legacy DB is unreachable.
     */
    private function showLaravelStats(): void
    {
        $stats = [
            'Clients (users)'  => DB::table('users')->where('role', 'client')->count(),
            'Coaches (users)'  => DB::table('users')->where('role', 'coach')->count(),
            'Admins (users)'   => DB::table('users')->whereIn('role', ['admin', 'superadmin'])->count(),
            'Client profiles'  => DB::table('client_profiles')->count(),
            'Check-ins'        => DB::table('checkins')->count(),
            'Metrics'          => DB::table('metrics')->count(),
            'XP records'       => DB::table('client_xp')->count(),
            'Payments'         => DB::table('payments')->count(),
        ];

        $rows = [];

        foreach ($stats as $label => $count) {
            $rows[] = [$label, number_format($count)];
        }

        $this->table(['Entity', 'Laravel count'], $rows);
    }

    /**
     * Build a PDO connection to the legacy PHP database.
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
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}

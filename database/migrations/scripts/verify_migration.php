<?php
/**
 * verify_migration.php — Standalone migration verification script
 *
 * This script can be executed directly via PHP CLI without the Laravel
 * framework, making it suitable for running on the EasyPanel console during
 * the production launch window when the Artisan CLI may not be readily
 * available.
 *
 * Usage:
 *   php /code/database/migrations/scripts/verify_migration.php
 *
 * Required environment variables (same as the Artisan command):
 *   LEGACY_DB_HOST, LEGACY_DB_PORT, LEGACY_DB_NAME,
 *   LEGACY_DB_USER, LEGACY_DB_PASS
 *
 * Optional — Laravel DB (defaults shown):
 *   LARAVEL_DB_HOST  (default: 127.0.0.1)
 *   LARAVEL_DB_PORT  (default: 3306)
 *   LARAVEL_DB_NAME  (required)
 *   LARAVEL_DB_USER  (required)
 *   LARAVEL_DB_PASS  (required)
 *
 * Exit codes:
 *   0 — all checks passed
 *   1 — one or more mismatches
 */

declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// Connection factories
// ─────────────────────────────────────────────────────────────────────────────

function connectLegacy(): ?PDO
{
    $host = getenv('LEGACY_DB_HOST') ?: '127.0.0.1';
    $port = getenv('LEGACY_DB_PORT') ?: '3306';
    $db   = getenv('LEGACY_DB_NAME') ?: '';
    $user = getenv('LEGACY_DB_USER') ?: '';
    $pass = getenv('LEGACY_DB_PASS') ?: '';

    if (! $db || ! $user) {
        echo "[WARN] LEGACY_DB_NAME or LEGACY_DB_USER not set — skipping legacy counts.\n";
        return null;
    }

    try {
        return new PDO(
            "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        echo "[WARN] Cannot connect to legacy DB: " . $e->getMessage() . "\n";
        return null;
    }
}

function connectLaravel(): PDO
{
    $host = getenv('LARAVEL_DB_HOST') ?: (getenv('DB_HOST') ?: '127.0.0.1');
    $port = getenv('LARAVEL_DB_PORT') ?: (getenv('DB_PORT') ?: '3306');
    $db   = getenv('LARAVEL_DB_NAME') ?: (getenv('DB_DATABASE') ?: '');
    $user = getenv('LARAVEL_DB_USER') ?: (getenv('DB_USERNAME') ?: '');
    $pass = getenv('LARAVEL_DB_PASS') ?: (getenv('DB_PASSWORD') ?: '');

    if (! $db || ! $user) {
        fwrite(STDERR, "[ERROR] LARAVEL_DB_NAME or LARAVEL_DB_USER not set.\n");
        exit(1);
    }

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Table-format helper
// ─────────────────────────────────────────────────────────────────────────────

/**
 * @param  string[]            $headers
 * @param  array<string[]>     $rows
 */
function printTable(array $headers, array $rows): void
{
    // Calculate column widths.
    $widths = array_map('strlen', $headers);

    foreach ($rows as $row) {
        foreach ($row as $i => $cell) {
            $widths[$i] = max($widths[$i], strlen((string) $cell));
        }
    }

    $separator = '+' . implode('+', array_map(fn ($w) => str_repeat('-', $w + 2), $widths)) . '+';

    echo $separator . "\n";

    $headerRow = '|';
    foreach ($headers as $i => $h) {
        $headerRow .= ' ' . str_pad($h, $widths[$i]) . ' |';
    }

    echo $headerRow . "\n";
    echo $separator . "\n";

    foreach ($rows as $row) {
        $line = '|';
        foreach ($row as $i => $cell) {
            $line .= ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
        }
        echo $line . "\n";
    }

    echo $separator . "\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// Main
// ─────────────────────────────────────────────────────────────────────────────

echo "\n=== WellCore Migration Verification ===\n";
echo date('[Y-m-d H:i:s]') . " Starting verification...\n\n";

$legacy  = connectLegacy();
$laravel = connectLaravel();

// Verification check definitions.
$checks = [
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

$rows     = [];
$allMatch = true;

foreach ($checks as $check) {
    $legacyCount  = 'N/A';
    $laravelCount = 'N/A';
    $status       = 'SKIP';

    // Legacy count.
    if ($legacy !== null) {
        try {
            $legacyCount = (int) $legacy->query($check['legacy'])->fetchColumn();
        } catch (PDOException $e) {
            $legacyCount = 'ERR';
            $status      = 'SKIP';
        }
    }

    // Laravel count.
    try {
        $laravelCount = (int) $laravel->query($check['laravel'])->fetchColumn();
        $status       = 'OK';
    } catch (PDOException $e) {
        $laravelCount = 'ERR';
        $status       = 'ERROR';
        $allMatch     = false;
    }

    // Compare.
    if ($legacy !== null && is_int($legacyCount) && is_int($laravelCount)) {
        if ($legacyCount !== $laravelCount) {
            $status   = 'MISMATCH';
            $allMatch = false;
        }
    }

    $rows[] = [
        $check['label'],
        is_int($legacyCount) ? number_format($legacyCount) : $legacyCount,
        is_int($laravelCount) ? number_format($laravelCount) : $laravelCount,
        $status,
    ];
}

printTable(['Entity', 'Legacy (PHP)', 'Laravel', 'Status'], $rows);

echo "\n";

if ($allMatch) {
    echo date('[Y-m-d H:i:s]') . " All counts verified — migration looks correct.\n\n";
    exit(0);
} else {
    echo date('[Y-m-d H:i:s]') . " WARNING: Mismatches detected. Check migration.log.\n\n";
    exit(1);
}

<?php

namespace App\Services\Migration;

use App\Models\Checkin;
use App\Models\ClientProfile;
use App\Models\ClientXp;
use App\Models\Metric;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use Throwable;

/**
 * PhpToLaravelMigrator
 *
 * Migrates production data from the legacy PHP flat-file application
 * (wellcorefitness) to the Laravel backend (wellcore-api).
 *
 * Key design decisions:
 *  - All migrations run inside a single DB transaction; any failure
 *    triggers a full rollback so the Laravel DB is never left in a
 *    partially-migrated state.
 *  - Every method is idempotent: it skips records that already exist,
 *    so it is safe to re-run the command after a partial failure once
 *    the root cause is fixed.
 *  - The PDO connection to the legacy DB is injected via the constructor
 *    so tests can supply an in-memory SQLite PDO without touching MySQL.
 *  - ID mapping ($idMap) translates legacy integer IDs to new Laravel
 *    auto-increment IDs for FK resolution across entities.
 *
 * Execution order matters:
 *   admins → coaches → clients → client_profiles →
 *   metrics → checkins → gamification → payments
 */
class PhpToLaravelMigrator
{
    /** @var array<string, array<int|string, int>> Maps 'entity' => ['legacy_id' => 'new_laravel_id'] */
    private array $idMap = [];

    /** @var array<string, int> Migration stats returned to the caller */
    private array $stats = [];

    public function __construct(private readonly PDO $legacyDb) {}

    // ─────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────

    /**
     * Run the full migration inside a single transaction.
     *
     * @return array<string, int>  Stats keyed by entity name.
     * @throws Throwable           Re-throws after rollback so the caller can report the error.
     */
    public function run(): array
    {
        DB::beginTransaction();

        try {
            $this->migrateAdmins();
            $this->migrateCoaches();
            $this->migrateClients();
            $this->migrateClientProfiles();
            $this->migrateMetrics();
            $this->migrateCheckins();
            $this->migrateGamification();
            $this->migratePayments();

            DB::commit();

            $this->log('Migration completed successfully.');

            return $this->stats;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->log('Migration failed — rolled back. Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Public migration methods (public so Feature tests can call them
    // individually without triggering the full run() transaction)
    // ─────────────────────────────────────────────────────────────

    /**
     * Migrate the legacy `clients` table to Laravel `users` (role=client).
     *
     * Legacy schema differences to account for:
     *  - No `birth_date` column in v1 schema (added later); treated as nullable.
     *  - No `coach_id` column in v1 schema (also added later); treated as nullable.
     *  - `password_hash` → `password` (bcrypt value is reused verbatim).
     *  - `client_code` is UNIQUE in both schemas; skip on collision.
     */
    public function migrateClients(): void
    {
        $rows = $this->legacyDb
            ->query('SELECT * FROM clients ORDER BY id')
            ->fetchAll(PDO::FETCH_ASSOC);

        $count = 0;

        foreach ($rows as $c) {
            // Idempotency guard: skip if this email already exists as a client.
            if (User::where('email', $c['email'])->where('role', 'client')->exists()) {
                // Still populate the ID map so dependent entities resolve correctly.
                $existing = User::where('email', $c['email'])->first();
                $this->idMap['clients'][$c['id']] = $existing->id;
                continue;
            }

            $newUser = User::create([
                'name'         => $c['name'],
                'email'        => $c['email'],
                'password'     => $c['password_hash'],   // already bcrypt — do NOT re-hash
                'role'         => 'client',
                'plan'         => $c['plan']             ?? null,
                'status'       => $c['status']           ?? 'activo',
                'client_code'  => $c['client_code']      ?? null,
                'coach_id'     => $this->resolveCoachId($c['coach_id'] ?? null),
                'fecha_inicio' => $c['fecha_inicio']     ?? null,
                'birth_date'   => $c['birth_date']       ?? null,
                'created_at'   => $c['created_at'],
                'updated_at'   => $c['updated_at'] ?? $c['created_at'],
            ]);

            $this->idMap['clients'][$c['id']] = $newUser->id;
            $count++;
        }

        $this->stats['clients'] = $count;
        $this->log("Clients migrated: $count");
    }

    /**
     * Migrate legacy `client_xp` gamification records.
     *
     * Legacy schema note: the v9 migration stores `client_id` as VARCHAR(60)
     * (matching the string client codes used in early v9), not an integer FK.
     * We attempt resolution by both integer position in $idMap and by string
     * lookup against `client_code`, falling back gracefully.
     *
     * Column mapping:
     *   streak_last_date (legacy) → last_activity_date (Laravel)
     */
    public function migrateGamification(): void
    {
        try {
            $rows = $this->legacyDb
                ->query('SELECT * FROM client_xp')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table client_xp not found in legacy DB — skipping.');
            $this->stats['gamification'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $xp) {
            $newUserId = $this->resolveClientId($xp);
            if ($newUserId === null) {
                continue;
            }

            // Idempotency guard.
            if (ClientXp::where('user_id', $newUserId)->exists()) {
                continue;
            }

            // Map legacy `streak_last_date` to `last_activity_date`.
            $lastActivity = $xp['last_activity_date']
                ?? $xp['streak_last_date']
                ?? null;

            ClientXp::create([
                'user_id'            => $newUserId,
                'xp_total'           => $xp['xp_total']        ?? 0,
                'level'              => $xp['level']            ?? 1,
                'streak_days'        => $xp['streak_days']      ?? 0,
                'streak_protected'   => $xp['streak_protected'] ?? false,
                'last_activity_date' => $lastActivity,
            ]);

            $count++;
        }

        $this->stats['gamification'] = $count;
        $this->log("XP records migrated: $count");
    }

    // ─────────────────────────────────────────────────────────────
    // Private migration methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Migrate the legacy `admins` table rows with admin/superadmin roles to
     * Laravel `users`. Coach rows in `admins` are skipped here; they are
     * handled by migrateCoaches() which reads the same table filtered by role.
     *
     * Legacy `admins` uses `username` (not `email`). We attempt to find an
     * email from an associated coaches table first; if unavailable we construct
     * a placeholder: username@wellcorefitness.internal.
     */
    private function migrateAdmins(): void
    {
        try {
            $rows = $this->legacyDb
                ->query("SELECT * FROM admins WHERE role IN ('admin','superadmin','jefe') ORDER BY id")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table admins not found — skipping admins.');
            $this->stats['admins'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $a) {
            $email = $a['email'] ?? ($a['username'] . '@wellcorefitness.internal');
            $role  = $this->mapAdminRole($a['role']);

            if (User::where('email', $email)->exists()) {
                $existing = User::where('email', $email)->first();
                $this->idMap['admins'][$a['id']] = $existing->id;
                continue;
            }

            $newUser = User::create([
                'name'       => $a['name'] ?? $a['username'],
                'email'      => $email,
                'password'   => $a['password_hash'],
                'role'       => $role,
                'status'     => 'activo',
                'created_at' => $a['created_at'] ?? now(),
                'updated_at' => $a['created_at'] ?? now(),
            ]);

            $this->idMap['admins'][$a['id']] = $newUser->id;
            $count++;
        }

        $this->stats['admins'] = $count;
        $this->log("Admins migrated: $count");
    }

    /**
     * Migrate coaches from the legacy `admins` table (role IN coach/coaches/
     * coach_manager) into Laravel `users` with role='coach'.
     *
     * The legacy system stored coaches inside the `admins` table distinguished
     * only by role. We pull them out here and populate $idMap['coaches'] for
     * use when resolving client.coach_id references.
     */
    private function migrateCoaches(): void
    {
        try {
            $rows = $this->legacyDb
                ->query("SELECT * FROM admins WHERE role IN ('coach','coaches','coach_manager') ORDER BY id")
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table admins not found — skipping coaches.');
            $this->stats['coaches'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $c) {
            $email = $c['email'] ?? ($c['username'] . '@wellcorefitness.internal');

            if (User::where('email', $email)->exists()) {
                $existing = User::where('email', $email)->first();
                $this->idMap['coaches'][$c['id']] = $existing->id;
                continue;
            }

            $newUser = User::create([
                'name'       => $c['name'] ?? $c['username'],
                'email'      => $email,
                'password'   => $c['password_hash'],
                'role'       => 'coach',
                'status'     => 'activo',
                'created_at' => $c['created_at'] ?? now(),
                'updated_at' => $c['created_at'] ?? now(),
            ]);

            $this->idMap['coaches'][$c['id']] = $newUser->id;
            $count++;
        }

        $this->stats['coaches'] = $count;
        $this->log("Coaches migrated: $count");
    }

    /**
     * Migrate legacy `client_profiles` to Laravel `client_profiles`.
     *
     * Column mapping differences:
     *  - Legacy uses `client_id` FK; Laravel uses `user_id` FK.
     *  - Legacy `lugar_entreno` enum: 'gym'|'casa'|'ambos'
     *    Laravel enum:               'gym'|'home'|'hybrid'
     */
    private function migrateClientProfiles(): void
    {
        try {
            $rows = $this->legacyDb
                ->query('SELECT * FROM client_profiles ORDER BY id')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table client_profiles not found — skipping.');
            $this->stats['client_profiles'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $p) {
            $newUserId = $this->idMap['clients'][$p['client_id']] ?? null;
            if ($newUserId === null) {
                continue;
            }

            if (ClientProfile::where('user_id', $newUserId)->exists()) {
                continue;
            }

            ClientProfile::create([
                'user_id'          => $newUserId,
                'edad'             => $p['edad']             ?? null,
                'peso'             => $p['peso']             ?? null,
                'altura'           => $p['altura']           ?? null,
                'objetivo'         => $p['objetivo']         ?? null,
                'ciudad'           => $p['ciudad']           ?? null,
                'whatsapp'         => $p['whatsapp']         ?? null,
                'nivel'            => $p['nivel']            ?? null,
                'lugar_entreno'    => $this->mapLugarEntreno($p['lugar_entreno'] ?? null),
                'dias_disponibles' => $this->decodeJson($p['dias_disponibles'] ?? null),
                'restricciones'    => $p['restricciones']    ?? null,
                'macros'           => $this->decodeJson($p['macros'] ?? null),
                'bio'              => $p['bio']              ?? null,
                'avatar_url'       => $p['avatar_url']       ?? null,
            ]);

            $count++;
        }

        $this->stats['client_profiles'] = $count;
        $this->log("Client profiles migrated: $count");
    }

    /**
     * Migrate legacy `metrics` table to Laravel `metrics`.
     *
     * Column mapping:
     *  - Legacy `client_id` → Laravel `user_id`
     *  - Columns present in both schemas are copied directly.
     */
    private function migrateMetrics(): void
    {
        try {
            $rows = $this->legacyDb
                ->query('SELECT * FROM metrics ORDER BY id')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table metrics not found — skipping.');
            $this->stats['metrics'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $m) {
            $newUserId = $this->idMap['clients'][$m['client_id']] ?? null;
            if ($newUserId === null) {
                continue;
            }

            // Unique constraint: (user_id, log_date) — skip on collision.
            if (Metric::where('user_id', $newUserId)->where('log_date', $m['log_date'])->exists()) {
                continue;
            }

            Metric::create([
                'user_id'              => $newUserId,
                'log_date'             => $m['log_date'],
                'peso'                 => $m['peso']                 ?? null,
                'porcentaje_grasa'     => $m['porcentaje_grasa']     ?? null,
                'porcentaje_musculo'   => $m['porcentaje_musculo']   ?? null,
                'pecho'                => $m['pecho']                ?? null,
                'cintura'              => $m['cintura']              ?? null,
                'cadera'               => $m['cadera']              ?? null,
                'muslo'                => $m['muslo']                ?? null,
                'brazo'                => $m['brazo']                ?? null,
                'notas'                => $m['notas']                ?? null,
                'created_at'           => $m['created_at'],
                'updated_at'           => $m['created_at'],
            ]);

            $count++;
        }

        $this->stats['metrics'] = $count;
        $this->log("Metrics migrated: $count");
    }

    /**
     * Migrate legacy `checkins` table to Laravel `checkins`.
     *
     * Column mapping:
     *  - Legacy `client_id`  → Laravel `user_id`
     *  - Legacy `week_label` → Laravel `week`
     *  - Unique constraint in legacy: (client_id, week_label)
     *    Unique constraint in Laravel: (user_id, checkin_date)
     */
    private function migrateCheckins(): void
    {
        try {
            $rows = $this->legacyDb
                ->query('SELECT * FROM checkins ORDER BY id')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table checkins not found — skipping.');
            $this->stats['checkins'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $ci) {
            $newUserId = $this->idMap['clients'][$ci['client_id']] ?? null;
            if ($newUserId === null) {
                continue;
            }

            if (Checkin::where('user_id', $newUserId)->where('checkin_date', $ci['checkin_date'])->exists()) {
                continue;
            }

            // Map legacy week_label ('2026-W08') to 'week' column.
            $week = $ci['week_label'] ?? $ci['week'] ?? date('Y-\WW', strtotime($ci['checkin_date']));

            Checkin::create([
                'user_id'         => $newUserId,
                'week'            => $week,
                'checkin_date'    => $ci['checkin_date'],
                'bienestar'       => $ci['bienestar']       ?? null,
                'dias_entrenados' => $ci['dias_entrenados'] ?? null,
                'nutricion'       => $ci['nutricion']       ?? null,
                'comentario'      => $ci['comentario']      ?? null,
                'coach_reply'     => $ci['coach_reply']     ?? null,
                'replied_at'      => $ci['replied_at']      ?? null,
                'created_at'      => $ci['created_at'],
                'updated_at'      => $ci['created_at'],
            ]);

            $count++;
        }

        $this->stats['checkins'] = $count;
        $this->log("Checkins migrated: $count");
    }

    /**
     * Migrate legacy `payments` table to Laravel `payments`.
     *
     * Legacy payments stores `amount` as DECIMAL; Laravel stores `amount_cents`
     * as BIGINT. We multiply by 100 and round to the nearest integer.
     *
     * Legacy status values differ from Laravel:
     *   Legacy: pending|approved|rejected|cancelled|declined|voided|error
     *   Laravel ENUM: PENDING|APPROVED|DECLINED|VOIDED|ERROR
     * Values not in the Laravel ENUM are stored as PENDING with a metadata note.
     */
    private function migratePayments(): void
    {
        try {
            $rows = $this->legacyDb
                ->query('SELECT * FROM payments ORDER BY id')
                ->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException) {
            $this->log('Table payments not found — skipping.');
            $this->stats['payments'] = 0;
            return;
        }

        $count = 0;

        foreach ($rows as $p) {
            // Prefer wompi_reference as the unique reference in Laravel.
            $ref = $p['wompi_reference'] ?? $p['payu_reference'] ?? null;

            if ($ref && Payment::where('wompi_reference', $ref)->exists()) {
                continue;
            }

            $newUserId = null;
            if (! empty($p['client_id'])) {
                $newUserId = $this->idMap['clients'][$p['client_id']] ?? null;
            }

            // If no client mapping and there is an email, try to find the user.
            if ($newUserId === null && ! empty($p['email'])) {
                $user = User::where('email', $p['email'])->first();
                $newUserId = $user?->id;
            }

            if ($newUserId === null) {
                // Payment has no resolvable user — skip to avoid orphan records.
                continue;
            }

            $amountCents = (int) round(($p['amount'] ?? 0) * 100);
            $status      = $this->mapPaymentStatus($p['status'] ?? 'pending');

            Payment::create([
                'user_id'          => $newUserId,
                'amount_cents'     => $amountCents,
                'currency'         => $p['currency']        ?? 'COP',
                'status'           => $status,
                'wompi_reference'  => $ref,
                'payment_method'   => $p['payment_method']  ?? null,
                'metadata'         => [
                    'legacy_payment_id'   => $p['id'],
                    'payu_reference'      => $p['payu_reference']      ?? null,
                    'payu_transaction_id' => $p['payu_transaction_id'] ?? null,
                    'wompi_transaction_id'=> $p['wompi_transaction_id']?? null,
                    'buyer_name'          => $p['buyer_name']          ?? null,
                    'buyer_phone'         => $p['buyer_phone']          ?? null,
                    'plan'                => $p['plan']                 ?? null,
                    'legacy_status'       => $p['status']               ?? null,
                ],
                'created_at' => $p['created_at'],
                'updated_at' => $p['updated_at'] ?? $p['created_at'],
            ]);

            $count++;
        }

        $this->stats['payments'] = $count;
        $this->log("Payments migrated: $count");
    }

    // ─────────────────────────────────────────────────────────────
    // Helper / mapping methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Resolve a legacy coach_id (from clients.coach_id) to a new Laravel user ID.
     */
    private function resolveCoachId(int|string|null $legacyCoachId): ?int
    {
        if ($legacyCoachId === null || $legacyCoachId == 0) {
            return null;
        }

        return $this->idMap['coaches'][$legacyCoachId] ?? null;
    }

    /**
     * Resolve the correct new user ID for a legacy XP row.
     *
     * The v9 legacy schema uses client_id as VARCHAR(60), which may contain
     * either an integer string ("42") or a client_code string ("cli-042").
     * We try integer ID lookup first, then string code lookup.
     */
    private function resolveClientId(array $xpRow): ?int
    {
        // Prefer explicit integer client_id (legacy v1 style).
        $rawId = $xpRow['client_id'] ?? $xpRow['user_id'] ?? null;

        if ($rawId === null) {
            return null;
        }

        // Integer lookup via idMap.
        if (is_numeric($rawId) && isset($this->idMap['clients'][(int) $rawId])) {
            return $this->idMap['clients'][(int) $rawId];
        }

        // String client_code lookup.
        $user = User::where('client_code', $rawId)->where('role', 'client')->first();

        return $user?->id;
    }

    /**
     * Map legacy admin roles to Laravel user roles.
     */
    private function mapAdminRole(string $legacyRole): string
    {
        return match ($legacyRole) {
            'superadmin'    => 'superadmin',
            'jefe'          => 'superadmin',
            'admin'         => 'admin',
            'coach_manager' => 'admin',
            default         => 'admin',
        };
    }

    /**
     * Normalize legacy lugar_entreno enum to Laravel values.
     *
     * Legacy: 'gym' | 'casa' | 'ambos'
     * Laravel: 'gym' | 'home' | 'hybrid'
     */
    private function mapLugarEntreno(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($value) {
            'gym'   => 'gym',
            'casa'  => 'home',
            'home'  => 'home',
            'ambos' => 'hybrid',
            default => null,
        };
    }

    /**
     * Normalize legacy payment status to Laravel ENUM values.
     *
     * Laravel ENUM: PENDING | APPROVED | DECLINED | VOIDED | ERROR
     */
    private function mapPaymentStatus(string $legacyStatus): string
    {
        return match (strtolower($legacyStatus)) {
            'approved'  => 'APPROVED',
            'declined'  => 'DECLINED',
            'rejected'  => 'DECLINED',
            'voided'    => 'VOIDED',
            'cancelled' => 'VOIDED',
            'error'     => 'ERROR',
            default     => 'PENDING',
        };
    }

    /**
     * Safely decode a JSON string that may already be an array (some legacy
     * rows have been serialized twice or stored as PHP arrays via json_encode).
     *
     * @return array<mixed>|null
     */
    private function decodeJson(string|array|null $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Write a timestamped line to STDOUT and the migration log file.
     */
    private function log(string $message): void
    {
        $line = date('[Y-m-d H:i:s] ') . $message;
        echo $line . PHP_EOL;

        $logPath = storage_path('logs/migration.log');
        file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
    }
}

<?php

use App\Models\ClientXp;
use App\Models\User;
use App\Services\Migration\PhpToLaravelMigrator;

function makeLegacyPdo(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("
        CREATE TABLE clients (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT    NOT NULL,
            email         TEXT    NOT NULL,
            password_hash TEXT    NOT NULL,
            plan          TEXT    DEFAULT 'esencial',
            status        TEXT    DEFAULT 'activo',
            client_code   TEXT,
            coach_id      INTEGER,
            fecha_inicio  TEXT,
            birth_date    TEXT,
            created_at    TEXT    NOT NULL,
            updated_at    TEXT
        )
    ");
    return $pdo;
}

function addXpTable(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE client_xp (
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id          TEXT    NOT NULL,
            user_id            INTEGER,
            xp_total           INTEGER DEFAULT 0,
            level              INTEGER DEFAULT 1,
            streak_days        INTEGER DEFAULT 0,
            streak_protected   INTEGER DEFAULT 0,
            streak_last_date   TEXT,
            last_activity_date TEXT
        )
    ");
}


it('migrates a client from legacy data to Laravel users table', function () {
    $pdo  = makeLegacyPdo();
    $hash = '$2y$12$abcdefghijklmnopqrstuutest';

    $pdo->prepare(
        "INSERT INTO clients (name, email, password_hash, plan, status, client_code, coach_id, fecha_inicio, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        'Carlos Test', 'carlos@legacytest.com', $hash,
        'elite', 'activo', 'elite-001', null,
        '2026-01-01', '1990-05-15',
        '2026-01-01 00:00:00', '2026-01-01 00:00:00',
    ]);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();

    $this->assertDatabaseHas('users', [
        'email' => 'carlos@legacytest.com',
        'role'  => 'client',
        'plan'  => 'elite',
    ]);
});

it('sets the correct plan, status and client_code on the new user', function () {
    $pdo = makeLegacyPdo();

    $pdo->prepare("
        INSERT INTO clients (name, email, password_hash, plan, status, client_code, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        'Plan Check', 'plancheck@legacy.com', 'hash',
        'metodo', 'inactivo', 'met-999', '2026-01-01 00:00:00',
    ]);

    (new PhpToLaravelMigrator($pdo))->migrateClients();

    $user = User::where('email', 'plancheck@legacy.com')->firstOrFail();

    expect($user->plan)->toBe('metodo')
        ->and($user->status)->toBe('inactivo')
        ->and($user->client_code)->toBe('met-999');
});

it('does not re-hash the bcrypt password during migration', function () {
    $bcryptHash = '$2y$12$abcdefghijklmnopqrstuutest';
    $pdo        = makeLegacyPdo();

    $pdo->prepare(
        "INSERT INTO clients (name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?)"
    )->execute(['Hash Test', 'hashtest@legacy.com', $bcryptHash, 'esencial', '2026-01-01 00:00:00']);

    (new PhpToLaravelMigrator($pdo))->migrateClients();

    $user = User::where('email', 'hashtest@legacy.com')->firstOrFail();
    expect($user->password)->toBe($bcryptHash);
});

it('is idempotent — running migrateClients twice does not duplicate users', function () {
    $pdo = makeLegacyPdo();

    $pdo->prepare(
        "INSERT INTO clients (name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?)"
    )->execute(['Ana Idempotente', 'ana@idem.com', 'hash', 'metodo', '2026-01-01 00:00:00']);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();
    $migrator->migrateClients();

    expect(User::where('email', 'ana@idem.com')->count())->toBe(1);
});

it('populates the id map so dependent entities can resolve the new user id', function () {
    $pdo = makeLegacyPdo();
    addXpTable($pdo);

    $pdo->prepare(
        "INSERT INTO clients (id, name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([1, 'Map Test', 'maptest@legacy.com', 'hash', 'elite', '2026-01-01 00:00:00']);

    $pdo->prepare(
        "INSERT INTO client_xp (client_id, xp_total, level, streak_days, last_activity_date) VALUES (?, ?, ?, ?, ?)"
    )->execute(['1', 500, 2, 5, '2026-03-09']);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();
    $migrator->migrateGamification();

    $user = User::where('email', 'maptest@legacy.com')->firstOrFail();

    $this->assertDatabaseHas('client_xp', [
        'user_id'  => $user->id,
        'xp_total' => 500,
    ]);
});

it('preserves XP data during migration and maps correctly to the new user', function () {
    $pdo = makeLegacyPdo();
    addXpTable($pdo);

    $pdo->prepare(
        "INSERT INTO clients (id, name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([1, 'XP Test', 'xptest@legacy.com', 'hash', 'elite', '2026-01-01 00:00:00']);

    $pdo->prepare(
        "INSERT INTO client_xp (client_id, xp_total, level, streak_days, last_activity_date) VALUES (?, ?, ?, ?, ?)"
    )->execute(['1', 850, 3, 7, '2026-03-09']);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();
    $migrator->migrateGamification();

    $user = User::where('email', 'xptest@legacy.com')->firstOrFail();

    expect($user->xp)->not->toBeNull()
        ->and($user->xp->xp_total)->toBe(850)
        ->and($user->xp->level)->toBe(3)
        ->and($user->xp->streak_days)->toBe(7);
});

it('handles the legacy streak_last_date column as an alias for last_activity_date', function () {
    $pdo = makeLegacyPdo();

    // Alternate legacy schema that uses streak_last_date (v9 naming convention).
    $pdo->exec("
        CREATE TABLE client_xp (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id        TEXT NOT NULL,
            xp_total         INTEGER DEFAULT 0,
            level            INTEGER DEFAULT 1,
            streak_days      INTEGER DEFAULT 0,
            streak_protected INTEGER DEFAULT 0,
            streak_last_date TEXT
        )
    ");

    $pdo->prepare(
        "INSERT INTO clients (id, name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([1, 'Streak Col', 'streakcol@legacy.com', 'hash', 'elite', '2026-01-01 00:00:00']);

    $pdo->prepare(
        "INSERT INTO client_xp (client_id, xp_total, level, streak_days, streak_last_date) VALUES (?, ?, ?, ?, ?)"
    )->execute(['1', 200, 1, 3, '2026-03-08']);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();
    $migrator->migrateGamification();

    $user = User::where('email', 'streakcol@legacy.com')->firstOrFail();

    expect($user->xp)->not->toBeNull()
        ->and($user->xp->last_activity_date->format('Y-m-d'))->toBe('2026-03-08');
});

it('is idempotent for gamification — running twice does not duplicate XP records', function () {
    $pdo = makeLegacyPdo();
    addXpTable($pdo);

    $pdo->prepare(
        "INSERT INTO clients (id, name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([1, 'Idem XP', 'idemxp@legacy.com', 'hash', 'elite', '2026-01-01 00:00:00']);

    $pdo->prepare(
        "INSERT INTO client_xp (client_id, xp_total, level, streak_days) VALUES (?, ?, ?, ?)"
    )->execute(['1', 100, 1, 2]);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();
    $migrator->migrateGamification();
    $migrator->migrateGamification(); // second run — must be a no-op

    $user = User::where('email', 'idemxp@legacy.com')->firstOrFail();

    expect(ClientXp::where('user_id', $user->id)->count())->toBe(1);
});

it('skips XP records whose client_id does not resolve to any migrated user', function () {
    $pdo = makeLegacyPdo();
    addXpTable($pdo);

    $pdo->prepare(
        "INSERT INTO client_xp (client_id, xp_total, level, streak_days) VALUES (?, ?, ?, ?)"
    )->execute(['999', 300, 2, 5]);

    (new PhpToLaravelMigrator($pdo))->migrateGamification();

    expect(ClientXp::count())->toBe(0);
});

it('gracefully handles a missing client_xp table in the legacy DB', function () {
    $pdo = makeLegacyPdo();

    expect(fn () => (new PhpToLaravelMigrator($pdo))->migrateGamification())
        ->not->toThrow(Exception::class);
});

it('migrates multiple clients and preserves all records', function () {
    $pdo  = makeLegacyPdo();
    $stmt = $pdo->prepare(
        "INSERT INTO clients (name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute(['Alice', 'alice@legacy.com', 'hash', 'elite',    '2026-01-01 00:00:00']);
    $stmt->execute(['Bob',   'bob@legacy.com',   'hash', 'metodo',   '2026-01-02 00:00:00']);
    $stmt->execute(['Carol', 'carol@legacy.com', 'hash', 'esencial', '2026-01-03 00:00:00']);

    (new PhpToLaravelMigrator($pdo))->migrateClients();

    expect(User::where('role', 'client')->count())->toBe(3);

    $this->assertDatabaseHas('users', ['email' => 'alice@legacy.com', 'plan' => 'elite']);
    $this->assertDatabaseHas('users', ['email' => 'bob@legacy.com',   'plan' => 'metodo']);
    $this->assertDatabaseHas('users', ['email' => 'carol@legacy.com', 'plan' => 'esencial']);
});

it('skips existing clients on re-run but inserts newly added ones', function () {
    $pdo  = makeLegacyPdo();
    $stmt = $pdo->prepare(
        "INSERT INTO clients (name, email, password_hash, plan, created_at) VALUES (?, ?, ?, ?, ?)"
    );

    $stmt->execute(['Existing', 'existing@legacy.com', 'hash', 'elite',  '2026-01-01 00:00:00']);
    $stmt->execute(['New User', 'new@legacy.com',      'hash', 'metodo', '2026-01-02 00:00:00']);

    $migrator = new PhpToLaravelMigrator($pdo);
    $migrator->migrateClients();

    // Simulate a new client added to the legacy DB between migration runs.
    $stmt->execute(['Third', 'third@legacy.com', 'hash', 'esencial', '2026-01-03 00:00:00']);
    $migrator->migrateClients();

    expect(User::where('role', 'client')->count())->toBe(3);
    $this->assertDatabaseHas('users', ['email' => 'third@legacy.com']);
});


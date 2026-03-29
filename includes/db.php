<?php

function dbConfig(): array
{
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'ecom_test',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ];
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = dbConfig();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['name'], $cfg['charset']);

    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    initializeDatabase($pdo);
    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Unable to load database/schema.sql');
    }

    $pdo->exec($schema);
    applyDbMigrations($pdo);
    $initialized = true;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name LIMIT 1');
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);
    return (bool) $stmt->fetchColumn();
}

function applyDbMigrations(PDO $pdo): void
{
    if (!columnExists($pdo, 'products', 'slug')) {
        $pdo->exec("ALTER TABLE products ADD COLUMN slug VARCHAR(255) NOT NULL DEFAULT '' AFTER id");
        $pdo->exec('ALTER TABLE products ADD UNIQUE KEY uniq_products_slug (slug)');
    }

    if (!columnExists($pdo, 'products', 'stock_qty')) {
        $pdo->exec('ALTER TABLE products ADD COLUMN stock_qty INT NOT NULL DEFAULT 0 AFTER cost_bdt');
    }

    if (!columnExists($pdo, 'site_config', 'config_key')) {
        throw new RuntimeException('site_config table is invalid');
    }
}

<?php
/**
 * Database Migration Script
 * Mount Excel Boarding School
 * Run via CLI: php database/migrate.php
 */

require_once __DIR__ . '/../includes/config.php';

// Security: CLI-only — no web access permitted
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access denied. This script must be run from the command line.");
}

echo "Starting Mount Excel Boarding School Database Migration...\n";

try {
    // Connect to MySQL server without selecting database first
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Read schema.sql
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Schema file not found at: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);

    // Execute queries
    $pdo->exec($sql);

    echo "SUCCESS: Database '" . DB_NAME . "' and all tables created/verified successfully!\n";

    // Reconnect with database selected to check table count
    $dbPdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $tables = $dbPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Current tables in database: " . implode(', ', $tables) . "\n";

} catch (PDOException $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

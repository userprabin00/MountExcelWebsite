<?php
/**
 * Database Backup Utility
 * Mount Excel Boarding School
 * Run via CLI: php database/backup.php
 */

require_once __DIR__ . '/../includes/config.php';

// Security: CLI-only — no web access permitted
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Forbidden. This script must be run from the command line.");
}

$backupDir = defined('BACKUP_DIR') ? BACKUP_DIR : (ROOT_PATH . '/backups');
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0750, true);
    // Protect backup directory if inside web root
    @file_put_contents($backupDir . '/.htaccess', "Require all denied\nDeny from all\n");
}

$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupDir . '/backup_mount_excel_' . $timestamp . '.sql';

echo "Starting database backup for " . DB_NAME . "...\n";

// Try using mysqldump if available
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
if (!file_exists($mysqldumpPath)) {
    $mysqldumpPath = 'mysqldump';
}

$cmd = sprintf(
    '"%s" --user=%s %s --host=%s --port=%s --default-character-set=utf8mb4 %s > "%s"',
    $mysqldumpPath,
    escapeshellarg(DB_USER),
    (!empty(DB_PASS) ? '--password=' . escapeshellarg(DB_PASS) : ''),
    escapeshellarg(DB_HOST),
    escapeshellarg(DB_PORT),
    escapeshellarg(DB_NAME),
    $backupFile
);

// Run mysqldump or PHP fallback
exec($cmd, $output, $returnVar);

if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
    echo "SUCCESS: Database backup created at: " . $backupFile . " (" . round(filesize($backupFile) / 1024, 2) . " KB)\n";
} else {
    // PHP Fallback dumper using PDO
    echo "Running PHP PDO database backup fallback...\n";
    try {
        $pdo = getDB();
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        $handle = fopen($backupFile, 'w');
        fwrite($handle, "-- Mount Excel Boarding School Database Backup\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($handle, "SET NAMES utf8mb4;\n");
        fwrite($handle, "SET foreign_key_checks = 0;\n\n");
        
        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            fwrite($handle, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $createTable['Create Table'] . ";\n\n");
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols = array_map(function($c) { return "`$c`"; }, array_keys($row));
                $vals = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($row));
                
                fwrite($handle, sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s);\n",
                    $table,
                    implode(', ', $cols),
                    implode(', ', $vals)
                ));
            }
            fwrite($handle, "\n");
        }
        fwrite($handle, "SET foreign_key_checks = 1;\n");
        fclose($handle);
        echo "SUCCESS: PHP fallback backup created at: " . $backupFile . "\n";
    } catch (Exception $e) {
        echo "ERROR creating backup: " . $e->getMessage() . "\n";
        exit(1);
    }
}

<?php
/**
 * Database Connection Management (PDO)
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/config.php';

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Failure: ' . $e->getMessage());
            
            if (APP_ENV === 'development') {
                throw new Exception('Database Connection Error: ' . $e->getMessage());
            } else {
                throw new Exception('A secure connection to the database could not be established. Please try again later.');
            }
        }
    }

    return $pdo;
}

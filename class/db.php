<?php
require_once __DIR__ . '/../config.php';

class db {
    private static $connection = null;

    protected function connect() {
        if (self::$connection === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
                . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$connection = new PDO($dsn, DB_USER, DB_PASS);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        }
        return self::$connection;
    }
}

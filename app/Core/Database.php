<?php
namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?PDO $pdo = null;

    public static function conn(): PDO {
        if (self::$pdo === null) {
            $host = env('DB_HOST', '127.0.0.1');
            $port = env('DB_PORT', '3306');
            $db = env('DB_DATABASE');
            $user = env('DB_USERNAME');
            $pass = env('DB_PASSWORD', '');
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            try {
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                // Sync MySQL session timezone with PHP timezone
                $offset = date('P'); // e.g. +07:00
                self::$pdo->exec("SET time_zone = '$offset'");
            } catch (PDOException $e) {
                app_log('error', 'DB Connection: ' . $e->getMessage());
                die('Database connection failed');
            }
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []) {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []) {
        return self::query($sql, $params)->fetch();
    }

    public static function fetchAll(string $sql, array $params = []) {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int {
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
        self::query($sql, $data);
        return (int) self::conn()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []) {
        $set = [];
        foreach ($data as $k => $v) $set[] = "$k = :$k";
        $sql = "UPDATE $table SET " . implode(',', $set) . " WHERE $where";
        self::query($sql, array_merge($data, $whereParams));
    }
}

<?php
/**
 * Database — PDO singleton wrapper.
 *
 * Usage:
 *   Database::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
 *   Database::fetchAll('SELECT * FROM products WHERE is_active = 1');
 *   Database::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
 *   Database::lastInsertId();
 *
 * Transactions:
 *   Database::beginTransaction();
 *   try { ... Database::commit(); } catch (\Throwable $e) { Database::rollback(); throw $e; }
 */

class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }

    /** Execute a query and return the PDOStatement. */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row, or null if not found. */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row !== false ? $row : null;
    }

    /** Fetch all rows. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Return the last auto-increment ID inserted. */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }

    public static function beginTransaction(): void
    {
        if (!self::getInstance()->inTransaction()) {
            self::getInstance()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->rollBack();
        }
    }

    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }
}

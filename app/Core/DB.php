<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/** Thin static wrapper around a single PDO connection. Always uses prepared statements. */
final class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = config('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], (int) $c['port'], $c['name'], $c['charset'] ?? 'utf8mb4');
            self::$pdo = new PDO($dsn, (string) $c['user'], (string) $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            // Keep MySQL timestamps in the same zone as PHP.
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$pdo;
    }

    public static function isConfigured(): bool
    {
        return (string) config('db.name') !== '' && (string) config('db.user') !== '';
    }

    public static function reset(): void
    {
        self::$pdo = null;
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        if (array_is_list($params)) {
            foreach ($params as $i => $v) {
                $stmt->bindValue($i + 1, $v, self::type($v));
            }
        } else {
            foreach ($params as $k => $v) {
                $stmt->bindValue(':' . ltrim((string) $k, ':'), $v, self::type($v));
            }
        }
        $stmt->execute();
        return $stmt;
    }

    private static function type(mixed $v): int
    {
        return match (true) {
            is_int($v)  => PDO::PARAM_INT,
            is_bool($v) => PDO::PARAM_BOOL,
            $v === null => PDO::PARAM_NULL,
            default     => PDO::PARAM_STR,
        };
    }

    public static function row(string $sql, array $params = []): ?array
    {
        $r = self::run($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function column(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            self::ident($table),
            implode(', ', array_map(fn ($c) => '`' . self::ident($c) . '`', $cols)),
            implode(', ', array_map(fn ($c) => ':' . $c, $cols))
        );
        self::run($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = '`' . self::ident($col) . '` = :set_' . $col;
            $params['set_' . $col] = $val;
        }
        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }
        return self::exec(sprintf('UPDATE `%s` SET %s WHERE %s', self::ident($table), implode(', ', $set), $where), $params);
    }

    /** @template T @param callable():T $fn @return T */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        if ($pdo->inTransaction()) {
            return $fn();
        }
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function ident(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
            throw new \InvalidArgumentException('Invalid identifier');
        }
        return $name;
    }

    /** Builds "?, ?, ?" for IN() clauses. */
    public static function placeholders(array $items): string
    {
        return implode(', ', array_fill(0, max(1, count($items)), '?'));
    }
}

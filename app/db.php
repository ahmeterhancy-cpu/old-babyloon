<?php
declare(strict_types=1);

/**
 * İnce PDO sarmalayıcısı. SQLite ve MySQL arasındaki sözdizimi farkları
 * yalnızca migrate.php'de ele alınır; uygulama kodu ikisinde de aynı çalışır.
 */
final class DB
{
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';

    public static function boot(array $cfg): void
    {
        $db = $cfg['db'];
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($db['driver'] === 'sqlite') {
            $dir = dirname($db['path']);
            if (!is_dir($dir)) { mkdir($dir, 0775, true); }
            self::$pdo = new PDO('sqlite:' . $db['path'], null, null, $opts);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
            self::$pdo->exec('PRAGMA journal_mode = WAL');
            self::$driver = 'sqlite';
        } elseif ($db['driver'] === 'dsn') {
            self::$pdo = new PDO($db['dsn'], $db['user'], $db['pass'], $opts);
            self::$driver = str_starts_with($db['dsn'], 'sqlite') ? 'sqlite' : 'mysql';
        } else {
            $charset = $db['charset'] ?? 'utf8mb4';
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['name'], $charset);
            if (!empty($db['port'])) { $dsn .= ';port=' . $db['port']; }
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], $opts);
            self::$driver = 'mysql';
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) { throw new RuntimeException('DB::boot() çağrılmadı'); }
        return self::$pdo;
    }

    public static function driver(): string { return self::$driver; }
    public static function isSqlite(): bool { return self::$driver === 'sqlite'; }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? $default : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql  = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table,
            implode(', ', $cols), implode(', ', array_map(fn($c) => ':' . $c, $cols)));
        self::run($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($data)));
        $st = self::run("UPDATE $table SET $sets WHERE $where", $data + $whereParams);
        return $st->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::run("DELETE FROM $table WHERE $where", $params)->rowCount();
    }

    /** Şu anki zaman damgası — iki sürücüde de aynı biçim. */
    public static function now(): string { return date('Y-m-d H:i:s'); }
}

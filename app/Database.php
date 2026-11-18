<?php
/**
 * Cienka warstwa nad PDO - jedyne miejsce, które zna dane dostępowe.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;
    private static array $cfg = [];

    /** Gdy true - błąd połączenia jest rzucany, a nie wyświetlany jako strona. */
    private static bool $silent = false;

    public static function init(array $cfg): void
    {
        self::$cfg = $cfg;
    }

    /** Przełącza tryb cichy (błąd połączenia jako wyjątek zamiast strony). */
    public static function silentErrors(bool $on = true): void
    {
        self::$silent = $on;
    }

    /**
     * Sprawdza, czy da się połączyć z bazą o podanych danych.
     * Niczego nie zmienia w bieżącej konfiguracji.
     *
     * @return array{ok:bool,message:string}
     */
    public static function testConnection(array $cfg): array
    {
        $host    = (string) ($cfg['host'] ?? 'localhost');
        $port    = (int) ($cfg['port'] ?? 3306);
        $name    = (string) ($cfg['name'] ?? '');
        $charset = (string) ($cfg['charset'] ?? 'utf8mb4');
        $socket  = (string) ($cfg['socket'] ?? '');

        if ($socket !== '') {
            $dsn = "mysql:unix_socket={$socket};dbname={$name};charset={$charset}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        }

        try {
            $pdo = new PDO(
                $dsn,
                (string) ($cfg['user'] ?? ''),
                (string) ($cfg['pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            unset($pdo);
            return ['ok' => true, 'message' => 'Połączenie działa.'];
        } catch (PDOException $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host    = self::$cfg['host'] ?? 'localhost';
        $port    = (int) (self::$cfg['port'] ?? 3306);
        $name    = self::$cfg['name'] ?? '';
        $charset = self::$cfg['charset'] ?? 'utf8mb4';
        $socket  = self::$cfg['socket'] ?? '';

        if ($socket !== '') {
            $dsn = "mysql:unix_socket={$socket};dbname={$name};charset={$charset}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ];

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) (self::$cfg['user'] ?? ''),
                (string) (self::$cfg['pass'] ?? ''),
                $options
            );
        } catch (PDOException $e) {
            // Nie ujawniamy hasła ani pełnego DSN-u użytkownikowi.
            error_log('Panel Magazyn: blad połączenia z bazą - ' . $e->getMessage());
            if (self::$silent) {
                throw $e;
            }
            http_response_code(500);
            self::renderConnectionError($e);
        }

        return self::$pdo;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    public static function value(string $sql, array $params = [], mixed $default = null): mixed
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $val = $st->fetchColumn();
        return $val === false ? $default : $val;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private static function renderConnectionError(PDOException $e): void
    {
        $debug = !empty(self::$cfg['show_errors']);
        $hint  = '<p>Panel nie może połączyć się z bazą danych. Sprawdź plik <code>config/config.php</code>: '
            . 'nazwa bazy, użytkownik, hasło oraz czy serwer MariaDB/MySQL jest uruchomiony.</p>';

        if ($debug) {
            $hint .= '<pre style="background:#f6f8fa;padding:12px;border-radius:8px;overflow:auto">'
                . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
        }

        echo '<!doctype html><html lang="pl"><head><meta charset="utf-8">'
            . '<title>Panel Magazynu - brak połączenia z bazą</title>'
            . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f4f6fa;'
            . 'margin:0;padding:40px;color:#1f2937}.box{max-width:760px;margin:0 auto;background:#fff;padding:28px 32px;'
            . 'border-radius:14px;box-shadow:0 8px 28px rgba(15,23,42,.08)}h1{margin:0 0 12px;font-size:22px;color:#b91c1c}'
            . 'code{background:#f1f5f9;padding:2px 6px;border-radius:5px}</style></head>'
            . '<body><div class="box"><h1>Brak połączenia z bazą danych</h1>' . $hint . '</div></body></html>';
        exit;
    }
}

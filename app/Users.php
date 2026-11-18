<?php
/**
 * Konta użytkowników panelu.
 *
 * Konta trzymane są w pliku config/users.php - baza danych pozostaje
 * nietknięta (panel nie tworzy tabel ani kolumn).
 *
 * Format pliku:
 *   return ['users' => [
 *       ['login' => 'ADMIN', 'name' => 'Administrator', 'password_hash' => '...',
 *        'role' => 'admin', 'active' => true, 'created_at' => '2026-11-09 18:00:00'],
 *   ]];
 *
 * Role:
 *   admin    - pełne prawa: zapis, usuwanie, zarządzanie kontami
 *   readonly - tylko odczyt (może zmienić własne hasło)
 */

declare(strict_types=1);

final class Users
{
    public const ROLE_ADMIN    = 'admin';
    public const ROLE_READONLY = 'readonly';

    /** Minimalna długość hasła. */
    public const MIN_PASSWORD = 8;

    /** Cache listy kont w obrębie jednego żądania. */
    private static ?array $cache = null;

    /** Ścieżka do pliku z kontami. */
    public static function path(): string
    {
        return dirname(__DIR__) . '/config/users.php';
    }

    /** Czy plik z kontami istnieje. */
    public static function fileExists(): bool
    {
        return is_file(self::path());
    }

    /**
     * Lista kont. Gdy pliku nie ma, ale w konfiguracji jest stare hasło
     * (config/auth.local.php), tworzy wirtualne konto administratora
     * i próbuje zapisać je w pliku.
     *
     * @return array<int,array{login:string,name:string,password_hash:string,role:string,active:bool,created_at:string}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (is_file(self::path())) {
            $data = @include self::path();
            if (is_array($data) && isset($data['users']) && is_array($data['users'])) {
                $out = [];
                foreach ($data['users'] as $u) {
                    if (!is_array($u) || empty($u['login'])) {
                        continue;
                    }
                    $out[] = self::normalize($u);
                }
                self::$cache = $out;
                return self::$cache;
            }
        }

        // Migracja ze starego układu: config/auth.local.php + config['auth']['user']
        $legacyHash = (string) ($GLOBALS['config']['auth']['password_hash'] ?? '');
        $legacyUser = (string) ($GLOBALS['config']['auth']['user'] ?? 'ADMIN');
        if ($legacyHash !== '') {
            $user = self::normalize([
                'login'         => $legacyUser,
                'name'          => 'Administrator',
                'password_hash' => $legacyHash,
                'role'          => self::ROLE_ADMIN,
                'active'        => true,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            self::save([$user]);   // zapis jest niekrytyczny - w razie braku praw konto działa wirtualnie
            self::$cache = [$user];
            return self::$cache;
        }

        self::$cache = [];
        return self::$cache;
    }

    /** Normalizuje rekord konta. */
    private static function normalize(array $u): array
    {
        $role = (string) ($u['role'] ?? self::ROLE_READONLY);
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_READONLY], true)) {
            $role = self::ROLE_READONLY;
        }

        return [
            'login'         => (string) $u['login'],
            'name'          => (string) ($u['name'] ?? $u['login']),
            'password_hash' => (string) ($u['password_hash'] ?? ''),
            'role'          => $role,
            'active'        => !empty($u['active']),
            'created_at'    => (string) ($u['created_at'] ?? ''),
        ];
    }

    /** Zapisuje listę kont do pliku. */
    public static function save(array $users): bool
    {
        $dir = dirname(self::path());
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        $normalized = [];
        $lines      = [];
        foreach ($users as $u) {
            $u = self::normalize($u);
            $normalized[] = $u;
            $lines[] = sprintf(
                "        [\n"
                . "            'login'         => %s,\n"
                . "            'name'          => %s,\n"
                . "            'password_hash' => %s,\n"
                . "            'role'          => %s,\n"
                . "            'active'        => %s,\n"
                . "            'created_at'    => %s,\n"
                . "        ],",
                var_export($u['login'], true),
                var_export($u['name'], true),
                var_export($u['password_hash'], true),
                var_export($u['role'], true),
                $u['active'] ? 'true' : 'false',
                var_export($u['created_at'], true)
            );
        }

        $content = "<?php\n"
            . "/**\n"
            . " * Konta użytkowników panelu - plik generowany, nie edytuj ręcznie.\n"
            . " *\n"
            . " * Hasła są zapisane jako bcrypt. Konta zarządza administrator\n"
            . " * w sekcji „Konta” panelu.\n"
            . " */\n\n"
            . "return [\n"
            . "    'users' => [\n"
            . implode("\n", $lines) . "\n"
            . "    ],\n"
            . "];\n";

        $tmp = self::path() . '.tmp';
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
            return false;
        }
        if (!@rename($tmp, self::path())) {
            @unlink($tmp);
            return false;
        }
        @chmod(self::path(), 0644);

        // Cache musi odzwierciedlać zapisany stan - inaczej ten sam request
        // czytałby poprzednią wersję listy kont.
        self::$cache = $normalized;

        return true;
    }

    /** Kasuje cache listy kont (po zmianie pliku z zewnątrz). */
    public static function forget(): void
    {
        self::$cache = null;
    }

    /** Znajduje konto po loginie (bez rozróżniania wielkości liter). */
    public static function find(string $login): ?array
    {
        $needle = self::key($login);
        if ($needle === '') {
            return null;
        }
        foreach (self::all() as $u) {
            if (self::key($u['login']) === $needle) {
                return $u;
            }
        }
        return null;
    }

    /** Klucz porównawczy loginu (małe litery, bez spacji). */
    private static function key(string $login): string
    {
        return mb_strtolower(trim($login));
    }

    /** Czy login jest wolny. */
    public static function loginFree(string $login, ?string $exceptLogin = null): bool
    {
        $existing = self::find($login);
        if ($existing === null) {
            return true;
        }
        return $exceptLogin !== null && self::key($existing['login']) === self::key($exceptLogin);
    }

    /**
     * Dodaje nowe konto.
     *
     * @return array{ok:bool,message:string}
     */
    public static function create(string $login, string $name, string $password, string $role): array
    {
        $login = trim($login);
        $name  = trim($name) !== '' ? trim($name) : $login;

        if ($login === '') {
            return ['ok' => false, 'message' => 'Login nie może być pusty.'];
        }
        if (!preg_match('/^[A-Za-z0-9._-]{2,32}$/', $login)) {
            return ['ok' => false, 'message' => 'Login może zawierać litery, cyfry, kropkę, myślnik i podkreślenie (2–32 znaki).'];
        }
        if (!self::loginFree($login)) {
            return ['ok' => false, 'message' => 'Konto o takim loginie już istnieje.'];
        }
        if (strlen($password) < self::MIN_PASSWORD) {
            return ['ok' => false, 'message' => 'Hasło musi mieć co najmniej ' . self::MIN_PASSWORD . ' znaków.'];
        }
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_READONLY], true)) {
            $role = self::ROLE_READONLY;
        }

        $users = self::all();
        $users[] = self::normalize([
            'login'         => $login,
            'name'          => $name,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'role'          => $role,
            'active'        => true,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        if (!self::save($users)) {
            return ['ok' => false, 'message' => 'Nie udało się zapisać kont - sprawdź prawa do zapisu w katalogu config/.'];
        }

        return ['ok' => true, 'message' => 'Konto „' . $login . '” zostało utworzone.'];
    }

    /**
     * Aktualizuje konto (nazwa, rola, aktywność; hasło osobno).
     *
     * @return array{ok:bool,message:string}
     */
    public static function update(string $login, array $data): array
    {
        $users = self::all();
        $found = false;

        foreach ($users as $i => $u) {
            if (self::key($u['login']) !== self::key($login)) {
                continue;
            }
            $found = true;

            if (array_key_exists('name', $data)) {
                $u['name'] = trim((string) $data['name']) !== '' ? trim((string) $data['name']) : $u['login'];
            }
            if (array_key_exists('role', $data)) {
                $role = (string) $data['role'];
                $u['role'] = in_array($role, [self::ROLE_ADMIN, self::ROLE_READONLY], true) ? $role : $u['role'];
            }
            if (array_key_exists('active', $data)) {
                $u['active'] = (bool) $data['active'];
            }
            $users[$i] = $u;
        }

        if (!$found) {
            return ['ok' => false, 'message' => 'Nie znaleziono konta.'];
        }

        if (!self::save($users)) {
            return ['ok' => false, 'message' => 'Nie udało się zapisać kont - sprawdź prawa do zapisu w katalogu config/.'];
        }

        return ['ok' => true, 'message' => 'Konto „' . $login . '” zostało zaktualizowane.'];
    }

    /** Ustawia nowe hasło konta. */
    public static function setPassword(string $login, string $password): array
    {
        if (strlen($password) < self::MIN_PASSWORD) {
            return ['ok' => false, 'message' => 'Hasło musi mieć co najmniej ' . self::MIN_PASSWORD . ' znaków.'];
        }

        $users = self::all();
        $found = false;
        foreach ($users as $i => $u) {
            if (self::key($u['login']) !== self::key($login)) {
                continue;
            }
            $u['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $users[$i] = $u;
            $found = true;
        }

        if (!$found) {
            return ['ok' => false, 'message' => 'Nie znaleziono konta.'];
        }
        if (!self::save($users)) {
            return ['ok' => false, 'message' => 'Nie udało się zapisać hasła - sprawdź prawa do zapisu w katalogu config/.'];
        }

        return ['ok' => true, 'message' => 'Hasło konta „' . $login . '” zostało zmienione.'];
    }

    /** Usuwa konto. Ostatniego administratora usunąć się nie da. */
    public static function delete(string $login): array
    {
        $users = self::all();
        $out   = [];
        $removed = false;

        foreach ($users as $u) {
            if (self::key($u['login']) === self::key($login)) {
                $removed = true;
                continue;
            }
            $out[] = $u;
        }

        if (!$removed) {
            return ['ok' => false, 'message' => 'Nie znaleziono konta.'];
        }
        if (self::adminCount($out) < 1) {
            return ['ok' => false, 'message' => 'To jedyne konto administratora - nie można go usunąć.'];
        }

        if (!self::save($out)) {
            return ['ok' => false, 'message' => 'Nie udało się zapisać kont - sprawdź prawa do zapisu w katalogu config/.'];
        }

        return ['ok' => true, 'message' => 'Konto „' . $login . '” zostało usunięte.'];
    }

    /** Liczba kont z rolą administratora. */
    public static function adminCount(?array $users = null): int
    {
        $users = $users ?? self::all();
        $n = 0;
        foreach ($users as $u) {
            if ($u['role'] === self::ROLE_ADMIN && $u['active']) {
                $n++;
            }
        }
        return $n;
    }

    /** Czy konto może logować się i ma określoną rolę. */
    public static function verify(string $login, string $password): ?array
    {
        $user = self::find($login);
        if ($user === null || !$user['active']) {
            return null;
        }
        if ($user['password_hash'] === '' || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    /** Etykieta roli po polsku. */
    public static function roleLabel(string $role): string
    {
        return $role === self::ROLE_ADMIN ? 'Administrator' : 'Tylko odczyt';
    }
}

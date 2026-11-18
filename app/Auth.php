<?php
/**
 * Logowanie administratora i użytkowników (sesja + bcrypt) oraz role.
 */

declare(strict_types=1);

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS  = 300;

    public static function enabled(): bool
    {
        return (bool) ($GLOBALS['config']['auth']['enabled'] ?? true);
    }

    public static function check(): bool
    {
        if (!self::enabled()) {
            return true;
        }
        return !empty($_SESSION['auth_ok']) && $_SESSION['auth_ok'] === true;
    }

    /** Login zalogowanego użytkownika (także przy wyłączonym logowaniu). */
    public static function user(): string
    {
        return (string) ($_SESSION['auth_user'] ?? (string) ($GLOBALS['config']['auth']['user'] ?? 'ADMIN'));
    }

    /** Nazwa wyświetlana obok loginu. */
    public static function userName(): string
    {
        $user = Users::find(self::user());
        if ($user !== null && $user['name'] !== '') {
            return $user['name'];
        }
        return self::user();
    }

    /** Rola zalogowanego użytkownika: admin albo readonly. */
    public static function role(): string
    {
        if (!empty($_SESSION['auth_role'])) {
            return (string) $_SESSION['auth_role'];
        }
        return Users::ROLE_ADMIN;   // logowanie wyłączone - pełne prawa
    }

    /** Czy zalogowany użytkownik jest administratorem. */
    public static function isAdmin(): bool
    {
        if (!self::enabled()) {
            return true;
        }
        return self::role() === Users::ROLE_ADMIN;
    }

    /** Czy zalogowany użytkownik może zapisywać dane. */
    public static function canWrite(): bool
    {
        return self::isAdmin();
    }

    /** Czy w systemie istnieje jakiekolwiek konto (panel gotowy do logowania). */
    public static function hasAccounts(): bool
    {
        return Users::all() !== [];
    }

    /**
     * Próba logowania. Zwraca true przy powodzeniu.
     */
    public static function attempt(string $login, string $password): bool
    {
        if (!self::enabled()) {
            $_SESSION['auth_ok']   = true;
            $_SESSION['auth_user'] = (string) ($GLOBALS['config']['auth']['user'] ?? 'ADMIN');
            $_SESSION['auth_role'] = Users::ROLE_ADMIN;
            return true;
        }

        if (self::isLocked()) {
            return false;
        }

        $user = Users::verify($login, $password);
        if ($user !== null) {
            session_regenerate_id(true);
            $_SESSION['auth_ok']   = true;
            $_SESSION['auth_user'] = $user['login'];
            $_SESSION['auth_role'] = $user['role'];
            unset($_SESSION['auth_fail'], $_SESSION['auth_lock_until']);
            return true;
        }

        self::registerFailure();
        return false;
    }

    /**
     * Ustawia hasło pierwszego konta administratora.
     *
     * @return array{ok:bool,message:string}
     */
    public static function createFirstAdmin(string $login, string $name, string $password, string $confirm): array
    {
        if ($password !== $confirm) {
            return ['ok' => false, 'message' => 'Powtórzone hasło różni się od nowego hasła.'];
        }
        if (strlen($password) < Users::MIN_PASSWORD) {
            return ['ok' => false, 'message' => 'Hasło musi mieć co najmniej ' . Users::MIN_PASSWORD . ' znaków.'];
        }

        $res = Users::create($login, $name, $password, Users::ROLE_ADMIN);
        if (!$res['ok']) {
            return $res;
        }

        return ['ok' => true, 'message' => 'Konto administratora zostało utworzone. Możesz się zalogować.'];
    }

    /**
     * Zmiana hasła zalogowanego użytkownika.
     *
     * @return array{ok:bool,message:string}
     */
    public static function changeOwnPassword(string $current, string $new, string $confirm): array
    {
        $login = self::user();

        if (!self::enabled()) {
            return ['ok' => false, 'message' => 'Logowanie jest wyłączone w konfiguracji.'];
        }
        if ($new !== $confirm) {
            return ['ok' => false, 'message' => 'Powtórzone hasło różni się od nowego hasła.'];
        }
        if (strlen($new) < Users::MIN_PASSWORD) {
            return ['ok' => false, 'message' => 'Nowe hasło musi mieć co najmniej ' . Users::MIN_PASSWORD . ' znaków.'];
        }

        $user = Users::find($login);
        if ($user === null) {
            return ['ok' => false, 'message' => 'Nie znaleziono Twojego konta.'];
        }
        if ($user['password_hash'] === '' || !password_verify($current, $user['password_hash'])) {
            return ['ok' => false, 'message' => 'Aktualne hasło jest nieprawidłowe.'];
        }
        if (mb_strtolower($new) === mb_strtolower($login)) {
            return ['ok' => false, 'message' => 'Hasło nie może być takie samo jak login.'];
        }

        return Users::setPassword($login, $new);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        session_destroy();
    }

    public static function isLocked(): bool
    {
        $until = (int) ($_SESSION['auth_lock_until'] ?? 0);
        return $until > time();
    }

    public static function lockRemaining(): int
    {
        return max(0, (int) ($_SESSION['auth_lock_until'] ?? 0) - time());
    }

    private static function registerFailure(): void
    {
        $fails = (int) ($_SESSION['auth_fail'] ?? 0) + 1;
        $_SESSION['auth_fail'] = $fails;

        if ($fails >= self::MAX_ATTEMPTS) {
            $_SESSION['auth_lock_until'] = time() + self::LOCK_SECONDS;
            $_SESSION['auth_fail']      = 0;
        }
    }

    /** Przekierowanie na formularz logowania, jeśli brak sesji. */
    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }
        flash('warning', 'Zaloguj się, aby przejść do panelu.');
        redirect('login');
    }

    /**
     * Wymuszenie uprawnień administratora (konta, ustawienia).
     */
    public static function requireAdmin(): void
    {
        if (self::isAdmin()) {
            return;
        }
        flash('danger', 'Ta sekcja jest dostępna tylko dla administratorów.');
        redirect('dashboard');
    }
}

<?php
/**
 * Funkcje pomocnicze: escapowanie, URL-e, CSRF, komunikaty flash, formatowanie.
 */

declare(strict_types=1);

/** Escape dla HTML - chroni przed XSS. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Ustawiona w config.php ścieżka bazowa (np. '/magazyn'). */
function base_path(): string
{
    return rtrim((string) ($GLOBALS['config']['app']['base_path'] ?? ''), '/');
}

/** Buduje adres URL do trasy panelu. */
function url(string $route = 'dashboard', array $params = []): string
{
    $query = array_merge(['r' => $route], $params);
    return base_path() . '/index.php?' . http_build_query($query);
}

/** Przekierowanie + zakończenie skryptu. */
function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

/** Czy zalogowany użytkownik może zapisywać dane (admin vs tylko odczyt). */
function can_write(): bool
{
    return Auth::canWrite();
}

/**
 * Sprawdza uprawnienia do zapisu - dla kont „tylko odczyt” przerywa operację.
 */
function require_write(): void
{
    if (can_write()) {
        return;
    }
    flash('danger', 'Twoje konto ma uprawnienia „tylko odczyt” - nie możesz zapisywać ani usuwać danych.');
    redirect('dashboard');
}

/* -------------------------------------------------------------------------
 |  CSRF
 | ---------------------------------------------------------------------- */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    return !empty($_SESSION['_csrf']) && hash_equals((string) $_SESSION['_csrf'], $sent);
}

/* -------------------------------------------------------------------------
 |  Komunikaty flash (jednorazowe)
 | ---------------------------------------------------------------------- */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_pull(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/* -------------------------------------------------------------------------
 |  Formatowanie
 | ---------------------------------------------------------------------- */
function fmt_date(?string $date, string $format = 'd.m.Y'): string
{
    if ($date === null || $date === '' || str_starts_with($date, '0000')) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts === false ? (string) $date : date($format, $ts);
}

function fmt_datetime(?string $value, string $format = 'd.m.Y H:i'): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    $ts = strtotime($value);
    return $ts === false ? (string) $value : date($format, $ts);
}

/** Czytelna etykieta dla wartości NULL / pustej. */
function dash(mixed $value): string
{
    if ($value === null || $value === '') {
        return '<span class="muted">—</span>';
    }
    return e((string) $value);
}

/** Polskie znaki w znakowaniu UTF-8 (do wyszukiwarki). */
function fold_pl(string $value): string
{
    $map = [
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
        'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
        'Ó' => 'o', 'Ś' => 's', 'Ź' => 'z', 'Ż' => 'z',
    ];
    return strtr($value, $map);
}

/** Parametr GET jako string (bez tablic). */
function qs(string $key, string $default = ''): string
{
    $val = $_GET[$key] ?? $default;
    return is_string($val) ? trim($val) : $default;
}

/** Parametr GET jako int z ograniczeniem zakresu. */
function qs_int(string $key, int $default = 0, int $min = PHP_INT_MIN, int $max = PHP_INT_MAX): int
{
    $val = $_GET[$key] ?? null;
    if ($val === null || !is_scalar($val) || !is_numeric((string) $val)) {
        return $default;
    }
    return max($min, min($max, (int) $val));
}

/** Klucz tablicy jako string (indeksy z GET). */
function qs_str_array(string $key): array
{
    $val = $_GET[$key] ?? [];
    if (!is_array($val)) {
        return [];
    }
    $out = [];
    foreach ($val as $k => $v) {
        if (is_scalar($v)) {
            $out[(string) $k] = (string) $v;
        }
    }
    return $out;
}

/**
 * Filtry z GET z zachowaniem zagnieżdżonych tablic (np. f[Data][from]).
 *
 * @return array<string,mixed>
 */
function qs_filters(string $key = 'f'): array
{
    $val = $_GET[$key] ?? [];
    if (!is_array($val)) {
        return [];
    }

    $out = [];
    foreach ($val as $col => $value) {
        $col = (string) $col;
        if (is_array($value)) {
            $sub = [];
            foreach ($value as $op => $v) {
                if (is_scalar($v) && trim((string) $v) !== '') {
                    $sub[(string) $op] = trim((string) $v);
                }
            }
            if ($sub !== []) {
                $out[$col] = $sub;
            }
        } elseif (is_scalar($value)) {
            $v = trim((string) $value);
            if ($v !== '') {
                $out[$col] = $v;
            }
        }
    }

    return $out;
}

/** Czy filtr zakresowy ma ustawioną dolną lub górną granicę. */
function filter_has_range(array $filters, string $column): bool
{
    return isset($filters[$column]) && is_array($filters[$column]);
}

/** Buduje URL zachowując bieżące parametry GET i podmieniając podane. */
function url_with(array $overrides, string $route = 'table'): string
{
    $params = array_merge($_GET, $overrides);
    $params['r'] = $route;
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        }
    }
    return base_path() . '/index.php?' . http_build_query($params);
}

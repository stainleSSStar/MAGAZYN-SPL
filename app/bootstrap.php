<?php
/**
 * Bootstrap aplikacji - punkt startowy dla każdego żądania.
 */

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', APP_ROOT . '/app');
define('VIEW_PATH', APP_ROOT . '/views');

require APP_PATH . '/PasswordStore.php';
require APP_PATH . '/Users.php';
require APP_PATH . '/PanelData.php';

// --- Wczytanie konfiguracji -------------------------------------------------
$configFile = APP_ROOT . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Brak pliku konfiguracji: config/config.php. Skopiuj config/config.sample.php.');
}

/** @var array $config */
$config = require $configFile;

// Opcjonalne nadpisania lokalne (config/config.local.php) - przydatne przy
// testach, gdy dane produkcyjne w config.php mają pozostać nienaruszone.
$localConfigFile = APP_ROOT . '/config/config.local.php';
if (is_file($localConfigFile)) {
    $local = require $localConfigFile;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

// Dane bazy zapisane z kreatora pierwszego uruchomienia (config/db.local.php).
$dbConfigFile = APP_ROOT . '/config/db.local.php';
if (is_file($dbConfigFile)) {
    $dbLocal = require $dbConfigFile;
    if (is_array($dbLocal)) {
        $config = array_replace_recursive($config, $dbLocal);
    }
}

// Hasło administratora (config/auth.local.php) - ma najwyższy priorytet.
// Powstaje przy pierwszym ustawieniu hasła z panelu albo przez bin/set-password.php.
$authConfigFile = PasswordStore::path();
if (is_file($authConfigFile)) {
    $authConfig = require $authConfigFile;
    if (is_array($authConfig)) {
        $config = array_replace_recursive($config, $authConfig);
    }
}

$GLOBALS['config'] = $config;

/* -------------------------------------------------------------------------
 |  Ścieżka bazowa (base_path)
 |
 |  Jeśli w konfiguracji jest pusta, wykrywamy ją automatycznie z adresu
 |  skryptu - dzięki temu panel działa po rozpakowaniu w dowolnym podkatalogu
 |  (np. https://serwer.pl/twojapaczka/) bez dodatkowej konfiguracji.
 | ---------------------------------------------------------------------- */
if ((string) ($config['app']['base_path'] ?? '') === '') {
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dir    = str_replace('\\', '/', dirname($script));
    $dir    = rtrim($dir, '/');
    $config['app']['base_path'] = ($dir === '/' || $dir === '.') ? '' : $dir;
    $GLOBALS['config'] = $config;
}

// --- Strefa czasowa i raportowanie błędów -----------------------------------
date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Warsaw');

error_reporting(E_ALL);
ini_set('display_errors', $config['db']['show_errors'] ? '1' : '0');
ini_set('log_errors', '1');

set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// --- Globalny handler wyjątków: czytelna strona zamiast surowego PHP -------
// UWAGA: ten handler działa też PRZED wczytaniem Helpers.php, dlatego nie może
// korzystać z funkcji aplikacji (np. base_path()) - liczy ścieżkę samodzielnie.
set_exception_handler(static function (Throwable $e): void {
    error_log('Panel Magazyn: nieobsłużony błąd - ' . $e->getMessage()
        . ' | ' . $e->getFile() . ':' . $e->getLine());

    if (!headers_sent()) {
        http_response_code(500);
    }

    $debug = !empty($GLOBALS['config']['db']['show_errors']);
    $detail = $debug
        ? '<pre style="background:#f1f5f9;padding:12px;border-radius:8px;overflow:auto">'
            . htmlspecialchars($e->getMessage() . "\n\n" . $e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8')
            . '</pre>'
        : '<p>Szczegóły zapisano w logu serwera.</p>';

    $base = rtrim((string) ($GLOBALS['config']['app']['base_path'] ?? ''), '/');

    echo '<!doctype html><html lang="pl"><head><meta charset="utf-8"><title>Panel Magazynu - błąd</title>'
        . '<style>body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f2f5fa;'
        . 'margin:0;padding:40px;color:#14213a}.box{max-width:780px;margin:0 auto;background:#fff;padding:28px 32px;'
        . 'border-radius:14px;box-shadow:0 10px 30px rgba(11,37,69,.1);border-top:4px solid #1c62b8}'
        . 'h1{margin:0 0 12px;font-size:21px;color:#0b2545}a{color:#1c62b8}</style></head>'
        . '<body><div class="box"><h1>Wystąpił błąd w panelu</h1>'
        . '<p>Operacja nie została zakończona. Spróbuj ponownie lub wróć do pulpitu.</p>'
        . $detail
        . '<p><a href="' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8') . '/index.php?r=dashboard">← Wróć do pulpitu</a></p>'
        . '</div></body></html>';
    exit;
});

// --- Sesja -------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => ($config['app']['base_path'] ?? '') . '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('MAGAZYNSESS');

    try {
        session_start();
    } catch (Throwable $e) {
        error_log('Panel Magazyn: nie udało się uruchomić sesji - ' . $e->getMessage());
        http_response_code(500);
        echo '<!doctype html><html lang="pl"><head><meta charset="utf-8"><title>Panel Magazynu - błąd sesji</title>'
            . '<style>body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f2f5fa;'
            . 'margin:0;padding:40px;color:#14213a}.box{max-width:760px;margin:0 auto;background:#fff;padding:28px 32px;'
            . 'border-radius:14px;box-shadow:0 10px 30px rgba(11,37,69,.1);border-top:4px solid #1c62b8}'
            . 'h1{margin:0 0 12px;font-size:21px;color:#0b2545}code{background:#f1f5f9;padding:2px 6px;border-radius:5px}</style>'
            . '</head><body><div class="box"><h1>Nie udało się uruchomić sesji</h1>'
            . '<p>Panel nie może zapisać sesji - sprawdź uprawnienia do katalogu sesji PHP '
            . '(domyślnie <code>' . htmlspecialchars((string) ini_get('session.save_path'), ENT_QUOTES, 'UTF-8') . '</code>) '
            . 'oraz plik <code>' . htmlspecialchars(APP_ROOT . '/config/config.php', ENT_QUOTES, 'UTF-8') . '</code>.</p>'
            . '</div></body></html>';
        exit;
    }
}

// --- Autoload ---------------------------------------------------------------
require APP_PATH . '/Helpers.php';
require APP_PATH . '/Database.php';
require APP_PATH . '/Schema.php';
require APP_PATH . '/Repository.php';
require APP_PATH . '/Validator.php';
require APP_PATH . '/FkResolver.php';
require APP_PATH . '/Stats.php';
require APP_PATH . '/Auth.php';
require APP_PATH . '/View.php';

<?php
/**
 * Wzorcowa konfiguracja - kopia do wypełnienia.
 * Skopiuj na config/config.php i uzupełnij dane swojego serwera.
 */

return [

    'db' => [
        'host'        => 'localhost',
        'port'        => 3306,
        'name'        => 'magazyn',
        'user'        => 'uzytkownik_bazy',
        'pass'        => 'haslo_bazy',
        'charset'     => 'utf8mb4',
        'socket'      => '',
        'show_errors' => false,
    ],

    'app' => [
        'name'            => 'Panel Magazynu',
        'base_path'       => '',
        'timezone'        => 'Europe/Warsaw',
        'date_format'     => 'd.m.Y',
        'datetime_format' => 'd.m.Y H:i',
        'rows_default'    => 50,
        'rows_max'        => 500,
        'version'         => '1.3.1',
    ],

    'auth' => [
        'enabled' => true,
        'user'    => 'ADMIN',
        // Pusty hash = hasło nieustawione. Panel poprosi o ustawienie hasła
        // przy pierwszym otwarciu. NIE MA ŻADNEGO HASŁA DOMYŚLNEGO.
        'password_hash' => '',
    ],
];

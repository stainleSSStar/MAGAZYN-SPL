<?php
/**
 * =====================================================================
 *  PANEL MAGAZYN - KONFIGURACJA
 * =====================================================================
 *  To jest JEDYNY plik, który musisz zmienić przy wdrożeniu na
 *  produkcję (albo na swoim serwerze testowym).
 *
 *  Aby wdrożyć panel na innej bazie o tej samej strukturze:
 *    1. Edytuj sekcję 'db'  -> host / port / name / user / pass
 *    2. Edytuj sekcję 'auth' -> login i hash hasla (patrz niżej)
 *    3. Gotowe. Panel automatycznie dostosuje się do nowych tabel.
 *
 *  Hasło admina: NIE zapisujemy go wprost - trzymamy bcrypt hash.
 *  Nowy hash wygenerujesz komendą:
 *      php bin/set-password.php "TwojeNoweHaslo"
 *  a wkleisz tutaj w sekcji 'auth'.
 * =====================================================================
 */

return [

    /* -----------------------------------------------------------------
     |  BAZA DANYCH
     |  Ustaw wartości swojego serwera. Reszta panelu działa bez zmian.
     | ----------------------------------------------------------------- */
    'db' => [
        'host'    => 'localhost',
        'port'    => 3306,
        'name'    => 'magazyn',
        'user'    => 'magazyn_app',
        'pass'    => 'TU_WPISZ_HASLO_BAZY',
        'charset' => 'utf8mb4',

        // Opcjonalnie: jeśli MariaDB słucha tylko na gnieździe (socket),
        // ustaw np. 'localhost:/var/run/mysqld/mysqld.sock'
        // albo zostaw '' i skonfiguruj host/port.
        'socket'  => '',

        // Pokaż pełne komunikaty błędów (WYŁĄCZ na produkcji!)
        'show_errors' => false,
    ],

    /* -----------------------------------------------------------------
     |  APLIKACJA
     | ----------------------------------------------------------------- */
    'app' => [
        'name'        => 'Panel Magazynu',

        // Jeśli wrzucasz panel w PODKATALOG (np. https://serwer.pl/magazyn/),
        // wpisz tutaj '/magazyn' (bez slasha na końcu). Puste = katalog główny.
        'base_path'   => '',

        'timezone'    => 'Europe/Warsaw',
        'date_format' => 'd.m.Y',
        'datetime_format' => 'd.m.Y H:i',

        // Domyślna liczba rekordów na stronie
        'rows_default' => 50,

        // Ile rekordów najwięcej może zostać pobranych naraz (ochrona)
        'rows_max'     => 500,

        // Wersja panelu
        'version' => '1.3.1',
    ],

    /* -----------------------------------------------------------------
     |  LOGOWANIE ADMINA
     |  Hasło -> bcrypt hash. Hasło ustawiasz po instalacji:
     |    1) przez panel: po otwarciu panelu pojawi się ekran
     |       „Ustaw hasło administratora”,
     |    2) albo komendą: php bin/set-password.php "TwojeHaslo" --write
     |  Hash trafia do pliku config/auth.local.php (lokalnego, nie w paczce).
     | ----------------------------------------------------------------- */
    'auth' => [
        'enabled' => true,
        'user'    => 'ADMIN',
        // Pusty hash = hasło nieustawione. Panel poprosi o ustawienie hasła
        // przy pierwszym otwarciu. NIE MA ŻADNEGO HASŁA DOMYŚLNEGO.
        'password_hash' => '',
    ],
];

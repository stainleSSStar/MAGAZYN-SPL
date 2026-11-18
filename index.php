<?php
/**
 * Panel Magazynu - kontroler frontowy (front controller).
 *
 * Wszystkie trasy prowadzą przez index.php?r=...
 * Dzięki temu panel działa na każdym serwerze, bez potrzeby
 * konfiguracji .htaccess / mod_rewrite.
 */

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$config = $GLOBALS['config'];
Database::init($config['db']);

$route = qs('r', 'dashboard');

/* =========================================================================
 |  KREATOR PIERWSZEGO URUCHOMIENIA
 |
 |  Działa zanim baza jest skonfigurowana i zanim istnieje jakiekolwiek
 |  konto. Pozwala wpisać dane bazy i utworzyć pierwszego administratora
 |  bez otwierania plików na serwerze.
 | ====================================================================== */
if ($route === 'setup') {
    $error   = null;
    $notice  = null;
    $dbOk    = true;

    Database::silentErrors(true);
    try {
        Database::value('SELECT 1');
    } catch (Throwable $e) {
        $dbOk = false;
    }
    Database::silentErrors(false);

    $dbValues = [
        'host'   => (string) ($config['db']['host'] ?? 'localhost'),
        'port'   => (string) ($config['db']['port'] ?? 3306),
        'name'   => (string) ($config['db']['name'] ?? ''),
        'user'   => (string) ($config['db']['user'] ?? ''),
        'pass'   => (string) ($config['db']['pass'] ?? ''),
        'socket' => (string) ($config['db']['socket'] ?? ''),
    ];

    $needAccount = !Auth::hasAccounts();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) {
            $error = 'Sesja wygasła. Spróbuj ponownie.';
        } else {
            $section = (string) ($_POST['_sekcja'] ?? '');

            if ($section === 'baza') {
                $new = [
                    'host'   => trim((string) ($_POST['db_host'] ?? '')),
                    'port'   => (int) ($_POST['db_port'] ?? 3306),
                    'name'   => trim((string) ($_POST['db_name'] ?? '')),
                    'user'   => trim((string) ($_POST['db_user'] ?? '')),
                    'pass'   => (string) ($_POST['db_pass'] ?? ''),
                    'socket' => trim((string) ($_POST['db_socket'] ?? '')),
                    'charset' => 'utf8mb4',
                    'show_errors' => false,
                ];
                $dbValues = array_merge($dbValues, $new);

                if ($new['name'] === '' || $new['user'] === '') {
                    $error = 'Podaj nazwę bazy i użytkownika.';
                } else {
                    $test = Database::testConnection($new);
                    if (!$test['ok']) {
                        $error = 'Nie udało się połączyć z bazą: ' . $test['message'];
                    } else {
                        $written = self_write_db_local($new);
                        if (!$written) {
                            $error = 'Nie udało się zapisać pliku config/db.local.php. '
                                   . 'Sprawdź prawa do zapisu w katalogu config/ albo uzupełnij config/config.php.';
                        } else {
                            flash('success', 'Dane bazy zapisane w config/db.local.php.');
                            redirect('setup');
                        }
                    }
                }
            } elseif ($section === 'konto') {
                $res = Auth::createFirstAdmin(
                    (string) ($_POST['login'] ?? ''),
                    (string) ($_POST['name'] ?? ''),
                    (string) ($_POST['p1'] ?? ''),
                    (string) ($_POST['p2'] ?? '')
                );
                if ($res['ok']) {
                    flash('success', $res['message']);
                    redirect('login');
                }
                $error = $res['message'];
            }
        }
    }

    View::render('setup', [
        'error'      => $error,
        'notice'     => $notice,
        'dbValues'   => $dbValues,
        'dbOk'       => $dbOk,
        'needAccount'=> $needAccount,
        'appName'    => $config['app']['name'],
    ], 'none');
    exit;
}

/* =========================================================================
 |  LOGOWANIE / WYLOGOWANIE
 | ====================================================================== */
if ($route === 'login') {
    if (Auth::check()) {
        redirect('dashboard');
    }

    $error = null;
    $login = qs('u');
    if ($login === '') {
        // Podpowiedziamy login tylko wtedy, gdy w systemie jest jedno konto.
        $accounts = Users::all();
        if (count($accounts) === 1) {
            $login = (string) $accounts[0]['login'];
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_check()) {
            $error = 'Sesja wygasła. Spróbuj ponownie.';
        } else {
            $login    = trim((string) ($_POST['u'] ?? ''));
            $password = (string) ($_POST['p'] ?? '');
            if (Auth::attempt($login, $password)) {
                redirect('dashboard');
            }
            $error = Auth::isLocked()
                ? 'Zbyt wiele nieudanych prób. Spróbuj ponownie za ' . ceil(Auth::lockRemaining() / 60) . ' min.'
                : 'Nieprawidłowy login lub hasło.';
        }
    }

    View::render('login', ['error' => $error, 'login' => $login, 'appName' => $config['app']['name']], 'none');
    exit;
}

if ($route === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        flash('danger', 'Sesja wygasła - zaloguj się ponownie.');
        redirect('login');
    }
    Auth::logout();
    redirect('login');
}

/* =========================================================================
 |  POZOSTAŁE TRASY - WYMAGAJĄ LOGOWANIA
 | ====================================================================== */
// Brak połączenia z bazą - kierujemy do kreatora, żeby dało się je poprawić.
Database::silentErrors(true);
try {
    Database::value('SELECT 1');
    Database::silentErrors(false);
} catch (Throwable $e) {
    Database::silentErrors(false);
    flash('danger', 'Brak połączenia z bazą danych - uzupełnij dane w kreatorze.');
    redirect('setup');
}

// Panel bez żadnego konta prowadzi do kreatora.
if (Auth::enabled() && !Auth::hasAccounts()) {
    redirect('setup');
}

Auth::requireLogin();

switch ($route) {

    /* ---------------------------------------------------------------------
     |  ZMIANA HASŁA (każdy użytkownik zmienia własne)
     | ------------------------------------------------------------------ */
    case 'haslo':
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check()) {
                $error = 'Sesja wygasła. Spróbuj ponownie.';
            } else {
                $res = Auth::changeOwnPassword(
                    (string) ($_POST['current'] ?? ''),
                    (string) ($_POST['p1'] ?? ''),
                    (string) ($_POST['p2'] ?? '')
                );
                if ($res['ok']) {
                    flash('success', $res['message']);
                    redirect('dashboard');
                }
                $error = $res['message'];
            }
        }
        View::render('haslo', ['error' => $error]);
        break;

    /* ---------------------------------------------------------------------
     |  POMIJANIE MATERIAŁU PRZY ZAMÓWIENIU
     |
     |  Flaga panelu (nie zmienia bazy) - materiał może mieć wartość
     |  archiwalną mimo stanu poniżej zera.
     | ------------------------------------------------------------------ */
    case 'ignoruj-zakup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        if (!csrf_check()) {
            flash('danger', 'Sesja wygasła - operacja przerwana.');
            redirect('dashboard');
        }
        require_write();

        $id  = (string) ($_POST['id'] ?? '');
        $back = (string) ($_POST['back'] ?? 'dashboard');
        if ($id === '' || !preg_match('/^\d+$/', $id)) {
            flash('warning', 'Nie podano materiału.');
            redirect('dashboard');
        }

        if (PanelData::toggleIgnoredPurchase($id)) {
            flash('success', PanelData::isIgnoredPurchase($id)
                ? 'Materiał został pominięty przy zamówieniu.'
                : 'Materiał znów liczy się przy zamówieniu.');
        } else {
            flash('danger', 'Nie udało się zapisać ustawienia - sprawdź prawa do zapisu w katalogu config/.');
        }

        if ($back === 'asortyment') {
            redirect('table', ['t' => 'asortyment', 'f' => ['IDasortyment' => $id]]);
        }
        if ($back === 'record') {
            redirect('record', ['t' => 'asortyment', 'id' => $id]);
        }
        redirect('dashboard');
        break;

    /* ---------------------------------------------------------------------
     |  KONTA UŻYTKOWNIKÓW (tylko administrator)
     | ------------------------------------------------------------------ */
    case 'konta':
        Auth::requireAdmin();
        View::render('konta', ['users' => Users::all(), 'me' => Auth::user()]);
        break;

    case 'konto':
        Auth::requireAdmin();

        $error = null;
        $notice = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check()) {
                $error = 'Sesja wygasła. Spróbuj ponownie.';
            } else {
                $action = (string) ($_POST['_akcja'] ?? '');
                $login  = (string) ($_POST['login'] ?? '');

                if ($action === 'nowe') {
                    $res = Users::create(
                        $login,
                        (string) ($_POST['name'] ?? ''),
                        (string) ($_POST['haslo'] ?? ''),
                        (string) ($_POST['role'] ?? Users::ROLE_READONLY)
                    );
                } elseif ($action === 'zapisz') {
                    $res = Users::update($login, [
                        'name'   => (string) ($_POST['name'] ?? ''),
                        'role'   => (string) ($_POST['role'] ?? Users::ROLE_READONLY),
                        'active' => !empty($_POST['active']),
                    ]);
                } elseif ($action === 'haslo') {
                    $res = Users::setPassword($login, (string) ($_POST['haslo'] ?? ''));
                } elseif ($action === 'usun') {
                    $res = Users::delete($login);
                } else {
                    $res = ['ok' => false, 'message' => 'Nieznana operacja.'];
                }

                if ($res['ok']) {
                    flash('success', $res['message']);
                } else {
                    $error = $res['message'];
                }
            }
        }

        View::render('konta', ['users' => Users::all(), 'me' => Auth::user(), 'error' => $error]);
        break;

    /* ---------------------------------------------------------------------
     |  PULPIT (dashboard)
     | ------------------------------------------------------------------ */
    case 'dashboard':
        View::render('dashboard', ['stats' => Stats::overview()]);
        break;

    /* ---------------------------------------------------------------------
     |  WYMANY - dedykowany moduł z filtrowaniem
     | ------------------------------------------------------------------ */
    case 'wymiany':
        $filters = qs_filters('f');
        $preset  = qs('preset', 'all');
        $q       = qs('q');

        // --- Presety czasowe (gdy użytkownik nie ustawił własnych dat) -----
        if (!isset($filters['Data']) && $preset !== '' && $preset !== 'all') {
            $from = match ($preset) {
                '7'    => date('Y-m-d', strtotime('-7 days')),
                '30'   => date('Y-m-d', strtotime('-30 days')),
                '90'   => date('Y-m-d', strtotime('-90 days')),
                'rok'  => date('Y-01-01'),
                'mies' => date('Y-m-01'),
                default => null,
            };
            if ($from !== null) {
                $filters['Data'] = ['from' => $from];
            }
        }

        $perPage = (int) $config['app']['rows_default'];
        $sort    = 'Data';
        $dir     = 'desc';
        $page    = qs_int('page', 1, 1);

        // Preset "ostatnie 10" - pokazuje tylko dziesięć najnowszych wpisów
        if ($preset === 'last10') {
            $perPage = 10;
            $page    = 1;
        } else {
            $perPage = qs_int('per_page', $perPage, 1, (int) $config['app']['rows_max']);
            if (qs('sort') !== '') {
                $sort = qs('sort');
            }
            if (qs('dir') !== '') {
                $dir  = qs('dir') === 'asc' ? 'asc' : 'desc';
            }
        }

        $result = Repository::paginate('wymiany', [
            'q'        => $q,
            'filters'  => $filters,
            'sort'     => $sort,
            'dir'      => $dir,
            'page'     => $page,
            'per_page' => $perPage,
        ]);

        $summary = Repository::summary('wymiany', $filters, $q);

        View::render('wymiany', [
            'result'   => $result,
            'filters'  => $filters,
            'preset'   => $preset,
            'summary'  => $summary,
            'devices'  => Schema::options('drukarki', ['Lokalizacja']),
            'models'   => Schema::options('model', ['Nazwa']),
            'workers'  => Schema::options('pracownik', ['Imie', 'Nazwisko']),
            'materials'=> Schema::options('asortyment', ['Nazwa', 'Symbol']),
            'dateFrom' => $filters['Data']['from'] ?? '',
            'dateTo'   => $filters['Data']['to'] ?? '',
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  USUWANIE WIELU REKORDÓW
     |
     |  Jak przy pojedynczym rekordzie - najpierw sprawdzamy, czy wybrane
     |  pozycje są używane w innych tabelach.
     | ------------------------------------------------------------------ */
    case 'bulk-delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        if (!csrf_check()) {
            flash('danger', 'Sesja wygasła - operacja przerwana.');
            redirect('dashboard');
        }
        require_write();

        $table = (string) ($_POST['t'] ?? '');
        $ids   = $_POST['ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter(array_map('strval', $ids), static fn($v) => $v !== ''));
        if (!Schema::exists($table)) {
            flash('danger', 'Nie znaleziono tabeli.');
            redirect('dashboard');
        }
        if ($ids === []) {
            flash('warning', 'Nie zaznaczono żadnego rekordu.');
            redirect('wymiany');
        }

        // Zapamiętujemy wybór, żeby ekran potwierdzenia mógł go odczytać.
        $_SESSION['_bulk_delete'] = ['t' => $table, 'ids' => $ids];

        if (empty($_POST['_force'])) {
            $refs = Repository::references($table, $ids);
            if ($refs !== []) {
                redirect('potwierdz-usuniecie-maszowe');
            }
        }

        try {
            $count = Repository::deleteMany($table, $ids);
            unset($_SESSION['_bulk_delete']);
            flash('success', 'Usunięto ' . $count . ' rekordów.');
        } catch (Throwable $e) {
            error_log('Panel Magazyn: usuwanie masowe nieudane - ' . $e->getMessage());
            flash('danger', 'Nie udało się usunąć rekordów. ' . self_error($e));
        }
        redirect('table', ['t' => $table]);
        break;

    /* ---------------------------------------------------------------------
     |  POTWIERDZENIE USUNIĘCIA MASOWEGO
     | ------------------------------------------------------------------ */
    case 'potwierdz-usuniecie-maszowe':
        $bulk = $_SESSION['_bulk_delete'] ?? null;
        if (!is_array($bulk) || empty($bulk['ids'])) {
            flash('warning', 'Nie ma zaznaczonych rekordów do usunięcia.');
            redirect('dashboard');
        }
        View::render('potwierdz-usuniecie-maszowe', [
            'table'   => (string) $bulk['t'],
            'ids'     => array_map('strval', $bulk['ids']),
            'refs'    => Repository::references((string) $bulk['t'], array_map('strval', $bulk['ids'])),
            'meta'    => Schema::meta((string) $bulk['t']),
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  LISTA REKORDÓW TABELI
     | ------------------------------------------------------------------ */
    case 'table':
        $table = qs('t', 'wymiany');
        if (!Schema::exists($table)) {
            flash('danger', 'Tabela „' . $table . '” nie istnieje w bazie.');
            redirect('dashboard');
        }

        $result = Repository::paginate($table, [
            'q'       => qs('q'),
            'filters' => qs_filters('f'),
            'sort'    => qs('sort'),
            'dir'     => qs('dir', 'asc'),
            'page'    => qs_int('page', 1, 1),
            'per_page'=> qs_int('per_page', (int) $config['app']['rows_default'], 1, (int) $config['app']['rows_max']),
        ]);

        View::render('table', [
            'table'   => $table,
            'meta'    => Schema::meta($table),
            'result'  => $result,
            'summary' => Repository::summary($table, qs_filters('f'), qs('q')),
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  SZCZEGÓŁY REKORDU
     | ------------------------------------------------------------------ */
    case 'record':
        $table = qs('t', 'wymiany');
        $id    = qs('id');
        if (!Schema::exists($table)) {
            flash('danger', 'Tabela „' . $table . '” nie istnieje w bazie.');
            redirect('dashboard');
        }
        $row = Repository::find($table, $id);
        if ($row === null) {
            flash('danger', 'Nie znaleziono rekordu.');
            redirect('table', ['t' => $table]);
        }
        View::render('record', [
            'table'    => $table,
            'meta'     => Schema::meta($table),
            'row'      => $row,
            'related'  => Repository::relatedCounts($table, $id),
            'fkValues' => FkResolver::resolve($table, $row),
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  DODAWANIE / EDYCJA REKORDU
     | ------------------------------------------------------------------ */
    case 'form':
        $table = qs('t', 'wymiany');
        if (!Schema::exists($table)) {
            flash('danger', 'Tabela „' . $table . '” nie istnieje w bazie.');
            redirect('dashboard');
        }

        $meta      = Schema::meta($table);
        $editingId = qs('id');
        $isEdit    = $editingId !== '';
        $row       = $isEdit ? Repository::find($table, $editingId) : null;

        if ($isEdit && $row === null) {
            flash('danger', 'Nie znaleziono rekordu do edycji.');
            redirect('table', ['t' => $table]);
        }

        $errors = [];
        $values = $row ?? [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_check()) {
                $errors['_'] = 'Sesja wygasła. Zatwierdź formularz ponownie.';
            } else {
                require_write();
                [$data, $errors] = Validator::validate($table, $_POST, $isEdit ? $editingId : null);

                if ($errors === []) {
                    try {
                        if ($isEdit) {
                            Repository::update($table, $editingId, $data);
                            $propagated = self_propagate($table, $editingId, (array) $row, $data);
                            flash('success', $propagated > 0
                                ? 'Rekord został zaktualizowany. Model przepisano w ' . $propagated . ' wymianach tego urządzenia.'
                                : 'Rekord został zaktualizowany.');
                            redirect('record', ['t' => $table, 'id' => $editingId]);
                        }
                        Repository::insert($table, $data);
                        flash('success', 'Rekord został dodany.');
                        if (!empty($_POST['_again'])) {
                            redirect('form', ['t' => $table]);
                        }
                        redirect('table', ['t' => $table]);
                    } catch (Throwable $e) {
                        error_log('Panel Magazyn: zapis nieudany - ' . $e->getMessage());
                        $errors['_'] = 'Nie udało się zapisać rekordu. ' . self_error($e);
                        $values = $_POST;
                    }
                } else {
                    $values = $_POST;
                }
            }
        }

        // Podpowiedź: następny wolny numer dla tabel bez AUTO_INCREMENT
        $suggestedId = null;
        if (!$isEdit) {
            $pk = $meta['pk'];
            if ($pk !== '' && !$meta['columns'][$pk]['auto_increment']) {
                $suggestedId = Repository::nextId($table);
            }

            // Pre-wypełnienie z GET (np. szybkie dodanie wymiany dla wybranego urządzenia)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                foreach ($meta['columns'] as $cName => $col) {
                    if (isset($_GET[$cName]) && is_scalar($_GET[$cName]) && (string) $_GET[$cName] !== '') {
                        $values[$cName] = (string) $_GET[$cName];
                    }
                }

                // Wskazanie urządzenia ustawia też model (działa bez JS, jak i w JS)
                foreach ($meta['columns'] as $cName => $col) {
                    $modelCol = $col['set_model'] ?? null;
                    if (!$modelCol || empty($values[$cName])) {
                        continue;
                    }
                    if (isset($values[$modelCol]) && $values[$modelCol] !== '') {
                        continue;
                    }
                    $ref = $col['ref'] ?? null;
                    if (!$ref) {
                        continue;
                    }
                    $modelId = FkResolver::linkedValue(
                        $ref,
                        (string) ($col['ref_col'] ?? Schema::pk($ref)),
                        (string) $values[$cName],
                        $modelCol
                    );
                    if ($modelId !== null && $modelId !== '') {
                        $values[$modelCol] = (string) $modelId;
                    }
                }
            }

            // Domyślna data = dzisiaj (dla kolumn typu date)
            foreach ($meta['columns'] as $cName => $col) {
                if ($col['type'] === 'date' && !isset($values[$cName])) {
                    $values[$cName] = date('Y-m-d');
                }
            }
        }

        // Opcja "przepisz wartość także w powiązanych rekordach" (np. model we wymianach)
        $propagate = [];
        if ($isEdit) {
            foreach ($meta['columns'] as $cName => $col) {
                $p = $col['propagate_to'] ?? null;
                if (!$p || !Schema::exists($p['table'] ?? '')) {
                    continue;
                }
                try {
                    $count = (int) Database::value(
                        'SELECT COUNT(*) FROM ' . Schema::qi($p['table'])
                        . ' WHERE ' . Schema::qi($p['key']) . ' = :id',
                        ['id' => $editingId]
                    );
                } catch (Throwable $e) {
                    continue;
                }
                $propagate[$cName] = [
                    'count' => $count,
                    'label' => Schema::meta($p['table'])['columns'][$p['column']]['label'] ?? $p['column'],
                ];
            }
        }

        View::render('form', [
            'table'       => $table,
            'meta'        => $meta,
            'row'         => $row,
            'values'      => $values,
            'errors'      => $errors,
            'isEdit'      => $isEdit,
            'suggestedId' => $suggestedId,
            'options'     => FkResolver::optionsFor($table),
            'propagate'   => $propagate,
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  USUWANIE REKORDU
     |
     |  Rekord używany w innych tabelach nie znika bez potwierdzenia -
     |  najpierw pokazujemy, co na niego wskazuje.
     | ------------------------------------------------------------------ */
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        if (!csrf_check()) {
            flash('danger', 'Sesja wygasła - operacja przerwana.');
            redirect('dashboard');
        }
        require_write();

        $table = (string) ($_POST['t'] ?? qs('t'));
        $id    = (string) ($_POST['id'] ?? qs('id'));
        if (!Schema::exists($table)) {
            flash('danger', 'Nie znaleziono tabeli.');
            redirect('dashboard');
        }

        // Bez wymuszonego potwierdzenia - najpierw sprawdzamy powiązania.
        if (empty($_POST['_force'])) {
            $refs = Repository::references($table, [$id]);
            if ($refs !== []) {
                redirect('potwierdz-usuniecie', ['t' => $table, 'id' => $id]);
            }
        }

        try {
            Repository::delete($table, $id);
            flash('success', 'Rekord został usunięty.');
        } catch (Throwable $e) {
            error_log('Panel Magazyn: usuwanie nieudane - ' . $e->getMessage());
            $refs = Repository::references($table, [$id]);
            $blocked = false;
            foreach ($refs as $r) {
                if ($r['blocked']) {
                    $blocked = true;
                }
            }
            flash('danger', $blocked
                ? 'Nie udało się usunąć rekordu - jest używany w innych tabelach i baza tego nie dopuszcza. '
                  . 'Najpierw usuń lub zmień powiązane rekordy.'
                : 'Nie udało się usunąć rekordu. ' . self_error($e));
        }
        redirect('table', ['t' => $table]);
        break;

    /* ---------------------------------------------------------------------
     |  POTWIERDZENIE USUNIĘCIA REKORDU UŻYWANEGO W INNYCH TABELACH
     | ------------------------------------------------------------------ */
    case 'potwierdz-usuniecie':
        $table = qs('t');
        $id    = qs('id');
        if (!Schema::exists($table)) {
            flash('danger', 'Nie znaleziono tabeli.');
            redirect('dashboard');
        }
        $row  = Repository::find($table, $id);
        $refs = Repository::references($table, [$id]);
        View::render('potwierdz-usuniecie', [
            'table' => $table,
            'row'   => $row,
            'refs'  => $refs,
            'meta'  => Schema::meta($table),
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  URZĄDZENIA (drukarki) - wyszukiwarka
     | ------------------------------------------------------------------ */
    case 'devices':
        $result = Repository::paginate('drukarki', [
            'q'       => qs('q'),
            'filters' => qs_filters('f'),
            'sort'    => qs('sort'),
            'dir'     => qs('dir', 'asc'),
            'page'    => qs_int('page', 1, 1),
            'per_page'=> qs_int('per_page', (int) $config['app']['rows_default'], 1, (int) $config['app']['rows_max']),
        ]);

        $modelOptions = Schema::options('model');

        // Liczba wymian dla każdego urządzenia (jedno zapytanie)
        $wymianyCount = [];
        foreach (Database::all('SELECT IDdrukarki, COUNT(*) AS n FROM wymiany GROUP BY IDdrukarki') as $row) {
            $wymianyCount[(string) $row['IDdrukarki']] = (int) $row['n'];
        }
        foreach ($result['rows'] as &$deviceRow) {
            $deviceRow['__wymiany'] = $wymianyCount[(string) $deviceRow['IDdrukarki']] ?? 0;
        }
        unset($deviceRow);

        View::render('devices', [
            'result'       => $result,
            'modelOptions' => $modelOptions,
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  KARTA URZĄDZENIA + HISTORIA WYMIAN
     | ------------------------------------------------------------------ */
    case 'device':
        $id  = qs('id');
        $dev = Repository::find('drukarki', $id);
        if ($dev === null) {
            flash('danger', 'Nie znaleziono urządzenia.');
            redirect('devices');
        }

        $history = Repository::paginate('wymiany', [
            'q'        => qs('q'),
            'filters'  => qs_str_array('f'),
            'sort'     => qs('sort', 'Data'),
            'dir'      => qs('dir', 'desc'),
            'page'     => qs_int('page', 1, 1),
            'per_page' => qs_int('per_page', 25, 1, (int) $config['app']['rows_max']),
            'where_extra' => ['IDdrukarki = :dev', ['dev' => $id]],
        ]);

        $materials = Database::all(
            'SELECT a.*, m.Nazwa AS ModelNazwa FROM asortyment a
             LEFT JOIN model m ON m.IDmodel = a.IDmodel
             WHERE a.IDmodel = :mid ORDER BY a.Nazwa',
            ['mid' => $dev['IDmodel'] ?? -1]
        );

        View::render('device', [
            'device'      => $dev,
            'history'     => $history,
            'materials'   => $materials,
            'stats'       => Stats::forDevice($id),
            'pracownicy'  => Schema::options('pracownik'),
        ]);
        break;

    /* ---------------------------------------------------------------------
     |  WYSZUKAWARKA GLOBALNA
     | ------------------------------------------------------------------ */
    case 'search':
        $term = qs('q');
        $hits = [];
        if ($term !== '') {
            foreach (array_keys(Schema::tables()) as $t) {
                $res = Repository::paginate($t, [
                    'q'        => $term,
                    'page'     => 1,
                    'per_page' => 10,
                ]);
                if ($res['total'] > 0) {
                    $hits[$t] = $res;
                }
            }
        }
        View::render('search', ['term' => $term, 'hits' => $hits]);
        break;

    /* ---------------------------------------------------------------------
     |  STRUKTURA BAZY
     | ------------------------------------------------------------------ */
    case 'structure':
        $tables = [];
        foreach (Schema::tables() as $name => $info) {
            $tables[$name] = [
                'info'    => $info,
                'columns' => Schema::columns($name),
            ];
        }
        View::render('structure', ['tables' => $tables]);
        break;

    /* ---------------------------------------------------------------------
     |  EKSPORT CSV
     | ------------------------------------------------------------------ */
    case 'export':
        $table = qs('t', 'wymiany');
        if (!Schema::exists($table)) {
            flash('danger', 'Nie znaleziono tabeli.');
            redirect('dashboard');
        }
        $result = Repository::paginate($table, [
            'q'       => qs('q'),
            'filters' => qs_filters('f'),
            'sort'    => qs('sort'),
            'dir'     => qs('dir', 'asc'),
            'page'    => 1,
            'per_page'=> 100000,
        ]);

        $filename = $table . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM - Excel poprawnie odczyta UTF-8
        $cols = array_keys(Schema::columns($table));
        fputcsv($out, $cols, ';', '"', '\\');
        foreach ($result['rows'] as $row) {
            $line = [];
            foreach ($cols as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($out, $line, ';', '"', '\\');
        }
        fclose($out);
        exit;

    /* ---------------------------------------------------------------------
     |  NIEZNANA TRASA
     | ------------------------------------------------------------------ */
    default:
        http_response_code(404);
        View::render('error', [
            'title'   => 'Nie znaleziono strony',
            'message' => 'Trasa „' . $route . '” nie istnieje.',
        ]);
        break;
}

/**
 * Zapisuje dane bazy do pliku config/db.local.php (kreator uruchomienia).
 */
function self_write_db_local(array $db): bool
{
    // index.php leży w katalogu głównym panelu, więc config/ jest obok niego.
    $path = __DIR__ . '/config/db.local.php';
    $dir  = dirname($path);

    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }

    $content = "<?php\n"
        . "/**\n"
        . " * Dane bazy danych - zapisane z kreatora pierwszego uruchomienia panelu.\n"
        . " * Ten plik jest lokalny - NIE jest dołączany do paczki z panelem.\n"
        . " */\n\n"
        . "return [\n"
        . "    'db' => [\n"
        . "        'host'        => " . var_export((string) $db['host'], true) . ",\n"
        . "        'port'        => " . var_export((int) $db['port'], true) . ",\n"
        . "        'name'        => " . var_export((string) $db['name'], true) . ",\n"
        . "        'user'        => " . var_export((string) $db['user'], true) . ",\n"
        . "        'pass'        => " . var_export((string) $db['pass'], true) . ",\n"
        . "        'charset'     => 'utf8mb4',\n"
        . "        'socket'      => " . var_export((string) ($db['socket'] ?? ''), true) . ",\n"
        . "        'show_errors' => false,\n"
        . "    ],\n"
        . "];\n";

    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    @chmod($path, 0644);

    return true;
}

/**
 * Bezpieczny komunikat błędu bazy (bez haseł i wrażliwych danych).
 */
function self_error(Throwable $e): string
{
    if (!empty($GLOBALS['config']['db']['show_errors'])) {
        return 'Kod błędu: ' . $e->getCode() . ' (' . $e->getMessage() . ')';
    }
    return 'Kod błędu: ' . $e->getCode() . '. Szczegóły znajdziesz w logu serwera.';
}

/**
 * Przepisuje zmienioną wartość kolumny także w powiązanych rekordach.
 *
 * Dotyczy kolumn opisanych w metadanych (propagate_to) - obecnie model
 * urządzenia -> model w wymianach tego urządzenia. Działa tylko wtedy,
 * gdy użytkownik zaznaczył odpowiedni checkbox w formularzu.
 *
 * @return int liczba zaktualizowanych rekordów
 */
function self_propagate(string $table, string $id, array $old, array $new): int
{
    $total = 0;

    foreach (Schema::meta($table)['columns'] as $cName => $col) {
        $p = $col['propagate_to'] ?? null;
        if (!$p) {
            continue;
        }
        if (empty($_POST['_propagate_' . $cName])) {
            continue;
        }

        $oldVal = (string) ($old[$cName] ?? '');
        $newVal = (string) ($new[$cName] ?? '');
        if ($oldVal === $newVal || $newVal === '') {
            continue;
        }

        $target = (string) ($p['table'] ?? '');
        $tcol   = (string) ($p['column'] ?? '');
        $key    = (string) ($p['key'] ?? '');
        if ($target === '' || $tcol === '' || $key === '' || !Schema::exists($target)) {
            continue;
        }

        try {
            $total += Database::execute(
                'UPDATE ' . Schema::qi($target) . ' SET ' . Schema::qi($tcol) . ' = :v'
                . ' WHERE ' . Schema::qi($key) . ' = :id',
                ['v' => $newVal, 'id' => $id]
            );
        } catch (Throwable $e) {
            error_log('Panel Magazyn: propagacja nieudana - ' . $e->getMessage());
        }
    }

    return $total;
}

<?php
/**
 * Warstwa metadanych schematu.
 *
 *  Panel NIE ma zahartowanej listy tabel - rozpoznaje je automatycznie
 *  w bazie danych (INFORMATION_SCHEMA). Dla znanych tabel w bazie
 *  podajemy ludzkie opisy w Polish (etykiety, opisy pól, typy formularzy).
 *
 *  Dodanie nowej tabeli do bazy = rekord w $static poniżej LUB zero linijek
 *  kodu (panel sam ją odczyta i wystawi do przeglądania).
 */

declare(strict_types=1);

final class Schema
{
    /** @var array<string,array<string,mixed>> Opisy ręczne znanych tabel. */
    private const STATIC = [
        'model' => [
            'label'       => 'Modele urządzeń',
            'label_one'   => 'Model urządzenia',
            'icon'        => '🖨️',
            'description' => 'Słownik modeli drukarek i innych urządzeń biurowych. Model łączy urządzenia z materiałami eksploatacyjnymi.',
            'columns' => [
                'IDmodel' => [
                    'label' => 'ID modelu',
                    'desc'  => 'Numer identyfikacyjny modelu. Nadawany automatycznie — nie trzeba go wpisywać.',
                ],
                'Nazwa' => [
                    'label'     => 'Nazwa modelu',
                    'desc'      => 'Pełna nazwa modelu urządzenia, np. „OKI B431” lub „Ricoh MP 2352”.',
                    'required'  => true,
                    'searchable'=> true,
                ],
            ],
        ],

        'drukarki' => [
            'label'       => 'Drukarki i urządzenia',
            'label_one'   => 'Urządzenie',
            'icon'        => '🖨️',
            'description' => 'Rejestr urządzeń drukujących: lokalizacja, adres IP i model. To tabela główna dla wyszukiwania po drukarce.',
            'columns' => [
                'IDdrukarki' => [
                    'label' => 'ID urządzenia',
                    'desc'  => 'Unikalny numer urządzenia. Przy dodawaniu podaj następny wolny numer (podpowiedź pojawi się automatycznie).',
                ],
                'Lokalizacja' => [
                    'label'     => 'Lokalizacja',
                    'desc'      => 'Nazwa miejsca, pokoju lub działu, np. „Biuro 301”, „Magazyn”.',
                    'required'  => true,
                    'searchable'=> true,
                ],
                'Adres_IP' => [
                    'label'     => 'Adres IP',
                    'desc'      => 'Adres IPv4 urządzenia w sieci lokalnej, np. 192.168.1.50.',
                    'searchable'=> true,
                    'type'      => 'ip',
                ],
                'INFO' => [
                    'label'     => 'Uwagi',
                    'desc'      => 'Dodatkowe informacje o urządzeniu, np. „brak”, „Xerrex”, status serwisowy.',
                    'searchable'=> true,
                ],
                'IDmodel' => [
                    'label'      => 'Model',
                    'desc'       => 'Model urządzenia — wybierz z listy modeli.',
                    'ref'        => 'model',
                    'ref_display'=> ['Nazwa'],
                    'searchable'=> true,
                    // Po zmianie modelu można jednym kliknięciem przepisać model
                    // także we wszystkich wymianach tego urządzenia.
                    'propagate_to' => [
                        'table'  => 'wymiany',
                        'column' => 'IDmodel',
                        'key'    => 'IDdrukarki',
                    ],
                ],
            ],
        ],

        'asortyment' => [
            'label'       => 'Asortyment (materiały)',
            'label_one'   => 'Materiał',
            'icon'        => '📦',
            'description' => 'Materiały eksploatacyjne: tonery, bębny, kasety. Przypisane do modeli urządzeń.',
            'columns' => [
                'IDasortyment' => [
                    'label' => 'ID materiału',
                    'desc'  => 'Numer materiału w magazynie. Przy dodawaniu podaj następny wolny numer.',
                ],
                'Nazwa' => [
                    'label'     => 'Nazwa materiału',
                    'desc'      => 'Nazwa toneru, bębna lub innego materiału, np. „Toner czarny OKI B431”.',
                    'required'  => true,
                    'searchable'=> true,
                ],
                'Symbol' => [
                    'label'     => 'Symbol / kod',
                    'desc'      => 'Symbol katalogowy producenta, np. 44917602 albo MX-61GTBA.',
                    'searchable'=> true,
                ],
                'Ilosc' => [
                    'label' => 'Stan magazynowy',
                    'desc'  => 'Liczba sztuk na stanie. Wartość ujemna oznacza stan poniżej zera (deficyt).',
                    'type'  => 'int',
                ],
                'IDmodel' => [
                    'label'      => 'Model',
                    'desc'       => 'Model urządzenia, do którego pasuje ten materiał.',
                    'ref'        => 'model',
                    'ref_display'=> ['Nazwa'],
                    'searchable'=> true,
                ],
            ],
        ],

        'pracownik' => [
            'label'       => 'Pracownicy',
            'label_one'   => 'Pracownik',
            'icon'        => '👷',
            'description' => 'Lista osób wykonujących wymiany materiałów.',
            'columns' => [
                'IDpracownik' => [
                    'label' => 'ID pracownika',
                    'desc'  => 'Numer pracownika. Przy dodawaniu podaj następny wolny numer.',
                ],
                'Imie' => [
                    'label'     => 'Imię',
                    'desc'      => 'Imię pracownika.',
                    'required'  => true,
                    'searchable'=> true,
                ],
                'Nazwisko' => [
                    'label'     => 'Nazwisko',
                    'desc'      => 'Nazwisko pracownika.',
                    'required'  => true,
                    'searchable'=> true,
                ],
            ],
        ],

        'wymiany' => [
            'label'       => 'Wymiany materiałów',
            'label_one'   => 'Wymiana',
            'icon'        => '🔄',
            'description' => 'Historia wymiany materiałów eksploatacyjnych: data, urządzenie, model, materiał, pracownik i licznik.',
            'columns' => [
                'IDwymiany' => [
                    'label' => 'ID wymiany',
                    'desc'  => 'Numer wpisu w historii. Nadawany automatycznie.',
                ],
                'Data' => [
                    'label'     => 'Data wymiany',
                    'desc'      => 'Data wykonania wymiany w formacie RRRR-MM-DD.',
                    'type'      => 'date',
                    'required'  => true,
                    'searchable'=> true,
                ],
                'Licznik' => [
                    'label' => 'Licznik',
                    'desc'  => 'Stan licznika drukarki w chwili wymiany (liczba wydrukowanych stron).',
                    'type'  => 'int',
                ],
                'IDmodel' => [
                    'label'      => 'Model',
                    'desc'       => 'Model urządzenia, w którym wykonano wymianę.',
                    'ref'        => 'model',
                    'ref_display'=> ['Nazwa'],
                    'required'  => true,
                    'searchable'=> true,
                ],
                'IDdrukarki' => [
                    'label'      => 'Urządzenie',
                    'desc'       => 'Drukarka lub inne urządzenie, w którym wykonano wymianę. Wybór urządzenia ustawia automatycznie model i zawęża listę materiałów.',
                    'ref'        => 'drukarki',
                    'ref_display'=> ['Lokalizacja'],
                    'searchable'=> true,
                    'set_model'  => 'IDmodel',
                ],
                'IDasortyment' => [
                    'label'      => 'Materiał',
                    'desc'       => 'Wymieniony materiał eksploatacyjny (toner, bęben, kaseta). Lista zawęża się do modelu wybranego wyżej.',
                    'ref'        => 'asortyment',
                    'ref_display'=> ['Nazwa', 'Symbol'],
                    'searchable'=> true,
                    'filter_by'  => 'IDmodel',
                ],
                'IDpracownik' => [
                    'label'      => 'Pracownik',
                    'desc'       => 'Osoba, która wykonała wymianę.',
                    'ref'        => 'pracownik',
                    'ref_display'=> ['Imie', 'Nazwisko'],
                    'searchable'=> true,
                ],
                'Info' => [
                    'label'     => 'Uwagi',
                    'desc'      => 'Dodatkowe informacje, np. „1 reset”, uwagi o pojemnikach.',
                    'type'      => 'textarea',
                    'searchable'=> true,
                ],
            ],
        ],
    ];

    /** @var array<string,array<string,mixed>> Cache introspekcji. */
    private static array $cache = [];

    /* ---------------------------------------------------------------------
     |  Podstawowe informacje o tabelach
     | -------------------------------------------------------------------- */

    /** @return array<string,array<string,mixed>> */
    public static function tables(): array
    {
        $rows = Database::all(
            "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
             ORDER BY TABLE_NAME"
        );

        $out = [];
        foreach ($rows as $row) {
            $name = (string) $row['name'];
            $meta = self::meta($name);
            $out[$name] = [
                'label'       => $meta['label'],
                'label_one'   => $meta['label_one'],
                'icon'        => $meta['icon'],
                'description' => $meta['description'],
                'count'       => self::count($name),
            ];
        }

        // Znane tabele pokazujemy na górze (w kolejności logicznej)
        $ordered = [];
        foreach (array_keys(self::STATIC) as $name) {
            if (isset($out[$name])) {
                $ordered[$name] = $out[$name];
            }
        }
        foreach ($out as $name => $info) {
            if (!isset($ordered[$name])) {
                $ordered[$name] = $info;
            }
        }

        return $ordered;
    }

    public static function exists(string $table): bool
    {
        return array_key_exists($table, self::tables());
    }

    public static function label(string $table): string
    {
        return self::meta($table)['label'];
    }

    public static function labelOne(string $table): string
    {
        return self::meta($table)['label_one'];
    }

    /** @return array<string,mixed> */
    public static function meta(string $table): array
    {
        if (isset(self::$cache[$table])) {
            return self::$cache[$table];
        }

        $static   = self::STATIC[$table] ?? [];
        $columns  = self::introspectColumns($table);
        $foreigns = self::introspectForeignKeys($table);

        if ($columns === []) {
            // Tabela nie istnieje lub jest pusta/nieczytelna
            return self::$cache[$table] = [
                'label'       => $static['label'] ?? ucfirst($table),
                'label_one'   => $static['label_one'] ?? (ucfirst($table)),
                'icon'        => $static['icon'] ?? '🗂️',
                'description' => $static['description'] ?? 'Tabela wykryta automatycznie w bazie danych.',
                'pk'          => '',
                'columns'     => [],
                'exists'      => false,
            ];
        }

        $out = [];

        foreach ($columns as $col) {
            $name     = $col['name'];
            $scol     = $static['columns'][$name] ?? [];
            $isPk     = $col['key'] === 'PRI';
            $auto     = str_contains(strtolower((string) $col['extra']), 'auto_increment');

            $out[$name] = [
                'name'            => $name,
                'label'           => $scol['label'] ?? self::humanize($name),
                'desc'            => $scol['desc'] ?? '',
                'type'            => $scol['type'] ?? self::mapType($col['type']),
                'db_type'         => $col['db_type'],
                'nullable'        => $col['nullable'],
                'required'        => $scol['required'] ?? (!$col['nullable'] && !$auto),
                'pk'              => $isPk,
                'auto_increment'  => $auto,
                'max_length'      => $col['max_length'],
                'default'         => $col['default'],
                'ref'             => $scol['ref'] ?? ($foreigns[$name]['table'] ?? null),
                'ref_col'         => $foreigns[$name]['column'] ?? null,
                'on_delete'       => $foreigns[$name]['on_delete'] ?? 'RESTRICT',
                'ref_display'     => $scol['ref_display'] ?? null,
                'filter_by'       => $scol['filter_by'] ?? null,
                'set_model'       => $scol['set_model'] ?? null,
                'propagate_to'    => $scol['propagate_to'] ?? null,
                'searchable'      => $scol['searchable'] ?? self::defaultSearchable($col['type'], $name),
                'readonly'        => $auto,
            ];
        }

        $pk = '';
        foreach ($out as $name => $col) {
            if ($col['pk']) {
                $pk = $name;
                break;
            }
        }

        return self::$cache[$table] = [
            'label'       => $static['label'] ?? ucfirst($table),
            'label_one'   => $static['label_one'] ?? ucfirst($table),
            'icon'        => $static['icon'] ?? '🗂️',
            'description' => $static['description'] ?? 'Tabela wykryta automatycznie w bazie danych.',
            'pk'          => $pk,
            'columns'     => $out,
            'exists'      => true,
        ];
    }

    /** @return array<string,array<string,mixed>> */
    public static function columns(string $table): array
    {
        return self::meta($table)['columns'];
    }

    public static function pk(string $table): string
    {
        return (string) self::meta($table)['pk'];
    }

    /** Kolumny, w których szukamy frazą. */
    public static function searchableColumns(string $table): array
    {
        $out = [];
        foreach (self::columns($table) as $name => $col) {
            if ($col['searchable'] && !$col['pk'] && !$col['auto_increment']) {
                $out[] = $name;
            }
        }
        return $out;
    }

    /**
     * Lista opcji dla pola klucza obcego: [klucz => 'etykieta'].
     *
     * @param array<int,string>|string|null $displayCols kolumny do pokazania w etykiecie
     * @return array<int|string,string>
     */
    public static function options(string $table, array|string|null $displayCols = null): array
    {
        $out = [];
        foreach (self::optionsDetailed($table, $displayCols) as $pk => $info) {
            $out[$pk] = $info['label'];
        }
        return $out;
    }

    /**
     * Lista opcji wraz z kontekstem modelu.
     *
     * Zwraca:
     *   [pk => [
     *       'label'      => 'Toner czarny (45807111) - OKI B432',
     *       'model'      => 2,          // ID modelu (null gdy brak)
     *       'model_name' => 'OKI B432',  // nazwa modelu (null gdy brak)
     *   ]]
     *
     * Dzięki temu formularz może zawęzić listę materiałów do modelu
     * wybranego urządzenia - w bazie nazwy tonerów się powtarzają.
     *
     * @param array<int,string>|string|null $displayCols
     * @return array<int|string,array{label:string,model:mixed,model_name:mixed}>
     */
    public static function optionsDetailed(string $table, array|string|null $displayCols = null): array
    {
        $meta = self::meta($table);
        if ($meta['columns'] === []) {
            return [];
        }

        $pk = $meta['pk'] ?: (array_key_first($meta['columns']) ?? '');
        $cols = $displayCols !== null
            ? (array) $displayCols
            : self::defaultDisplayColumns($table, $meta);

        $valid = [];
        foreach ($cols as $c) {
            if (isset($meta['columns'][$c])) {
                $valid[] = $c;
            }
        }
        if ($valid === []) {
            $valid = [$pk];
        }

        // Kolumna wskazująca na model (np. asortyment.IDmodel)
        $modelCol = null;
        foreach ($meta['columns'] as $cName => $col) {
            if (($col['ref'] ?? null) === 'model') {
                $modelCol = $cName;
                break;
            }
        }

        $sel = array_map(static fn (string $c): string => self::qi($c), $valid);
        $sql = 'SELECT ' . self::qi($pk) . ' AS __pk, ' . implode(', ', $sel);
        $sql .= $modelCol !== null ? ', ' . self::qi($modelCol) . ' AS __model' : '';
        $sql .= ' FROM ' . self::qi($table);
        $sql .= ' ORDER BY ' . implode(', ', $sel);

        $rows = Database::all($sql);

        // Nazwy modeli (jedno zapytanie, a nie osobno dla każdego rekordu)
        $modelNames = [];
        if ($modelCol !== null) {
            foreach (Database::all('SELECT IDmodel AS id, Nazwa AS nazwa FROM model') as $mr) {
                $modelNames[(string) $mr['id']] = (string) $mr['nazwa'];
            }
        }

        $out = [];
        foreach ($rows as $row) {
            $parts = [];
            foreach ($valid as $c) {
                $val = trim((string) ($row[$c] ?? ''));
                if ($val === '') {
                    continue;
                }
                // Symbol / kod w nawiasie - odróżnia powtórzone nazwy tonerów
                $parts[] = (stripos($c, 'symbol') !== false || stripos($c, 'kod') !== false)
                    ? '(' . $val . ')'
                    : $val;
            }

            $modelId   = $modelCol !== null ? ($row['__model'] ?? null) : null;
            $modelName = ($modelId !== null && $modelId !== '')
                ? ($modelNames[(string) $modelId] ?? null)
                : null;

            $label = trim(implode(' ', $parts));
            if ($modelName !== null && $modelName !== '') {
                $label .= ' - ' . $modelName;
            }
            if ($label === '') {
                $label = '—';
            }

            $out[(string) $row['__pk']] = [
                'label'      => $label,
                'model'      => $modelId,
                'model_name' => $modelName,
                'has_model'  => $modelCol !== null,
            ];
        }

        return $out;
    }

    /** Opis kolumn pokazywany w nagłówkach i opisach pól. */
    public static function describeColumn(string $table, string $column): string
    {
        $cols = self::columns($table);
        return $cols[$column]['desc'] ?? '';
    }

    /* ---------------------------------------------------------------------
     |  Introspekcja
     | -------------------------------------------------------------------- */

    /** @return array<int,array<string,mixed>> */
    private static function introspectColumns(string $table): array
    {
        try {
            $rows = Database::all(
                "SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY,
                        EXTRA, CHARACTER_MAXIMUM_LENGTH, COLUMN_DEFAULT
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                 ORDER BY ORDINAL_POSITION",
                [$table]
            );
        } catch (Throwable $e) {
            error_log('Panel Magazyn: introspekcja tabeli ' . $table . ' - ' . $e->getMessage());
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name'        => (string) $r['COLUMN_NAME'],
                'type'        => (string) $r['DATA_TYPE'],
                'db_type'     => (string) $r['COLUMN_TYPE'],
                'nullable'    => $r['IS_NULLABLE'] === 'YES',
                'key'         => (string) $r['COLUMN_KEY'],
                'extra'       => (string) $r['EXTRA'],
                'max_length'  => $r['CHARACTER_MAXIMUM_LENGTH'] !== null ? (int) $r['CHARACTER_MAXIMUM_LENGTH'] : null,
                'default'     => $r['COLUMN_DEFAULT'],
            ];
        }

        return $out;
    }

    /** @return array<string,array{table:string,column:string,on_delete:string}> */
    private static function introspectForeignKeys(string $table): array
    {
        try {
            $rows = Database::all(
                "SELECT k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME,
                        COALESCE(r.DELETE_RULE, 'RESTRICT') AS DELETE_RULE
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
                 LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r
                        ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                       AND r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
                 WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME = ? AND k.REFERENCED_TABLE_NAME IS NOT NULL",
                [$table]
            );
        } catch (Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            $out[(string) $r['COLUMN_NAME']] = [
                'table'     => (string) $r['REFERENCED_TABLE_NAME'],
                'column'    => (string) $r['REFERENCED_COLUMN_NAME'],
                // Co baza zrobi z powiązanymi rekordami przy usunięciu rodzica:
                // RESTRICT - blokuje usunięcie, SET NULL - zeruje powiązanie,
                // CASCADE - usuwa razem z rodzicem.
                'on_delete' => strtoupper((string) $r['DELETE_RULE']),
            ];
        }

        return $out;
    }

    public static function count(string $table): int
    {
        try {
            return (int) Database::value('SELECT COUNT(*) FROM ' . self::qi($table));
        } catch (Throwable $e) {
            error_log('Panel Magazyn: nie udało się policzyć rekordów w ' . $table . ' - ' . $e->getMessage());
            return 0;
        }
    }

    /* ---------------------------------------------------------------------
     |  Pomocnicze
     | -------------------------------------------------------------------- */

    /** @param array<string,mixed> $meta */
    private static function defaultDisplayColumns(string $table, array $meta): array
    {
        $preferred = [
            'model'      => ['Nazwa'],
            'drukarki'   => ['Lokalizacja'],
            'asortyment' => ['Nazwa'],
            'pracownik'  => ['Imie', 'Nazwisko'],
            'wymiany'    => ['Data'],
        ];

        if (isset($preferred[$table])) {
            return $preferred[$table];
        }

        $out = [];
        foreach ($meta['columns'] as $name => $col) {
            if (!$col['pk'] && !$col['auto_increment']) {
                $out[] = $name;
                if (count($out) === 2) {
                    break;
                }
            }
        }

        return $out;
    }

    private static function mapType(string $dbType): string
    {
        return match ($dbType) {
            'int', 'tinyint', 'smallint', 'mediumint', 'bigint' => 'int',
            'decimal', 'float', 'double'                         => 'float',
            'date'                                               => 'date',
            'datetime', 'timestamp'                              => 'datetime',
            'text', 'mediumtext', 'longtext'                     => 'textarea',
            'blob'                                               => 'textarea',
            'enum', 'set'                                        => 'select',
            'bool', 'boolean'                                    => 'bool',
            default                                              => 'string',
        };
    }

    private static function defaultSearchable(string $dbType, string $name): bool
    {
        return in_array($dbType, ['varchar', 'char', 'text', 'mediumtext', 'longtext'], true);
    }

    /** Zamienia np. "IDdrukarki" na "ID drukarki". */
    private static function humanize(string $name): string
    {
        $spaced = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $name) ?? $name;
        $spaced = preg_replace('/(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $spaced) ?? $spaced;
        return trim($spaced);
    }

    /** Bezpieczny identyfikator SQL (nazwa tabeli/kolumny). */
    public static function qi(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

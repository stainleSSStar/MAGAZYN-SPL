<?php
/**
 * Narzędzie CLI: naprawa podwójnie zakodowanych polskich znaków.
 *
 *  Dump bazy zawiera jeden zapis zniekształcony przez konwersję
 *  UTF-8 -> Windows-1250 (zamiast poprawnego kodowania).
 *  Przykład: "trzeba odebrać" zapisane jako "trzeba odebraÄ‡".
 *
 *  Użycie:
 *      php bin/repair-encoding.php             # tylko sprawdza (raport)
 *      php bin/repair-encoding.php --write     # poprawia w bazie
 *
 *  Narzędzie skanuje wszystkie kolumny tekstowe wszystkich tabel,
 *  poprawia TYLKO wartości, które jednoznacznie wyglądają na
 *  podwójnie zakodowane (po poprawnej konwersji pojawiają się
 *  polskie znaki diakrytyczne, których wcześniej nie było).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("To narzędzie działa tylko z linii poleceń.\n");
}

require __DIR__ . '/../app/bootstrap.php';

$config = $GLOBALS['config'];
Database::init($config['db']);
$write = in_array('--write', $argv, true);

/** Polskie znaki diakrytyczne - ich pojawienie się po konwersji = sygnał naprawy. */
const PL_PATTERN = '/[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]/u';

echo "Panel Magazynu - naprawa kodowania znaków\n";
echo 'Baza: ' . $config['db']['name'] . ' @ ' . $config['db']['host'] . "\n";
echo $write ? "Tryb: ZAPIS\n\n" : "Tryb: tylko raport (dodaj --write, aby poprawić)\n\n";

/**
 * Próbuje naprawić pojedynczy tekst. Zwraca naprawiony tekst albo null.
 */
function repairText(string $value): ?string
{
    if ($value === '' || !mb_check_encoding($value, 'UTF-8')) {
        return null;
    }
    // Mojibake zawiera znaki z zakresu U+0080-U+00FF albo typograficzne
    if (!preg_match('/[\x{0080}-\x{00FF}]/u', $value)) {
        return null;
    }

    // Konwersja znaków UTF-8 na bajty Windows-1250 odtwarza oryginalne bajty UTF-8.
    // (np. "Ä‡" -> bajty C4 87 -> "ć")
    $bytes = @iconv('UTF-8', 'CP1250//IGNORE', $value);
    if ($bytes === false || $bytes === '') {
        return null;
    }
    if (!mb_check_encoding($bytes, 'UTF-8')) {
        return null;
    }
    // Poprawiony tekst musi zawierać polskie znaki (a oryginał ich nie miał)
    if (!preg_match(PL_PATTERN, $bytes)) {
        return null;
    }
    if (preg_match(PL_PATTERN, $value)) {
        return null; // oryginał już miał poprawne polskie znaki
    }
    // Kontrola round-trip: bajty odczytane jako Windows-1250 muszą dać
    // dokładnie ten sam tekst, który był w bazie (wyklucza pominięcia z //IGNORE)
    if (@iconv('CP1250', 'UTF-8', $bytes) !== $value) {
        return null;
    }

    return $bytes;
}

$tables = Database::all(
    "SELECT TABLE_NAME AS name FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME"
);

$totalFixed = 0;
$totalRows  = 0;
$candidates = 0;

foreach ($tables as $t) {
    $table = (string) $t['name'];

    $cols = Database::all(
        "SELECT COLUMN_NAME AS name, DATA_TYPE AS type FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
           AND DATA_TYPE IN ('varchar','char','text','mediumtext','longtext')",
        [$table]
    );
    if ($cols === []) {
        continue;
    }

    $pkCols = Database::all(
        "SELECT COLUMN_NAME AS name FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_KEY = 'PRI'",
        [$table]
    );
    $pk = $pkCols !== [] ? (string) $pkCols[0]['name'] : null;

    $colNames = array_map(static fn (array $c): string => (string) $c['name'], $cols);
    $select   = array_map(static fn (string $c): string => "`" . str_replace('`', '``', $c) . "`", $colNames);
    $pkSql    = $pk !== null ? '`' . str_replace('`', '``', $pk) . '`' : null;

    $rows = Database::all(
        'SELECT ' . ($pkSql !== null ? $pkSql . ', ' : '') . implode(', ', $select)
        . ' FROM `' . str_replace('`', '``', $table) . '`'
    );

    $fixedRows = 0;
    foreach ($rows as $row) {
        $totalRows++;
        $updates = [];

        foreach ($colNames as $col) {
            $value = $row[$col] ?? null;
            if (!is_string($value) || $value === '') {
                continue;
            }
            $fixed = repairText($value);
            if ($fixed !== null && $fixed !== $value) {
                $updates[$col] = $fixed;
                echo sprintf(
                    "  [%s] %s: \"%s\"  ->  \"%s\"\n",
                    $table,
                    $pk !== null ? $pk . '=' . $row[$pk] : '?',
                    $value,
                    $fixed
                );
            }
        }

        if ($updates !== []) {
            $candidates++;

            if ($write && $pk !== null) {
                $sets   = [];
                $params = [];
                foreach ($updates as $col => $newValue) {
                    $sets[]        = '`' . str_replace('`', '``', $col) . '` = :set_' . $col;
                    $params['set_' . $col] = $newValue;
                }
                $params['__pk'] = $row[$pk];
                Database::execute(
                    'UPDATE `' . str_replace('`', '``', $table) . '` SET ' . implode(', ', $sets)
                    . ' WHERE `' . str_replace('`', '``', $pk) . '` = :__pk',
                    $params
                );
                $fixedRows++;
                $totalFixed++;
            }
        }
    }

    if ($fixedRows > 0) {
        echo "  -> poprawiono $fixedRows wierszy w tabeli $table\n";
    }
}

echo "\nSprawdzono wierszy: $totalRows\n";
echo $write
    ? "Poprawiono wierszy: $totalFixed\n"
    : "Do poprawienia: $candidates (uruchom ponownie z --write)\n";

exit(0);

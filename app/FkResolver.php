<?php
/**
 * Rozwiązywanie kluczy obcych - czytelne etykiety zamiast samych numerów.
 */

declare(strict_types=1);

final class FkResolver
{
    /** @var array<string,array<int|string,string>> cache opcji */
    private static array $cache = [];

    /**
     * Opcje list wyboru dla wszystkich kolumn kluczy obcych tabeli.
     *
     * @return array<string,array<int|string,array{label:string,model:mixed,model_name:mixed}>>
     */
    public static function optionsFor(string $table): array
    {
        $out = [];
        foreach (Schema::columns($table) as $name => $col) {
            $ref = $col['ref'] ?? null;
            if (!$ref) {
                continue;
            }
            $out[$name] = self::optionsDetailed($ref, $col['ref_display'] ?? null);
        }
        return $out;
    }

    /**
     * @return array<int|string,string>
     */
    public static function options(string $refTable, array|string|null $displayCols = null): array
    {
        $key = $refTable . '|' . (is_array($displayCols) ? implode(',', $displayCols) : (string) $displayCols);
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        return self::$cache[$key] = Schema::options($refTable, $displayCols);
    }

    /**
     * Opcje wraz z kontekstem modelu (potrzebne do zawężania listy materiałów).
     *
     * @return array<int|string,array{label:string,model:mixed,model_name:mixed}>
     */
    public static function optionsDetailed(string $refTable, array|string|null $displayCols = null): array
    {
        return Schema::optionsDetailed($refTable, $displayCols);
    }

    /**
     * Wartość powiązanej kolumny z wiersza tabeli referowanej.
     * Np. dla urządzenia 56 zwraca IDmodel (5) - używane do ustawienia modelu
     * przy wskazaniu drukarki z adresu URL.
     */
    public static function linkedValue(string $refTable, string $keyCol, string|int $value, string $wantCol): mixed
    {
        if ($value === '' || !preg_match('/^[A-Za-z0-9_]+$/', $refTable)
            || !preg_match('/^[A-Za-z0-9_]+$/', $keyCol)
            || !preg_match('/^[A-Za-z0-9_]+$/', $wantCol)) {
            return null;
        }

        try {
            $sql = "SELECT `{$wantCol}` AS v FROM `{$refTable}` WHERE `{$keyCol}` = ? LIMIT 1";
            return Database::value($sql, [$value]);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Zamienia wartości kluczy obcych w rekordzie na czytelne etykiety.
     *
     * @param array<string,mixed> $row
     * @return array<string,array{id:mixed,label:string,table:string}>
     */
    public static function resolve(string $table, array $row): array
    {
        $out = [];
        foreach (Schema::columns($table) as $name => $col) {
            $ref = $col['ref'] ?? null;
            if (!$ref) {
                continue;
            }
            $value = $row[$name] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            $options = self::options($ref, $col['ref_display'] ?? null);
            $out[$name] = [
                'id'    => $value,
                'label' => (string) ($options[(string) $value] ?? ('#' . $value)),
                'table' => $ref,
            ];
        }
        return $out;
    }
}

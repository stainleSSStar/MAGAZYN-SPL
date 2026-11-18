<?php
/**
 * Lokalne ustawienia panelu, które nie mieszczą się w bazie danych.
 *
 * Panel nie tworzy tabel ani kolumn, więc flagy specyficzne dla panelu
 * (np. „ten materiał to wartość archiwalna, nie zamawiać go”) trzymane są
 * w pliku config/panel-data.json.
 */

declare(strict_types=1);

final class PanelData
{
    /** Ścieżka do pliku z ustawieniami. */
    public static function path(): string
    {
        return dirname(__DIR__) . '/config/panel-data.json';
    }

    /** @return array<string,mixed> */
    public static function all(): array
    {
        if (!is_file(self::path())) {
            return [];
        }
        $raw = @file_get_contents(self::path());
        if ($raw === false || $raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string,mixed> $data */
    public static function save(array $data): bool
    {
        $dir = dirname(self::path());
        if (!is_dir($dir) || !is_writable($dir)) {
            return false;
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }

        $tmp = self::path() . '.tmp';
        if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
            return false;
        }
        if (!@rename($tmp, self::path())) {
            @unlink($tmp);
            return false;
        }
        @chmod(self::path(), 0644);

        return true;
    }

    /**
     * Identyfikatory materiałów pominiętych przy zamówieniu.
     *
     * @return array<int,string>
     */
    public static function ignoredPurchase(): array
    {
        $data = self::all();
        $list = $data['ignorowane_zakup'] ?? [];
        if (!is_array($list)) {
            return [];
        }
        return array_values(array_unique(array_map('strval', $list)));
    }

    /** Czy dany materiał jest pominięty przy zamówieniu. */
    public static function isIgnoredPurchase(string $id): bool
    {
        return in_array((string) $id, self::ignoredPurchase(), true);
    }

    /**
     * Dodaje/usuwa materiał z listy pomijanych przy zamówieniu.
     *
     * @return bool czy zapis się udał
     */
    public static function toggleIgnoredPurchase(string $id): bool
    {
        $id  = (string) $id;
        $all = self::all();
        $list = $all['ignorowane_zakup'] ?? [];
        if (!is_array($list)) {
            $list = [];
        }
        $list = array_map('strval', $list);

        if (in_array($id, $list, true)) {
            $list = array_values(array_diff($list, [$id]));
        } else {
            $list[] = $id;
        }

        $all['ignorowane_zakup'] = array_values(array_unique($list));
        return self::save($all);
    }
}

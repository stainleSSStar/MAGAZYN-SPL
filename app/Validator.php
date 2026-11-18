<?php
/**
 * Walidacja danych z formularzy - na podstawie metadanych schematu.
 */

declare(strict_types=1);

final class Validator
{
    /**
     * @param array<string,mixed> $input  dane z POST
     * @param int|string|null    $editingId  ID edytowanego rekordu (null = dodawanie)
     *
     * @return array{0:array<string,mixed>,1:array<string,string>} [dane do zapisu, błędy]
     */
    public static function validate(string $table, array $input, int|string|null $editingId = null): array
    {
        $meta    = Schema::meta($table);
        $columns = $meta['columns'];
        $errors  = [];
        $data    = [];

        foreach ($columns as $name => $col) {
            $type = $col['type'];

            // Klucz główny z AUTO_INCREMENT ustawia baza - pomijamy.
            if ($col['auto_increment']) {
                continue;
            }

            $raw = $input[$name] ?? null;
            $val = is_string($raw) ? trim($raw) : $raw;

            // Puste pola tekstowe -> NULL (jeśli koluma dopuszcza NULL)
            if ($val === '' || $val === null) {
                if ($col['required']) {
                    $errors[$name] = 'Pole „' . $col['label'] . '” jest wymagane.';
                } elseif ($col['nullable']) {
                    $data[$name] = null;
                } else {
                    $data[$name] = $type === 'int' ? 0 : '';
                }
                continue;
            }

            switch ($type) {
                case 'int':
                    if (!self::isIntLike($val)) {
                        $errors[$name] = 'Pole „' . $col['label'] . '” musi być liczbą całkowitą.';
                        $data[$name] = $val;
                        break;
                    }
                    $data[$name] = (int) $val;
                    break;

                case 'float':
                    if (!is_numeric($val)) {
                        $errors[$name] = 'Pole „' . $col['label'] . '” musi być liczbą.';
                    }
                    $data[$name] = (float) $val;
                    break;

                case 'date':
                    if (!self::isDate($val)) {
                        $errors[$name] = 'Pole „' . $col['label'] . '” musi zawierać poprawną datę (RRRR-MM-DD).';
                    }
                    $data[$name] = $val;
                    break;

                case 'datetime':
                    $data[$name] = $val;
                    break;

                case 'ip':
                    if (filter_var($val, FILTER_VALIDATE_IP) === false) {
                        $errors[$name] = 'Pole „' . $col['label'] . '” musi zawierać poprawny adres IP.';
                    }
                    $data[$name] = $val;
                    break;

                case 'bool':
                    $data[$name] = (int) (bool) $val;
                    break;

                default: // string / textarea
                    if ($col['max_length'] !== null && mb_strlen((string) $val) > $col['max_length']) {
                        $errors[$name] = 'Pole „' . $col['label'] . '” może mieć maksymalnie '
                            . $col['max_length'] . ' znaków.';
                    }
                    $data[$name] = (string) $val;
                    break;
            }

            // --- Klucz obcy: sprawdź czy wskazany rekord istnieje ------------
            if (($col['ref'] ?? null) && isset($data[$name]) && $data[$name] !== null && $data[$name] !== '') {
                $refTable = $col['ref'];
                $refPk    = Schema::pk($refTable);
                if ($refPk !== '') {
                    try {
                        $exists = Database::value(
                            'SELECT COUNT(*) FROM ' . Schema::qi($refTable) . ' WHERE ' . Schema::qi($refPk) . ' = :id',
                            ['id' => $data[$name]]
                        );
                        if ((int) $exists === 0) {
                            $errors[$name] = 'Wskazany rekord nie istnieje w tabeli „'
                                . Schema::label($refTable) . '”.';
                        }
                    } catch (Throwable $e) {
                        $errors[$name] = 'Nie udało się sprawdzić wartości pola „' . $col['label'] . '”.';
                    }
                }
            }
        }

        // --- Unikalność klucza głównego ------------------------------------
        $pk = $meta['pk'];
        if ($pk !== '' && isset($data[$pk]) && $data[$pk] !== null && $data[$pk] !== '') {
            try {
                $sql    = 'SELECT COUNT(*) FROM ' . Schema::qi($table) . ' WHERE ' . Schema::qi($pk) . ' = :id';
                $params = ['id' => $data[$pk]];
                if ($editingId !== null) {
                    $sql .= ' AND ' . Schema::qi($pk) . ' <> :__old';
                    $params['__old'] = $editingId;
                }
                if ((int) Database::value($sql, $params) > 0) {
                    $errors[$pk] = 'Wartość „' . $data[$pk] . '” jest już zajęta - wybierz inny numer.';
                }
            } catch (Throwable $e) {
                $errors[$pk] = 'Nie udało się sprawdzić numeru rekordu.';
            }
        }

        return [$data, $errors];
    }

    private static function isIntLike(mixed $val): bool
    {
        if (is_int($val)) {
            return true;
        }
        $s = trim((string) $val);
        return $s !== '' && preg_match('/^-?\d+$/', $s) === 1;
    }

    private static function isDate(string $val): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $val, $m) !== 1) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}

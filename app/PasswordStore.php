<?php
/**
 * Przechowywanie hasła administratora panelu.
 *
 * Hasło NIE jest zapisywane w config/config.php - trzymane jest w osobnym,
 * lokalnym pliku config/auth.local.php (tworzonym przy pierwszym ustawieniu
 * hasła z panelu albo komendą: php bin/set-password.php "Hasło" --write).
 *
 * Dzięki temu:
 *  - w paczce z panelem nie ma żadnego domyślnego hasła,
 *  - plik config/config.php zawiera wyłącznie dane bazy i nazwę administratora,
 *  - aktualizacja panelu (podmiana plików) nie kasuje ustawionego hasła.
 *
 * Klasa nie zależy od niczego - może być używana również z CLI.
 */

declare(strict_types=1);

final class PasswordStore
{
    /** Nazwa pliku w katalogu config/. */
    public const FILE = 'auth.local.php';

    /** Ścieżka bezwzględna do pliku z hashem hasła. */
    public static function path(): string
    {
        return dirname(__DIR__) . '/config/' . self::FILE;
    }

    /** Czy plik z hashem istnieje. */
    public static function exists(): bool
    {
        return is_file(self::path());
    }

    /**
     * Zapisuje nowy hash hasła.
     *
     * @return array{ok:bool,message:string}
     */
    public static function write(string $hash): array
    {
        if (!preg_match('/^\$2[ay]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $hash)) {
            return ['ok' => false, 'message' => 'Nieprawidłowy format hasła - zapis przerwany.'];
        }

        $path = self::path();
        $dir  = dirname($path);

        if (!is_dir($dir) || !is_writable($dir)) {
            return [
                'ok'      => false,
                'message' => 'Katalog config/ nie jest zapisywalny. Nadaj mu prawa do zapisu '
                           . '(np. chmod 755 config) albo ustaw hasło komendą: '
                           . 'php bin/set-password.php "TwojeHaslo" --write',
            ];
        }
        if (is_file($path) && !is_writable($path)) {
            return [
                'ok'      => false,
                'message' => 'Plik ' . self::FILE . ' nie jest zapisywalny. Nadaj mu prawa do zapisu '
                           . '(np. chmod 644 config/' . self::FILE . ') albo ustaw hasło komendą: '
                           . 'php bin/set-password.php "TwojeHaslo" --write',
            ];
        }

        $content = "<?php\n"
            . "/**\n"
            . " * Hasło administratora panelu.\n"
            . " *\n"
            . " * Ustawione z panelu (trasa „Zmiana hasła”) albo komendą:\n"
            . " *     php bin/set-password.php \"TwojeHaslo\" --write\n"
            . " *\n"
            . " * Ten plik jest lokalny - NIE jest dołączany do paczki z panelem.\n"
            . " */\n\n"
            . "return [\n"
            . "    'auth' => [\n"
            . "        'password_hash' => '" . $hash . "',\n"
            . "    ],\n"
            . "];\n";

        $tmp = $path . '.tmp';
        if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
            return [
                'ok'      => false,
                'message' => 'Nie udało się zapisać pliku z hasłem. Sprawdź prawa do zapisu w katalogu config/.',
            ];
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            return [
                'ok'      => false,
                'message' => 'Nie udało się zapisać pliku z hasłem. Sprawdź prawa do zapisu w katalogu config/.',
            ];
        }
        @chmod($path, 0644);

        return ['ok' => true, 'message' => 'Hasło zapisane w config/' . self::FILE . '.'];
    }
}

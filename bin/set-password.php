<?php
/**
 * Narzędzie CLI: ustawia hasło administratora panelu.
 *
 * Zapisuje hasło (bcrypt) w pliku config/users.php - tym samym, którego używa panel.
 *
 * Użycie:
 *     php bin/set-password.php "MojeNoweHaslo"                 # pokazuje hash
 *     php bin/set-password.php "MojeNoweHaslo" --write         # zapisuje
 *     php bin/set-password.php "MojeNoweHaslo" --write --login JAN
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("To narzędzie działa tylko z linii poleceń.\n");
}

require __DIR__ . '/../app/PasswordStore.php';
require __DIR__ . '/../app/Users.php';

$password = $argv[1] ?? '';
$write    = in_array('--write', $argv, true);
$login    = 'ADMIN';

foreach ($argv as $i => $arg) {
    if ($arg === '--login' && isset($argv[$i + 1])) {
        $login = (string) $argv[$i + 1];
    }
}

if ($password === '') {
    fwrite(STDERR, "Użycie: php bin/set-password.php \"TwojeNoweHaslo\" [--write] [--login LOGIN]\n");
    exit(1);
}

if (strlen($password) < Users::MIN_PASSWORD) {
    fwrite(STDERR, "Hasło jest za krótkie - użyj minimum " . Users::MIN_PASSWORD . " znaków.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

if ($write) {
    // Konto istnieje - zmieniamy hasło; nie istnieje - tworzymy administratora.
    $existing = Users::find($login);
    if ($existing !== null) {
        $res = Users::setPassword($login, $password);
    } else {
        $res = Users::create($login, $login, $password, Users::ROLE_ADMIN);
    }

    if (!$res['ok']) {
        fwrite(STDERR, "Nie udało się zapisać hasła: " . $res['message'] . "\n");
        exit(1);
    }

    echo "Hasło administratora zostało zapisane w config/users.php\n";
    echo "Login: " . $login . "\n";
    echo "Nowy hash: " . $hash . "\n";
    exit(0);
}

echo "Nowy hash hasła:\n\n";
echo $hash . "\n\n";
echo "Zapisz go komendą:\n\n";
echo "    php bin/set-password.php \"" . $password . "\" --write\n";

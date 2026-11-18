<?php
/**
 * Kreator pierwszego uruchomienia - dane bazy + pierwsze konto administratora.
 * Dostępny zanim baza jest skonfigurowana i zanim istnieje jakiekolwiek konto.
 *
 * @var string|null $error
 * @var string|null $notice
 * @var array  $dbValues
 * @var bool   $dbOk
 * @var bool   $needAccount
 * @var string $appName
 */
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Konfiguracja · <?= e($appName ?? 'Panel Magazynu') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_path()) ?>/assets/img/logo.svg">
    <link rel="stylesheet" href="<?= e(base_path()) ?>/assets/css/app.css">
</head>
<body class="loginpage loginpage--setup">
    <div class="loginbox loginbox--wide">
        <div class="loginbox__head">
            <img src="<?= e(base_path()) ?>/assets/img/logo.svg" alt="" width="72" height="84">
            <h1>Konfiguracja panelu</h1>
            <p>Dwa kroki i panel jest gotowy do pracy</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert--danger"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- ============ KROK 1: BAZA DANYCH ============ -->
        <section class="setupstep">
            <header class="setupstep__head">
                <span class="setupstep__num">1</span>
                <div>
                    <h2>Połączenie z bazą danych</h2>
                    <p class="muted">
                        <?php if ($dbOk): ?>
                            Połączenie działa. Możesz je poprawić, jeśli chcesz użyć innego konta.
                        <?php else: ?>
                            Panel nie łączy się z bazą — uzupełnij dane.
                        <?php endif; ?>
                    </p>
                </div>
                <span class="badge <?= $dbOk ? 'badge--ok' : 'badge--danger' ?>">
                    <?= $dbOk ? 'połączono' : 'brak połączenia' ?>
                </span>
            </header>

            <form method="post" action="<?= e(url('setup')) ?>" class="setupstep__body">
                <?= csrf_field() ?>
                <input type="hidden" name="_sekcja" value="baza">

                <div class="setupgrid">
                    <label class="field">
                        <span class="field__label">Host</span>
                        <input type="text" name="db_host" value="<?= e($dbValues['host']) ?>" placeholder="localhost">
                    </label>
                    <label class="field">
                        <span class="field__label">Port</span>
                        <input type="number" name="db_port" value="<?= e($dbValues['port']) ?>" placeholder="3306">
                    </label>
                    <label class="field">
                        <span class="field__label">Nazwa bazy <span class="req">*</span></span>
                        <input type="text" name="db_name" value="<?= e($dbValues['name']) ?>" placeholder="magazyn">
                    </label>
                    <label class="field">
                        <span class="field__label">Użytkownik <span class="req">*</span></span>
                        <input type="text" name="db_user" value="<?= e($dbValues['user']) ?>" autocomplete="off">
                    </label>
                    <label class="field">
                        <span class="field__label">Hasło</span>
                        <input type="password" name="db_pass" value="" autocomplete="new-password"
                               placeholder="<?= $dbValues['pass'] !== '' ? 'bez zmian' : '' ?>">
                    </label>
                    <label class="field">
                        <span class="field__label">Gniazdo (socket) <small class="muted">- opcjonalnie</small></span>
                        <input type="text" name="db_socket" value="<?= e($dbValues['socket']) ?>"
                               placeholder="/var/run/mysqld/mysqld.sock">
                    </label>
                </div>

                <p class="muted setupstep__note">
                    Dane zapisujemy w pliku <code>config/db.local.php</code> (poza bazą, poza paczką).
                    Uprawnienia użytkownika bazy: <code>SELECT, INSERT, UPDATE, DELETE</code> — panel nie tworzy tabel.
                </p>

                <button type="submit" class="btn btn--primary">Sprawdź i zapisz połączenie</button>
            </form>
        </section>

        <!-- ============ KROK 2: KONTO ADMINISTRATORA ============ -->
        <?php if ($needAccount): ?>
            <section class="setupstep">
                <header class="setupstep__head">
                    <span class="setupstep__num">2</span>
                    <div>
                        <h2>Konto administratora</h2>
                        <p class="muted">To jedyne konto z uprawnieniami do zapisu i zarządzania.</p>
                    </div>
                </header>

                <form method="post" action="<?= e(url('setup')) ?>" class="setupstep__body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_sekcja" value="konto">

                    <div class="setupgrid">
                        <label class="field">
                            <span class="field__label">Login <span class="req">*</span></span>
                            <input type="text" name="login" value="ADMIN" required
                                   pattern="[A-Za-z0-9._-]{2,32}" autocomplete="username">
                        </label>
                        <label class="field">
                            <span class="field__label">Nazwa wyświetlana</span>
                            <input type="text" name="name" value="Administrator" placeholder="np. Jan Kowalski">
                        </label>
                        <label class="field">
                            <span class="field__label">Hasło <span class="req">*</span></span>
                            <input type="password" name="p1" required minlength="8" autocomplete="new-password">
                        </label>
                        <label class="field">
                            <span class="field__label">Powtórz hasło <span class="req">*</span></span>
                            <input type="password" name="p2" required minlength="8" autocomplete="new-password">
                        </label>
                    </div>

                    <p class="muted setupstep__note">
                        Konta zapisujemy w pliku <code>config/users.php</code>. Hasła są kodowane (bcrypt),
                        nigdy nie są przechowywane w bazie danych.
                    </p>

                    <button type="submit" class="btn btn--primary">Utwórz konto administratora</button>
                </form>
            </section>
        <?php else: ?>
            <section class="setupstep">
                <header class="setupstep__head">
                    <span class="setupstep__num setupstep__num--ok">✓</span>
                    <div>
                        <h2>Konto administratora</h2>
                        <p class="muted">Konta są już skonfigurowane — zarządzasz nimi w panelu (menu „Konta”).</p>
                    </div>
                </header>
                <div class="setupstep__body">
                    <a class="btn btn--primary" href="<?= e(url('login')) ?>">Przejdź do logowania</a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>

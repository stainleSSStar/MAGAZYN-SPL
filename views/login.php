<?php
/**
 * Formularz logowania - samodzielna strona (bez nawigacji panelu).
 *
 * @var string|null $error
 * @var string      $login
 * @var string      $appName
 */
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Logowanie · <?= e($appName ?? 'Panel Magazynu') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_path()) ?>/assets/img/logo.svg">
    <link rel="stylesheet" href="<?= e(base_path()) ?>/assets/css/app.css">
</head>
<body class="loginpage">
    <div class="loginbox">
        <div class="loginbox__head">
            <img src="<?= e(base_path()) ?>/assets/img/logo.svg" alt="" width="72" height="84">
            <h1><?= e($appName ?? 'Panel Magazynu') ?></h1>
            <p>Zaloguj się, aby zarządzać bazą magazynu</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert--danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login')) ?>" class="loginbox__form">
            <?= csrf_field() ?>
            <label class="field">
                <span class="field__label">Login</span>
                <input type="text" name="u" value="<?= e($login ?? '') ?>" required autofocus autocomplete="username">
            </label>

            <label class="field">
                <span class="field__label">Hasło</span>
                <input type="password" name="p" required autocomplete="current-password">
            </label>

            <button type="submit" class="btn btn--primary btn--block">Zaloguj się</button>
        </form>
    </div>
</body>
</html>

<?php
/**
 * Układ (layout) panelu - sidebar, górny pasek, obszar treści.
 *
 * @var string $content  wyrenderowana treść
 * @var array  $flash    komunikaty jednorazowe
 */

$pageTitle    = $pageTitle    ?? ($GLOBALS['config']['app']['name'] ?? 'Panel');
$pageSubtitle = $pageSubtitle ?? '';
$activeRoute  = $activeRoute  ?? ($_GET['r'] ?? 'dashboard');
$activeTable  = $activeTable  ?? ($_GET['t'] ?? '');
$navTables    = Schema::tables();
$appName      = $GLOBALS['config']['app']['name'] ?? 'Panel Magazynu';
?>
<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle) ?> · <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(base_path()) ?>/assets/img/logo.svg">
    <link rel="stylesheet" href="<?= e(base_path()) ?>/assets/css/app.css?v=<?= e($GLOBALS['config']['app']['version'] ?? '1') ?>">
</head>
<body>
<div class="app">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar__brand">
            <img src="<?= e(base_path()) ?>/assets/img/logo.svg" alt="" class="sidebar__logo" width="44" height="51">
            <div class="sidebar__brandtext">
                <span class="sidebar__name"><?= e($appName) ?></span>
                <span class="sidebar__sub">Baza danych magazynu</span>
            </div>
        </div>

        <nav class="nav">
            <a class="nav__item <?= $activeRoute === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('dashboard')) ?>">
                <span class="nav__icon">📊</span> Pulpit
            </a>
            <a class="nav__item <?= $activeRoute === 'wymiany' ? 'is-active' : '' ?>" href="<?= e(url('wymiany')) ?>">
                <span class="nav__icon">🔄</span> Wymiany
            </a>
            <a class="nav__item <?= $activeRoute === 'devices' || $activeRoute === 'device' ? 'is-active' : '' ?>" href="<?= e(url('devices')) ?>">
                <span class="nav__icon">🖨️</span> Urządzenia
            </a>
            <a class="nav__item <?= $activeRoute === 'search' ? 'is-active' : '' ?>" href="<?= e(url('search')) ?>">
                <span class="nav__icon">🔍</span> Wyszukiwarka
            </a>

            <div class="nav__section">Tabele</div>
            <?php foreach ($navTables as $tName => $tInfo): ?>
                <a class="nav__item <?= ($activeRoute === 'table' || $activeRoute === 'record' || $activeRoute === 'form') && $activeTable === $tName ? 'is-active' : '' ?>"
                   href="<?= e(url('table', ['t' => $tName])) ?>">
                    <span class="nav__icon"><?= e($tInfo['icon'] ?? '🗂️') ?></span>
                    <span class="nav__label"><?= e($tInfo['label']) ?></span>
                    <span class="nav__count"><?= (int) $tInfo['count'] ?></span>
                </a>
            <?php endforeach; ?>

            <div class="nav__section">Narzędzia</div>
            <a class="nav__item <?= $activeRoute === 'structure' ? 'is-active' : '' ?>" href="<?= e(url('structure')) ?>">
                <span class="nav__icon">🧩</span> Struktura bazy
            </a>
            <?php if (Auth::isAdmin()): ?>
                <a class="nav__item <?= $activeRoute === 'konta' ? 'is-active' : '' ?>" href="<?= e(url('konta')) ?>">
                    <span class="nav__icon">👥</span> Konta użytkowników
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar__foot">
            <a class="sidebar__user" href="<?= e(url('haslo')) ?>" title="Kliknij, aby zmienić hasło">
                <span class="sidebar__avatar"><?= e(mb_strtoupper(mb_substr(Auth::user(), 0, 1))) ?></span>
                <div>
                    <strong><?= e(Auth::user()) ?></strong>
                    <small><?= e(Users::roleLabel(Auth::role())) ?> · zmień hasło</small>
                </div>
            </a>
            <form method="post" action="<?= e(url('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn--ghost btn--sm btn--block">Wyloguj się</button>
            </form>
        </div>
    </aside>

    <!-- ================= MAIN ================= -->
    <div class="main">
        <header class="topbar">
            <button class="iconbtn topbar__burger" type="button" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu">☰</button>

            <div class="topbar__title">
                <h1><?= e($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ''): ?>
                    <p><?= e($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>

            <form class="topbar__search" method="get" action="<?= e(base_path()) ?>/index.php">
                <input type="hidden" name="r" value="search">
                <input type="search" name="q" placeholder="Szukaj w całej bazie… (Enter)" value="<?= e(qs('q')) ?>" autocomplete="off">
                <button type="submit" aria-label="Szukaj">🔍</button>
            </form>
        </header>

        <main class="content">
            <?php if (!Auth::isAdmin()): ?>
                <div class="alert alert--info">
                    <span>
                        Twoje konto ma uprawnienia <strong>tylko odczyt</strong> — możesz przeglądać dane
                        i eksportować do CSV, ale nie możesz zapisywać ani usuwać rekordów.
                    </span>
                </div>
            <?php endif; ?>

            <?php foreach ($flash as $msg): ?>
                <div class="alert alert--<?= e($msg['type']) ?>">
                    <span><?= e($msg['message']) ?></span>
                    <button type="button" class="alert__close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endforeach; ?>

            <?= $content ?>
        </main>

        <footer class="footer">
            <?= e($appName) ?> v<?= e($GLOBALS['config']['app']['version'] ?? '1.0.0') ?>
        </footer>
    </div>
</div>

<div class="overlay" onclick="document.body.classList.remove('nav-open')"></div>
<script src="<?= e(base_path()) ?>/assets/js/app.js?v=<?= e($GLOBALS['config']['app']['version'] ?? '1') ?>"></script>
</body>
</html>

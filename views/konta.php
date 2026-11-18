<?php
/**
 * Zarządzanie kontami użytkowników (tylko administrator).
 *
 * @var array  $users
 * @var string $me
 * @var string|null $error
 */
$pageTitle    = 'Konta użytkowników';
$pageSubtitle = 'Konta do logowania, ich role oraz hasła';
$activeRoute  = 'konta';

$adminCount = Users::adminCount($users);
?>
<?php if (!empty($error)): ?>
    <div class="alert alert--danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="alert alert--info">
    <span>
        Twoje konto: <strong><?= e($me) ?></strong> ·
        rola: <strong><?= e(Users::roleLabel(Auth::role())) ?></strong>.
        Administratorzy widzą wszystkie dane i mogą je zmieniać; konta „tylko odczyt”
        mogą przeglądać dane i eksportować do CSV, ale nie mogą zapisywać ani usuwać.
    </span>
</div>

<div class="grid grid--sidebar">
    <!-- ================= LISTA KONT ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Konta</h2>
            <span class="badge"><?= count($users) ?> kont</span>
        </header>
        <div class="card__body card__body--flush">
            <table class="table">
                <thead>
                    <tr>
                        <th>Login</th>
                        <th>Nazwa</th>
                        <th>Rola</th>
                        <th>Stan</th>
                        <th>Utworzono</th>
                        <th class="right">Operacje</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <strong><?= e($u['login']) ?></strong>
                            <?php if (mb_strtolower($u['login']) === mb_strtolower($me)): ?>
                                <small class="muted">(Ty)</small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($u['name']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === Users::ROLE_ADMIN ? 'badge--ok' : '' ?>">
                                <?= e(Users::roleLabel($u['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['active']): ?>
                                <span class="badge badge--ok">aktywne</span>
                            <?php else: ?>
                                <span class="badge badge--warn">wyłączone</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= e($u['created_at'] !== '' ? $u['created_at'] : '—') ?></td>
                        <td class="right nowrap">
                            <button type="button" class="btn btn--ghost btn--sm" data-edit-user
                                    data-login="<?= e($u['login']) ?>"
                                    data-name="<?= e($u['name']) ?>"
                                    data-role="<?= e($u['role']) ?>"
                                    data-active="<?= $u['active'] ? '1' : '0' ?>">
                                ✎ Edytuj
                            </button>
                            <button type="button" class="btn btn--ghost btn--sm" data-pass-user
                                    data-login="<?= e($u['login']) ?>">
                                🔑 Hasło
                            </button>
                            <?php if ($adminCount > 1 || $u['role'] !== Users::ROLE_ADMIN): ?>
                                <form method="post" action="<?= e(url('konto')) ?>" class="inline"
                                      onsubmit="return confirm('Usunąć konto <?= e($u['login']) ?>? Operacji nie można cofnąć.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="_akcja" value="usun">
                                    <input type="hidden" name="login" value="<?= e($u['login']) ?>">
                                    <button type="submit" class="btn btn--danger btn--sm">🗑 Usuń</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ================= EDYTOR KONTA ================= -->
    <aside class="sidecol">
        <section class="card" id="konto-nowe">
            <header class="card__head"><h2>Nowe konto</h2></header>
            <div class="card__body">
                <form method="post" action="<?= e(url('konto')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_akcja" value="nowe">

                    <label class="field">
                        <span class="field__label">Login <span class="req">*</span></span>
                        <input type="text" name="login" id="konto-login" required pattern="[A-Za-z0-9._-]{2,32}"
                               autocomplete="off">
                    </label>

                    <label class="field">
                        <span class="field__label">Nazwa wyświetlana</span>
                        <input type="text" name="name" id="konto-nazwa" autocomplete="off">
                    </label>

                    <label class="field">
                        <span class="field__label">Rola <span class="req">*</span></span>
                        <select name="role" id="konto-rola">
                            <option value="<?= e(Users::ROLE_ADMIN) ?>">Administrator — pełne prawa</option>
                            <option value="<?= e(Users::ROLE_READONLY) ?>">Tylko odczyt — bez zapisu</option>
                        </select>
                    </label>

                    <label class="field">
                        <span class="field__label">Hasło <span class="req">*</span></span>
                        <input type="password" name="haslo" id="konto-haslo" required minlength="8" autocomplete="new-password">
                        <small class="field__hint">Minimum 8 znaków.</small>
                    </label>

                    <button type="submit" class="btn btn--primary btn--block">Utwórz konto</button>
                </form>
            </div>
        </section>

        <section class="card" id="konto-edycja" hidden>
            <header class="card__head"><h2>Edycja konta</h2></header>
            <div class="card__body">
                <form method="post" action="<?= e(url('konto')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_akcja" value="zapisz">
                    <input type="hidden" name="login" id="edyt-login">

                    <p class="muted">Konto: <strong id="edyt-tytul"></strong></p>

                    <label class="field">
                        <span class="field__label">Nazwa wyświetlana</span>
                        <input type="text" name="name" id="edyt-nazwa">
                    </label>

                    <label class="field">
                        <span class="field__label">Rola</span>
                        <select name="role" id="edyt-rola">
                            <option value="<?= e(Users::ROLE_ADMIN) ?>">Administrator — pełne prawa</option>
                            <option value="<?= e(Users::ROLE_READONLY) ?>">Tylko odczyt — bez zapisu</option>
                        </select>
                    </label>

                    <label class="checkline">
                        <input type="checkbox" name="active" id="edyt-aktywne" value="1" checked>
                        <span>Konto aktywne — może się logować</span>
                    </label>

                    <button type="submit" class="btn btn--primary btn--block">Zapisz zmiany</button>
                    <button type="button" class="btn btn--ghost btn--block" id="konto-anuluj">Anuluj edycję</button>
                </form>
            </div>
        </section>

        <section class="card" id="konto-haslo-panel" hidden>
            <header class="card__head"><h2>Nowe hasło użytkownika</h2></header>
            <div class="card__body">
                <form method="post" action="<?= e(url('konto')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_akcja" value="haslo">
                    <input type="hidden" name="login" id="haslo-login">

                    <p class="muted">Ustawiasz hasło dla konta: <strong id="haslo-tytul"></strong></p>

                    <label class="field">
                        <span class="field__label">Nowe hasło <span class="req">*</span></span>
                        <input type="password" name="haslo" required minlength="8" autocomplete="new-password">
                        <small class="field__hint">Minimum 8 znaków. Użytkownik może je potem zmienić sam.</small>
                    </label>

                    <button type="submit" class="btn btn--primary btn--block">Ustaw hasło</button>
                    <button type="button" class="btn btn--ghost btn--block" id="haslo-anuluj">Anuluj</button>
                </form>
            </div>
        </section>
    </aside>
</div>

<script>
(function () {
    var edytPanel = document.getElementById('konto-edycja');
    var hasloPanel = document.getElementById('konto-haslo-panel');

    function show(panel, on) { if (panel) { panel.hidden = !on; } }

    document.querySelectorAll('[data-edit-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edyt-login').value = btn.getAttribute('data-login');
            document.getElementById('edyt-tytul').textContent = btn.getAttribute('data-login');
            document.getElementById('edyt-nazwa').value = btn.getAttribute('data-name');
            document.getElementById('edyt-rola').value = btn.getAttribute('data-role');
            document.getElementById('edyt-aktywne').checked = btn.getAttribute('data-active') === '1';
            show(hasloPanel, false);
            show(edytPanel, true);
            edytPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    document.querySelectorAll('[data-pass-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('haslo-login').value = btn.getAttribute('data-login');
            document.getElementById('haslo-tytul').textContent = btn.getAttribute('data-login');
            show(edytPanel, false);
            show(hasloPanel, true);
            hasloPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    });

    var anulujEdyt = document.getElementById('konto-anuluj');
    if (anulujEdyt) {
        anulujEdyt.addEventListener('click', function () { show(edytPanel, false); });
    }
    var anulujHaslo = document.getElementById('haslo-anuluj');
    if (anulujHaslo) {
        anulujHaslo.addEventListener('click', function () { show(hasloPanel, false); });
    }
})();
</script>

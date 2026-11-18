<?php
/**
 * Zmiana hasła administratora.
 *
 * @var string|null $error
 */
$pageTitle    = 'Zmiana hasła';
$pageSubtitle = 'Ustaw nowe hasło swojego konta';
?>
<?php if (!empty($error)): ?>
    <div class="alert alert--danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="formcard formcard--narrow">
    <form method="post" action="<?= e(url('haslo')) ?>">
        <?= csrf_field() ?>

        <div class="formcard__grid">
            <div class="formfield">
                <label class="field" for="fld_current">
                    <span class="field__label">Aktualne hasło <span class="req">*</span></span>
                    <input type="password" name="current" id="fld_current" required autocomplete="current-password"
                           autofocus>
                </label>
            </div>

            <div class="formfield">
                <label class="field" for="fld_p1">
                    <span class="field__label">Nowe hasło <span class="req">*</span></span>
                    <input type="password" name="p1" id="fld_p1" required minlength="8" autocomplete="new-password">
                    <small class="field__hint">Minimum 8 znaków. Nie może być takie samo jak nazwa administratora.</small>
                </label>
            </div>

            <div class="formfield">
                <label class="field" for="fld_p2">
                    <span class="field__label">Powtórz nowe hasło <span class="req">*</span></span>
                    <input type="password" name="p2" id="fld_p2" required minlength="8" autocomplete="new-password">
                </label>
            </div>
        </div>

        <div class="formcard__foot">
            <button type="submit" class="btn btn--primary">💾 Zapisz nowe hasło</button>
            <a class="btn btn--ghost" href="<?= e(url('dashboard')) ?>">Anuluj</a>
            <span class="muted" style="font-size:12.5px">
                Konto: <strong><?= e(Auth::user()) ?></strong> · rola: <?= e(Users::roleLabel(Auth::role())) ?> ·
                hasło zapisuje się w pliku <code>config/users.php</code> — nie trafia do bazy.
            </span>
        </div>
    </form>
</div>

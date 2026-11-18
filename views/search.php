<?php
/**
 * Wyszukiwarka globalna - przeszukuje wszystkie tabele bazy.
 *
 * @var string $term
 * @var array  $hits
 */
$pageTitle    = 'Wyszukiwarka';
$pageSubtitle = $term !== '' ? 'Wyniki dla frazy: „' . $term . '”' : 'Przeszukaj wszystkie tabele bazy danych';
?>
<div class="card">
    <div class="card__body">
        <form method="get" action="<?= e(base_path()) ?>/index.php" class="toolbar__search">
            <input type="hidden" name="r" value="search">
            <input type="search" name="q" value="<?= e($term) ?>" placeholder="Wpisz frazę, np. „OKI”, „192.168.1.50”, „toner”…" autofocus>
            <button type="submit" class="btn btn--primary">Szukaj</button>
        </form>
        <p class="hint">
            Wyszukiwanie działa po wszystkich polach tekstowych każdej tabeli.
            Jeśli szukasz konkretnej drukarki, użyj zakładki <strong>Urządzenia</strong> - znajdziesz tam
            filtrowanie po lokalizacji, adresie IP i modelu.
        </p>
    </div>
</div>

<?php if ($term === ''): ?>
    <div class="grid grid--3">
        <?php foreach (Schema::tables() as $tName => $tInfo): ?>
            <a class="quick__item quick__item--card" href="<?= e(url('table', ['t' => $tName])) ?>">
                <span class="quick__icon"><?= e($tInfo['icon'] ?? '🗂️') ?></span>
                <span>
                    <strong><?= e($tInfo['label']) ?></strong>
                    <small><?= (int) $tInfo['count'] ?> rekordów</small>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
<?php elseif (empty($hits)): ?>
    <div class="card">
        <div class="card__body empty-state">
            <div class="empty-state__icon">🔍</div>
            <h2>Brak wyników</h2>
            <p>Nie znaleziono żadnego rekordu zawierającego „<?= e($term) ?>”.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($hits as $tName => $res): ?>
        <section class="card">
            <header class="card__head">
                <h2><?= e(Schema::label($tName)) ?></h2>
                <span class="badge"><?= (int) $res['total'] ?> trafień</span>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('table', ['t' => $tName, 'q' => $term])) ?>">Otwórz tabelę →</a>
            </header>
            <div class="card__body card__body--flush">
                <div class="tablewrap">
                    <table class="table table--compact">
                        <thead>
                        <tr>
                            <?php foreach (Schema::columns($tName) as $cName => $col): ?>
                                <th><?= e($col['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($res['rows'] as $row): ?>
                            <tr onclick="window.location='<?= e(url('record', ['t' => $tName, 'id' => $row[Schema::pk($tName)] ?? ''])) ?>'" class="clickable">
                                <?php foreach (Schema::columns($tName) as $cName => $col): ?>
                                    <?php $v = $row[$cName] ?? null; ?>
                                    <td><?= ($v === null || $v === '') ? '<span class="muted">—</span>' : e((string) $v) ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

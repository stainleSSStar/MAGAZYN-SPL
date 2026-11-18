<?php
/**
 * Potwierdzenie masowego usunięcia rekordów używanych w innych tabelach.
 *
 * @var string $table
 * @var array  $ids
 * @var array  $refs
 * @var array  $meta
 */
$pageTitle    = 'Potwierdzenie usunięcia';
$pageSubtitle = count($ids) . ' zaznaczonych rekordów jest powiązanych z innymi tabelami';
$activeTable  = $table;

$blocked = false;
foreach ($refs as $r) {
    if ($r['blocked']) {
        $blocked = true;
    }
}
?>
<div class="alert alert--warning">
    <span>
        Zaznaczone rekordy są używane w innych tabelach. Usunięcie ich bez potwierdzenia
        zostawiłoby w bazie pozycje bez odpowiedniego opisu.
    </span>
</div>

<div class="grid grid--sidebar">
    <section class="card">
        <header class="card__head">
            <h2>Co zostanie usunięte</h2>
            <span class="badge"><?= count($ids) ?> rekordów</span>
        </header>
        <div class="card__body">
            <p class="muted">
                Tabela <strong><?= e($meta['label']) ?></strong> — numery rekordów:
            </p>
            <p class="recids"><?php foreach ($ids as $id): ?><code>#<?= e($id) ?></code><?php endforeach; ?></p>
        </div>
    </section>

    <aside class="sidecol">
        <section class="card">
            <header class="card__head"><h2>Gdzie są używane</h2></header>
            <div class="card__body">
                <ul class="linklist">
                    <?php foreach ($refs as $relTable => $r): ?>
                        <li>
                            <a href="<?= e(url('table', ['t' => $relTable, 'f' => [$r['column'] => $ids[0] ?? '']])) ?>">
                                <?= e($r['label']) ?>
                            </a>
                            <span class="badge"><?= (int) $r['count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($blocked): ?>
                    <div class="alert alert--danger" style="margin:16px 0 0">
                        <span>
                            <strong>Baza nie pozwoli usunąć tych rekordów.</strong>
                            Powiązane rekordy wskazują na nie kluczem obcym z regułą <code>RESTRICT</code>.
                            Najpierw usuń albo zmień powiązane rekordy powyżej.
                        </span>
                    </div>
                <?php else: ?>
                    <div class="alert alert--warning" style="margin:16px 0 0">
                        <span>
                            Po usunięciu powiązane rekordy zachowają wpis, ale zostaną <strong>bez odniesienia</strong>
                            do usuwanych pozycji (puste pole klucza obcego).
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card__foot card__foot--actions">
                <a class="btn btn--ghost" href="<?= e(url('table', ['t' => $table])) ?>">
                    Anuluj — zostaw rekordy
                </a>
                <?php if (!$blocked): ?>
                    <form method="post" action="<?= e(url('bulk-delete')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="t" value="<?= e($table) ?>">
                        <?php foreach ($ids as $id): ?>
                            <input type="hidden" name="ids[]" value="<?= e($id) ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="_force" value="1">
                        <button type="submit" class="btn btn--danger"
                                onclick="return confirm('Usunąć zaznaczone rekordy mimo powiązań?');">
                            🗑 Usuń mimo to
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

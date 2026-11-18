<?php
/**
 * Potwierdzenie usunięcia rekordu, który jest używany w innych tabelach.
 *
 * @var string $table
 * @var array|null $row
 * @var array  $refs
 * @var array  $meta
 */
$pageTitle    = 'Potwierdzenie usunięcia';
$pageSubtitle = 'Ten rekord jest używany w innych tabelach';
$activeTable  = $table;

$pk      = $meta['pk'] ?? '';
$blocked = false;
foreach ($refs as $r) {
    if ($r['blocked']) {
        $blocked = true;
    }
}
?>
<div class="alert alert--warning">
    <span>
        Ten rekord jest powiązany z innymi rekordami w bazie. Usunięcie go bez potwierdzenia
        zostawiłoby w bazie pozycje bez odpowiedniego opisu.
    </span>
</div>

<div class="grid grid--sidebar">
    <section class="card">
        <header class="card__head">
            <h2>Rekord do usunięcia</h2>
            <span class="badge"><?= e($meta['label']) ?></span>
        </header>
        <div class="card__body card__body--flush">
            <table class="table table--detail">
                <tbody>
                <?php if (is_array($row)): ?>
                    <?php foreach ($meta['columns'] as $cName => $col): ?>
                        <?php $v = $row[$cName] ?? null; ?>
                        <?php if ($v === null || $v === '') { continue; } ?>
                        <tr>
                            <th><?= e($col['label']) ?></th>
                            <td>
                                <?php if ($col['type'] === 'date') { ?>
                                    <?= e(fmt_date((string) $v)) ?>
                                <?php } else { ?>
                                    <?= e((string) $v) ?>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" class="muted">Rekord #<?= e((string) ($_GET['id'] ?? '')) ?> nie istnieje.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="sidecol">
        <section class="card">
            <header class="card__head"><h2>Gdzie jest używany</h2></header>
            <div class="card__body">
                <ul class="linklist">
                    <?php foreach ($refs as $relTable => $r): ?>
                        <li>
                            <a href="<?= e(url('table', ['t' => $relTable, 'f' => [$r['column'] => (string) ($row[$pk] ?? '')]])) ?>">
                                <?= e($r['label']) ?>
                            </a>
                            <span class="badge"><?= (int) $r['count'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if ($blocked): ?>
                    <div class="alert alert--danger" style="margin:16px 0 0">
                        <span>
                            <strong>Baza nie pozwoli usunąć tego rekordu.</strong>
                            Powiązane rekordy wskazują na niego kluczem obcym z regułą <code>RESTRICT</code>.
                            Najpierw usuń albo zmień powiązane rekordy powyżej.
                        </span>
                    </div>
                <?php else: ?>
                    <div class="alert alert--warning" style="margin:16px 0 0">
                        <span>
                            Po usunięciu powiązane rekordy zachowają wpis, ale zostaną <strong>bez odniesienia</strong>
                            do tego rekordu (puste pole klucza obcego). Zdecyduj, czy tego chcesz.
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card__foot card__foot--actions">
                <a class="btn btn--ghost" href="<?= e(url('record', ['t' => $table, 'id' => (string) ($row[$pk] ?? '')])) ?>">
                    Anuluj — zostaw rekord
                </a>
                <?php if (!$blocked): ?>
                    <form method="post" action="<?= e(url('delete')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="t" value="<?= e($table) ?>">
                        <input type="hidden" name="id" value="<?= e((string) ($row[$pk] ?? '')) ?>">
                        <input type="hidden" name="_force" value="1">
                        <button type="submit" class="btn btn--danger"
                                onclick="return confirm('Usunąć rekord mimo powiązań? Powiązane rekordy stracą odniesienie.');">
                            🗑 Usuń mimo to
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>

<?php
/**
 * Szczegóły rekordu - wszystkie pola z opisami i powiązaniami.
 *
 * @var string $table
 * @var array  $meta
 * @var array  $row
 * @var array  $related
 * @var array  $fkValues
 */
$pageTitle    = $meta['label_one'] . ' #' . ($row[$meta['pk']] ?? '');
$pageSubtitle = $meta['description'];
$activeTable  = $table;

$pk = $meta['pk'];
$columns = $meta['columns'];
?>
<div class="toolbar">
    <div class="toolbar__actions">
        <a class="btn btn--ghost" href="<?= e(url('table', ['t' => $table])) ?>">← Wróć do listy</a>
        <?php if (can_write()): ?>
            <a class="btn btn--primary" href="<?= e(url('form', ['t' => $table, 'id' => $row[$pk] ?? ''])) ?>">✎ Edytuj</a>
            <form method="post" action="<?= e(url('delete')) ?>" class="inline"
                  onsubmit="return confirm('Usunąć ten rekord? Operacji nie można cofnąć.');">
                <?= csrf_field() ?>
                <input type="hidden" name="t" value="<?= e($table) ?>">
                <input type="hidden" name="id" value="<?= e($row[$pk] ?? '') ?>">
                <button type="submit" class="btn btn--danger">🗑 Usuń</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid--sidebar">
    <section class="card">
        <header class="card__head">
            <h2>Dane rekordu</h2>
            <span class="badge"><?= e($meta['label']) ?></span>
        </header>
        <div class="card__body card__body--flush">
            <table class="table table--detail">
                <tbody>
                <?php foreach ($columns as $cName => $col): ?>
                    <?php $raw = $row[$cName] ?? null; ?>
                    <tr>
                        <th>
                            <?= e($col['label']) ?>
                            <?php if ($col['desc'] !== ''): ?>
                                <span class="field__info" title="<?= e($col['desc']) ?>">ⓘ</span>
                            <?php endif; ?>
                        </th>
                        <td>
                            <?php if (!empty($col['ref']) && $raw !== null && $raw !== ''): ?>
                                <?php $fk = $fkValues[$cName] ?? null; ?>
                                <a href="<?= e(url('record', ['t' => $col['ref'], 'id' => $raw])) ?>">
                                    <?= e($fk['label'] ?? ('#' . $raw)) ?>
                                </a>
                                <span class="muted">(#<?= e($raw) ?>)</span>
                            <?php elseif ($raw === null || $raw === ''): ?>
                                <span class="muted">—</span>
                            <?php elseif ($col['type'] === 'date') : ?>
                                <?= e(fmt_date((string) $raw)) ?>
                            <?php elseif ($col['type'] === 'datetime'): ?>
                                <?= e(fmt_datetime((string) $raw)) ?>
                            <?php elseif ($col['type'] === 'textarea'): ?>
                                <span class="pre"><?= e((string) $raw) ?></span>
                            <?php else: ?>
                                <?= e((string) $raw) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <aside class="sidecol">
        <?php if (!empty($related)): ?>
            <section class="card">
                <header class="card__head"><h2>Powiązane rekordy</h2></header>
                <div class="card__body">
                    <ul class="linklist">
                        <?php foreach ($related as $relTable => $relInfo): ?>
                            <li>
                                <a href="<?= e(url('table', ['t' => $relTable, 'f' => [$relInfo['column'] => (string) ($row[$pk] ?? '')]])) ?>">
                                    <?= e(Schema::label($relTable)) ?>
                                </a>
                                <span class="badge"><?= (int) $relInfo['count'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($table === 'asortyment'): ?>
            <?php $ignored = PanelData::isIgnoredPurchase((string) ($row[$pk] ?? '')); ?>
            <section class="card">
                <header class="card__head"><h2>Zamawianie</h2></header>
                <div class="card__body">
                    <?php if ($ignored): ?>
                        <p class="muted" style="margin-bottom:12px">
                            Ten materiał jest <strong>pominięty przy zamówieniu</strong> — nie wchodzi
                            na listę „Materiały do zamówienia”, nawet jeśli stan jest ujemny.
                            Przydatne dla pozycji o wartości archiwalnej.
                        </p>
                    <?php else: ?>
                        <p class="muted" style="margin-bottom:12px">
                            Ten materiał jest uwzględniany na liście „Materiały do zamówienia”.
                            Możesz go pominąć, jeśli ma wartość archiwalną.
                        </p>
                    <?php endif; ?>

                    <?php if (can_write()): ?>
                        <form method="post" action="<?= e(url('ignoruj-zakup')) ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e((string) ($row[$pk] ?? '')) ?>">
                            <input type="hidden" name="back" value="record">
                            <button type="submit" class="btn btn--ghost btn--block">
                                <?= $ignored ? '↩ Przywróć do zamówienia' : '⛔ Pomiń przy zamówieniu' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($table === 'drukarki'): ?>
            <section class="card">
                <header class="card__head"><h2>Skrót</h2></header>
                <div class="card__body">
                    <a class="btn btn--primary btn--block" href="<?= e(url('device', ['id' => $row[$pk] ?? ''])) ?>">
                        🖨️ Karta urządzenia i historia
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <section class="card">
            <header class="card__head"><h2>Informacja o polach</h2></header>
            <div class="card__body">
                <dl class="deflist">
                    <?php foreach ($columns as $cName => $col): ?>
                        <dt><?= e($col['label']) ?> <code><?= e($cName) ?></code></dt>
                        <dd>
                            <?= e($col['desc'] !== '' ? $col['desc'] : 'Brak opisu w słowniku panelu.') ?>
                            <small class="muted">Typ: <?= e($col['db_type']) ?><?= $col['required'] ? ' · wymagane' : '' ?><?= $col['nullable'] ? ' · dopuszczalne NULL' : '' ?></small>
                        </dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>
    </aside>
</div>

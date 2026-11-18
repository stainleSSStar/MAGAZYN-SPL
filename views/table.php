<?php
/**
 * Lista rekordów: wyszukiwarka, filtry, sortowanie, paginacja, eksport CSV,
 * zaznaczanie wielu rekordów i usuwanie masowe.
 *
 * @var string $table
 * @var array  $meta
 * @var array  $result
 * @var array  $summary
 */
$pageTitle    = $meta['label'];
$pageSubtitle = $meta['description'];
$activeTable  = $table;

$columns = $meta['columns'];
$rows    = $result['rows'];
$q       = qs('q');
$filters = qs_filters('f');
$perPage = (int) $result['per_page'];
$maxRows = (int) ($GLOBALS['config']['app']['rows_max'] ?? 500);
$pk      = $meta['pk'];

// Mapowanie kluczy obcych na czytelne etykiety
$fkLabels = [];
foreach ($columns as $cName => $col) {
    if (!empty($col['ref'])) {
        $fkLabels[$cName] = FkResolver::options($col['ref'], $col['ref_display'] ?? null);
    }
}
?>
<div class="toolbar">
    <form class="toolbar__search" method="get" action="<?= e(base_path()) ?>/index.php">
        <input type="hidden" name="r" value="table">
        <input type="hidden" name="t" value="<?= e($table) ?>">
        <?php foreach ($filters as $fk => $fv): ?>
            <?php if (is_array($fv)): ?>
                <?php foreach (['from', 'to'] as $op): ?>
                    <?php if (isset($fv[$op])): ?>
                        <input type="hidden" name="f[<?= e($fk) ?>][<?= e($op) ?>]" value="<?= e($fv[$op]) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <input type="hidden" name="f[<?= e($fk) ?>]" value="<?= e($fv) ?>">
            <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="sort" value="<?= e($result['sort']) ?>">
        <input type="hidden" name="dir" value="<?= e($result['dir']) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Szukaj w rekordach…">
        <button type="submit" class="btn btn--primary">Szukaj</button>
        <?php if ($q !== '' || $filters !== []): ?>
            <a class="btn btn--ghost" href="<?= e(url('table', ['t' => $table])) ?>">Wyczyść</a>
        <?php endif; ?>
    </form>

    <div class="toolbar__actions">
        <details class="dropdown">
            <summary class="btn btn--ghost">Na stronę: <?= $perPage ?></summary>
            <div class="dropdown__menu">
                <?php foreach ([25, 50, 100, 200, $maxRows] as $size): ?>
                    <a class="dropdown__item <?= $perPage === (int) $size ? 'is-active' : '' ?>"
                       href="<?= e(url_with(['per_page' => $size, 'page' => 1])) ?>"><?= (int) $size ?> rekordów</a>
                <?php endforeach; ?>
            </div>
        </details>

        <a class="btn btn--ghost" href="<?= e(url_with(['r' => 'export', 't' => $table])) ?>">⬇ CSV</a>
        <?php if (can_write()): ?>
            <a class="btn btn--primary" href="<?= e(url('form', ['t' => $table])) ?>">＋ Dodaj rekord</a>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== FILTRY ===================== -->
<details class="filters" <?= $filters !== [] ? 'open' : '' ?>>
    <summary>Filtry kolumn <span class="filters__count"><?= count($filters) ?></span></summary>
    <form method="get" action="<?= e(base_path()) ?>/index.php" class="filters__grid">
        <input type="hidden" name="r" value="table">
        <input type="hidden" name="t" value="<?= e($table) ?>">
        <input type="hidden" name="q" value="<?= e($q) ?>">
        <input type="hidden" name="sort" value="<?= e($result['sort']) ?>">
        <input type="hidden" name="dir" value="<?= e($result['dir']) ?>">

        <?php foreach ($columns as $cName => $col): ?>
            <?php if ($col['pk'] && $col['auto_increment']) { continue; } ?>
            <label class="field field--sm">
                <span class="field__label">
                    <?= e($col['label']) ?>
                    <?php if ($col['desc'] !== ''): ?><span class="field__info" title="<?= e($col['desc']) ?>">ⓘ</span><?php endif; ?>
                </span>
                <?php if ($col['type'] === 'date'): ?>
                    <input type="date" name="f[<?= e($cName) ?>][from]" data-calendar="1"
                           value="<?= e(is_array($filters[$cName] ?? null) ? ($filters[$cName]['from'] ?? '') : '') ?>">
                    <input type="date" name="f[<?= e($cName) ?>][to]" data-calendar="1"
                           value="<?= e(is_array($filters[$cName] ?? null) ? ($filters[$cName]['to'] ?? '') : '') ?>">
                <?php elseif (!empty($col['ref'])): ?>
                    <select name="f[<?= e($cName) ?>]">
                        <option value="">— wszystkie —</option>
                        <?php foreach (($fkLabels[$cName] ?? []) as $optVal => $optLabel): ?>
                            <option value="<?= e($optVal) ?>" <?= (string) ($filters[$cName] ?? '') === (string) $optVal ? 'selected' : '' ?>>
                                <?= e($optLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="<?= $col['type'] === 'int' ? 'number' : 'text' ?>"
                           name="f[<?= e($cName) ?>]"
                           value="<?= e(is_array($filters[$cName] ?? null) ? '' : ($filters[$cName] ?? '')) ?>"
                           placeholder="<?= $col['type'] === 'int' ? 'liczba' : 'filtr…' ?>">
                <?php endif; ?>
            </label>
        <?php endforeach; ?>

        <div class="filters__buttons">
            <button type="submit" class="btn btn--primary btn--sm">Zastosuj filtry</button>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('table', ['t' => $table])) ?>">Wyczyść</a>
        </div>
    </form>
</details>

<!-- ===================== PODSUMOWANIE ===================== -->
<?php if (!empty($summary) && (int) ($summary['count'] ?? 0) > 0): ?>
    <div class="kpi-grid kpi-grid--4">
        <div class="kpi kpi--static">
            <span class="kpi__icon">📋</span>
            <span class="kpi__value"><?= (int) $summary['count'] ?></span>
            <span class="kpi__label">Rekordów w wynikach</span>
            <span class="kpi__meta"><?= $q !== '' || $filters !== [] ? 'po zastosowaniu filtrów' : 'wszystkie w tabeli' ?></span>
        </div>
        <?php if (!empty($summary['date_from'])): ?>
            <div class="kpi kpi--static">
                <span class="kpi__icon">📅</span>
                <span class="kpi__value" style="font-size:17px"><?= e(fmt_date((string) $summary['date_from'])) ?></span>
                <span class="kpi__label">Najstarszy wpis</span>
                <span class="kpi__meta">początek okresu</span>
            </div>
            <div class="kpi kpi--static">
                <span class="kpi__icon">🗓️</span>
                <span class="kpi__value" style="font-size:17px"><?= e(fmt_date((string) ($summary['date_to'] ?? ''))) ?></span>
                <span class="kpi__label">Najnowszy wpis</span>
                <span class="kpi__meta">koniec okresu</span>
            </div>
        <?php endif; ?>
        <?php foreach ($columns as $cName => $col): ?>
            <?php if (isset($summary['sum_' . $cName])): ?>
                <div class="kpi kpi--static">
                    <span class="kpi__icon">🔢</span>
                    <span class="kpi__value"><?= number_format((float) $summary['sum_' . $cName], 0, ',', ' ') ?></span>
                    <span class="kpi__label">Suma: <?= e($col['label']) ?></span>
                    <span class="kpi__meta">średnia <?= number_format((float) ($summary['avg_' . $cName] ?? 0), 0, ',', ' ') ?></span>
                </div>
            <?php elseif (isset($summary['distinct_' . $cName])): ?>
                <div class="kpi kpi--static">
                    <span class="kpi__icon">🔗</span>
                    <span class="kpi__value"><?= (int) $summary['distinct_' . $cName] ?></span>
                    <span class="kpi__label">Różne: <?= e($col['label']) ?></span>
                    <span class="kpi__meta">różnych wartości w wynikach</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ===================== TABELA ===================== -->
<section class="card">
    <form method="post" action="<?= e(url('bulk-delete')) ?>" id="bulk-form">
        <?= csrf_field() ?>
        <input type="hidden" name="t" value="<?= e($table) ?>">

        <div class="card__head">
            <h2><?= e($meta['label']) ?></h2>
            <span class="badge"><?= (int) $result['total'] ?> rekordów</span>
            <div class="bulkbar">
                <span id="bulk-count" class="muted">Zaznaczono: 0</span>
                <?php if (can_write()): ?>
                    <button type="submit" class="btn btn--danger btn--sm" id="bulk-btn" disabled
                            onclick="return confirm('Usunąć zaznaczone rekordy? Operacji nie można cofnąć.');">
                        🗑 Usuń zaznaczone
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="card__body card__body--flush">
            <div class="tablewrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="th--check"><input type="checkbox" id="check-all" title="Zaznacz wszystkie na stronie"></th>
                        <?php foreach ($columns as $cName => $col): ?>
                            <?php
                            $isSorted = $result['sort'] === $cName;
                            $nextDir  = ($isSorted && $result['dir'] === 'ASC') ? 'desc' : 'asc';
                            ?>
                            <th class="<?= $col['pk'] ? 'th--pk' : '' ?>">
                                <a class="th-sort <?= $isSorted ? 'is-active' : '' ?>"
                                   href="<?= e(url_with(['sort' => $cName, 'dir' => $nextDir, 'page' => 1])) ?>">
                                    <?= e($col['label']) ?>
                                    <span class="th-sort__arrow"><?= $isSorted ? ($result['dir'] === 'ASC' ? '▲' : '▼') : '↕' ?></span>
                                </a>
                                <?php if ($col['desc'] !== ''): ?>
                                    <span class="field__info" title="<?= e($col['desc']) ?>">ⓘ</span>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="th--actions">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + 2 ?>" class="empty-row">
                                Brak rekordów spełniających kryteria.
                                <a href="<?= e(url('table', ['t' => $table])) ?>">Wyczyść filtry</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= e($row[$pk]) ?>" class="row-check"></td>
                                <?php foreach ($columns as $cName => $col): ?>
                                    <?php
                                    $val = $row[$cName] ?? null;
                                    if (!empty($col['ref']) && $val !== null && $val !== '') {
                                        $shown = $fkLabels[$cName][(string) $val] ?? ('#' . $val);
                                        $cell  = '<a href="' . e(url('record', ['t' => $table, 'id' => $row[$pk]])) . '">' . e($shown) . '</a>';
                                    } elseif ($val === null || $val === '') {
                                        $cell = '<span class="muted">—</span>';
                                    } elseif ($col['type'] === 'date') {
                                        $cell = e(fmt_date((string) $val));
                                    } elseif ($col['type'] === 'int' && $cName === 'Ilosc') {
                                        $qty  = (int) $val;
                                        $cls  = $qty < 0 ? 'badge--danger' : ($qty === 0 ? 'badge--warn' : 'badge--ok');
                                        $cell = '<span class="badge ' . $cls . '">' . $qty . '</span>';
                                    } elseif ($col['type'] === 'textarea' || (is_string($val) && mb_strlen($val) > 60)) {
                                        $cell = '<span title="' . e((string) $val) . '">' . e(mb_strimwidth((string) $val, 0, 60, '…')) . '</span>';
                                    } else {
                                        $cell = e((string) $val);
                                    }
                                    ?>
                                    <td><?= $cell ?></td>
                                <?php endforeach; ?>
                                <td class="rowactions">
                                    <a class="iconbtn" title="Podgląd" href="<?= e(url('record', ['t' => $table, 'id' => $row[$pk]])) ?>">👁</a>
                                    <?php if (can_write()): ?>
                                        <a class="iconbtn" title="Edytuj" href="<?= e(url('form', ['t' => $table, 'id' => $row[$pk]])) ?>">✎</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===================== PAGINACJA ===================== -->
        <footer class="card__foot pager">
            <span class="pager__info">
                Wyświetlane <strong><?= (int) $result['from'] ?>–<?= (int) $result['to'] ?></strong>
                z <strong><?= (int) $result['total'] ?></strong> rekordów
                <?php if ((int) $result['pages'] > 1): ?>
                    · strona <?= (int) $result['page'] ?> z <?= (int) $result['pages'] ?>
                <?php endif; ?>
            </span>

            <?php if ((int) $result['pages'] > 1): ?>
                <nav class="pager__nav">
                    <?php
                    $page  = (int) $result['page'];
                    $pages = (int) $result['pages'];
                    ?>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>"
                       href="<?= $page <= 1 ? '#' : url_with(['page' => 1]) ?>">« pierwsza</a>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>"
                       href="<?= $page <= 1 ? '#' : url_with(['page' => max(1, $page - 1)]) ?>">‹ poprzednia</a>

                    <?php
                    $start = max(1, min($page - 2, max(1, $pages - 4)));
                    $end   = min($pages, $start + 4);
                    $start = max(1, $end - 4);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a class="pager__btn <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url_with(['page' => $i])) ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>"
                       href="<?= $page >= $pages ? '#' : url_with(['page' => min($pages, $page + 1)]) ?>">następna ›</a>
                    <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>"
                       href="<?= $page >= $pages ? '#' : url_with(['page' => $pages]) ?>">ostatnia »</a>
                </nav>
            <?php endif; ?>
        </footer>
    </form>
</section>

<p class="hint">
    Kolumny <span class="muted">ⓘ</span> mają opis (najedź myszą). Aby dodać pole: przycisk <strong>＋ Dodaj rekord</strong>.
    Zaznaczając kilka rekordów możesz je usunąć jednym kliknięciem.
</p>

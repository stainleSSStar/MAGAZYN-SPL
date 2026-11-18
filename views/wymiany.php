<?php
/**
 * Moduł WYMANY - filtrowanie po urządzeniu, modelu, pracowniku, dacie i okresie.
 *
 * @var array $result
 * @var array $filters
 * @var string $preset
 * @var array $summary
 * @var array $devices
 * @var array $models
 * @var array $workers
 * @var array $materials
 * @var string $dateFrom
 * @var string $dateTo
 */
$pageTitle    = 'Wymiany';
$pageSubtitle = 'Historia wymian materiałów - wszystkie, z ostatniego okresu lub dla jednego urządzenia';

$rows = $result['rows'];

$presets = [
    'all'    => ['Wszystkie',   ''],
    '7'      => ['7 dni',       '7'],
    '30'     => ['30 dni',      '30'],
    '90'     => ['90 dni',      '90'],
    'mies'   => ['Ten miesiąc', 'mies'],
    'rok'    => ['Ten rok',     'rok'],
    'last10' => ['Ostatnie 10', 'last10'],
];

$devSel = (string) ($filters['IDdrukarki'] ?? '');
$modSel = (string) ($filters['IDmodel'] ?? '');
$wrkSel = (string) ($filters['IDpracownik'] ?? '');
$matSel = (string) ($filters['IDasortyment'] ?? '');
?>
<div class="toolbar">
    <div class="toolbar__actions">
        <?php if (can_write()): ?>
            <a class="btn btn--primary" href="<?= e(url('form', ['t' => 'wymiany'])) ?>">＋ Dodaj wymianę</a>
        <?php endif; ?>
        <?php if ($devSel !== ''): ?>
            <?php if (can_write()): ?>
                <a class="btn btn--ghost" href="<?= e(url('form', ['t' => 'wymiany', 'IDdrukarki' => $devSel])) ?>">
                    ＋ Dodaj dla tego urządzenia
                </a>
            <?php endif; ?>
            <a class="btn btn--ghost" href="<?= e(url('device', ['id' => $devSel])) ?>">🖨️ Karta urządzenia</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url_with(['r' => 'export', 't' => 'wymiany'])) ?>">⬇ CSV</a>
    </div>
</div>

<!-- ================= FILTRY ================= -->
<section class="card filtercard">
    <div class="card__body">
        <form method="get" action="<?= e(base_path()) ?>/index.php" id="wymiany-filter">
            <input type="hidden" name="r" value="wymiany">

            <div class="filtergrid">
                <!-- Urządzenie -->
                <label class="field">
                    <span class="field__label">🖨️ Urządzenie (drukarka)</span>
                    <select name="f[IDdrukarki]" id="f-dev">
                        <option value="">— wszystkie urządzenia —</option>
                        <?php foreach ($devices as $dId => $dName): ?>
                            <option value="<?= e($dId) ?>" <?= $devSel === (string) $dId ? 'selected' : '' ?>>
                                <?= e($dName) ?> (#<?= e($dId) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" class="selectfilter" placeholder="filtruj listę urządzeń…" data-target="f-dev">
                </label>

                <!-- Model -->
                <label class="field">
                    <span class="field__label">⚙️ Model</span>
                    <select name="f[IDmodel]" id="f-model">
                        <option value="">— wszystkie modele —</option>
                        <?php foreach ($models as $mId => $mName): ?>
                            <option value="<?= e($mId) ?>" <?= $modSel === (string) $mId ? 'selected' : '' ?>>
                                <?= e($mName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" class="selectfilter" placeholder="filtruj modele…" data-target="f-model">
                </label>

                <!-- Pracownik -->
                <label class="field">
                    <span class="field__label">👷 Pracownik</span>
                    <select name="f[IDpracownik]" id="f-worker">
                        <option value="">— wszyscy pracownicy —</option>
                        <?php foreach ($workers as $wId => $wName): ?>
                            <option value="<?= e($wId) ?>" <?= $wrkSel === (string) $wId ? 'selected' : '' ?>>
                                <?= e($wName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" class="selectfilter" placeholder="filtruj pracowników…" data-target="f-worker">
                </label>

                <!-- Materiał -->
                <label class="field">
                    <span class="field__label">📦 Materiał</span>
                    <select name="f[IDasortyment]" id="f-material">
                        <option value="">— wszystkie materiały —</option>
                        <?php foreach ($materials as $aId => $aName): ?>
                            <option value="<?= e($aId) ?>" <?= $matSel === (string) $aId ? 'selected' : '' ?>>
                                <?= e($aName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="search" class="selectfilter" placeholder="filtruj materiały…" data-target="f-material">
                </label>

                <!-- Zakres dat -->
                <label class="field">
                    <span class="field__label">📅 Data od</span>
                    <input type="date" name="f[Data][from]" value="<?= e($dateFrom) ?>" class="datepick" data-calendar="1">
                </label>

                <label class="field">
                    <span class="field__label">📅 Data do</span>
                    <input type="date" name="f[Data][to]" value="<?= e($dateTo) ?>" class="datepick" data-calendar="1">
                </label>

                <!-- Szukaj w uwagach -->
                <label class="field">
                    <span class="field__label">🔍 Szukaj w uwagach</span>
                    <input type="search" name="q" value="<?= e(qs('q')) ?>" placeholder="np. reset, pojemnik…">
                </label>
            </div>

            <div class="presets">
                <span class="presets__label">Szybki wybór okresu:</span>
                <?php foreach ($presets as $key => [$label, $val]): ?>
                    <a class="preset <?= $preset === $key ? 'is-active' : '' ?>"
                       href="<?= e(url_with(['preset' => $val, 'page' => 1, 'r' => 'wymiany'], 'wymiany')) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>

            <div class="filtergrid__foot">
                <button type="submit" class="btn btn--primary">Pokaż wymiany</button>
                <a class="btn btn--ghost" href="<?= e(url('wymiany')) ?>">Wyczyść filtry</a>
                <span class="muted" style="font-size:12.5px">
                    Wszystkie filtry działają jednocześnie — możesz np. wybrać urządzenie i zakres dat.
                </span>
            </div>
        </form>
    </div>
</section>

<!-- ================= PODSUMOWANIE ================= -->
<div class="kpi-grid kpi-grid--4">
    <div class="kpi kpi--static">
        <span class="kpi__icon">🔄</span>
        <span class="kpi__value"><?= (int) ($summary['count'] ?? 0) ?></span>
        <span class="kpi__label">Wymian w wynikach</span>
        <span class="kpi__meta">z <?= (int) ($result['total'] ?? 0) ?> wszystkich w bazie</span>
    </div>
    <div class="kpi kpi--static">
        <span class="kpi__icon">📅</span>
        <span class="kpi__value" style="font-size:17px">
            <?= e($summary['date_from'] ? fmt_date((string) $summary['date_from']) : '—') ?>
        </span>
        <span class="kpi__label">Najstarsza wymiana</span>
        <span class="kpi__meta">w wybranym zbiorze</span>
    </div>
    <div class="kpi kpi--static">
        <span class="kpi__icon">🗓️</span>
        <span class="kpi__value" style="font-size:17px">
            <?= e($summary['date_to'] ? fmt_date((string) $summary['date_to']) : '—') ?>
        </span>
        <span class="kpi__label">Najnowsza wymiana</span>
        <span class="kpi__meta">w wybranym zbiorze</span>
    </div>
    <div class="kpi kpi--static">
        <span class="kpi__icon">🔢</span>
        <span class="kpi__value"><?= number_format((float) ($summary['sum_Licznik'] ?? 0), 0, ',', ' ') ?></span>
        <span class="kpi__label">Suma liczników</span>
        <span class="kpi__meta">średnia <?= number_format((float) ($summary['avg_Licznik'] ?? 0), 0, ',', ' ') ?></span>
    </div>
</div>

<!-- ================= TABELA ================= -->
<section class="card">
    <form method="post" action="<?= e(url('bulk-delete')) ?>" id="bulk-form">
        <?= csrf_field() ?>
        <input type="hidden" name="t" value="wymiany">

        <div class="card__head">
            <h2>Lista wymian</h2>
            <span class="badge"><?= (int) $result['total'] ?> rekordów</span>
            <div class="bulkbar">
                <span id="bulk-count" class="muted">Zaznaczono: 0</span>
                <button type="submit" class="btn btn--danger btn--sm" id="bulk-btn" disabled
                        onclick="return confirm('Usunąć zaznaczone wymiany? Operacji nie można cofnąć.');">
                    🗑 Usuń zaznaczone
                </button>
            </div>
        </div>

        <div class="card__body card__body--flush">
            <div class="tablewrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th class="th--check">
                            <input type="checkbox" id="check-all" title="Zaznacz wszystkie na stronie">
                        </th>
                        <th>
                            <a class="th-sort <?= ($result['sort'] ?? '') === 'Data' ? 'is-active' : '' ?>"
                               href="<?= e(url_with(['sort' => 'Data', 'dir' => 'desc', 'page' => 1], 'wymiany')) ?>">Data</a>
                        </th>
                        <th>Urządzenie</th>
                        <th>Model</th>
                        <th>Materiał</th>
                        <th>Pracownik</th>
                        <th class="num">Licznik</th>
                        <th>Uwagi</th>
                        <th class="th--actions">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" class="empty-row">Brak wymian spełniających wybrane kryteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $w): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="<?= e($w['IDwymiany']) ?>" class="row-check">
                                </td>
                                <td class="nowrap">
                                    <a href="<?= e(url('record', ['t' => 'wymiany', 'id' => $w['IDwymiany']])) ?>">
                                        <?= e(fmt_date($w['Data'])) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if (!empty($w['IDdrukarki'])): ?>
                                        <a href="<?= e(url('device', ['id' => $w['IDdrukarki']])) ?>">
                                            <?= e($devices[(string) $w['IDdrukarki']] ?? ('#' . $w['IDdrukarki'])) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="muted">nie przypisano</span>
                                    <?php endif; ?>
                                </td>
                                <td class="muted"><?= e($models[(string) ($w['IDmodel'] ?? '')] ?? '—') ?></td>
                                <td>
                                    <?php if (!empty($w['IDasortyment'])): ?>
                                        <?= e($materials[(string) $w['IDasortyment']] ?? ('#' . $w['IDasortyment'])) ?>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($w['IDpracownik'])): ?>
                                        <?= e($workers[(string) $w['IDpracownik']] ?? ('#' . $w['IDpracownik'])) ?>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num"><?= $w['Licznik'] !== null ? e((string) $w['Licznik']) : '—' ?></td>
                                <td class="muted"><?= e($w['Info'] ?? '') ?: '—' ?></td>
                                <td class="rowactions">
                                    <a class="iconbtn" title="Podgląd" href="<?= e(url('record', ['t' => 'wymiany', 'id' => $w['IDwymiany']])) ?>">👁</a>
                                    <a class="iconbtn" title="Edytuj" href="<?= e(url('form', ['t' => 'wymiany', 'id' => $w['IDwymiany']])) ?>">✎</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <footer class="card__foot pager">
            <span class="pager__info">
                Wyświetlane <strong><?= (int) $result['from'] ?>–<?= (int) $result['to'] ?></strong>
                z <strong><?= (int) $result['total'] ?></strong> wymian
            </span>
            <?php if ((int) $result['pages'] > 1): ?>
                <nav class="pager__nav">
                    <?php $page = (int) $result['page']; $pages = (int) $result['pages']; ?>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => 1], 'wymiany') ?>">« pierwsza</a>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => max(1, $page - 1)], 'wymiany') ?>">‹ poprzednia</a>
                    <?php
                    $start = max(1, min($page - 2, max(1, $pages - 4)));
                    $end   = min($pages, $start + 4);
                    $start = max(1, $end - 4);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <a class="pager__btn <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url_with(['page' => $i], 'wymiany')) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>" href="<?= $page >= $pages ? '#' : url_with(['page' => min($pages, $page + 1)], 'wymiany') ?>">następna ›</a>
                    <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>" href="<?= $page >= $pages ? '#' : url_with(['page' => $pages], 'wymiany') ?>">ostatnia »</a>
                </nav>
            <?php endif; ?>
        </footer>
    </form>
</section>

<p class="hint">
    Wskazówka: zaznacz kilka wymian i usuń je jednym kliknięciem. Zmiany w bazie wykonujesz bez wpływu na jej strukturę.
</p>

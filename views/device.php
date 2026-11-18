<?php
/**
 * Karta urządzenia: dane, materiały eksploatacyjne i historia wymian.
 *
 * @var array $device
 * @var array $history
 * @var array $materials
 * @var array $stats
 * @var array $pracownicy
 */
$modelOptions    = FkResolver::options('model');
$modelName       = $modelOptions[(string) ($device['IDmodel'] ?? '')] ?? 'nieznany model';
$pageTitle       = 'Urządzenie: ' . ($device['Lokalizacja'] ?? '');
$pageSubtitle    = $modelName . ($device['Adres_IP'] ? ' · ' . $device['Adres_IP'] : '');

$materialOptions = FkResolver::options('asortyment', ['Nazwa']);
?>
<div class="toolbar">
    <div class="toolbar__actions">
        <a class="btn btn--ghost" href="<?= e(url('devices')) ?>">← Wszystkie urządzenia</a>
        <?php if (can_write()): ?>
            <a class="btn btn--primary" href="<?= e(url('form', ['t' => 'drukarki', 'id' => $device['IDdrukarki']])) ?>">✎ Edytuj urządzenie</a>
            <a class="btn btn--ghost" href="<?= e(url('form', ['t' => 'wymiany'])) ?>">＋ Dodaj wymianę</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpi-grid kpi-grid--3">
    <div class="kpi kpi--static">
        <span class="kpi__icon">🔄</span>
        <span class="kpi__value"><?= (int) ($stats['wymiany_count'] ?? 0) ?></span>
        <span class="kpi__label">Wymian w historii</span>
        <span class="kpi__meta"><?= e((int) ($stats['ostatnia_data'] ?? 0) > 0 ? 'ostatnia: ' . fmt_date((string) $stats['ostatnia_data']) : 'brak wpisów') ?></span>
    </div>
    <div class="kpi kpi--static">
        <span class="kpi__icon">🔢</span>
        <span class="kpi__value"><?= $stats['ostatni_licznik'] !== null ? e((string) $stats['ostatni_licznik']) : '—' ?></span>
        <span class="kpi__label">Ostatni licznik</span>
        <span class="kpi__meta">stan w chwili ostatniej wymiany</span>
    </div>
    <div class="kpi kpi--static">
        <span class="kpi__icon">📦</span>
        <span class="kpi__value"><?= count($materials) ?></span>
        <span class="kpi__label">Materiałów dla modelu</span>
        <span class="kpi__meta"><?= e($modelName) ?></span>
    </div>
</div>

<div class="grid grid--sidebar">
    <section class="card">
        <header class="card__head">
            <h2>Historia wymian</h2>
            <span class="badge"><?= (int) ($history['total'] ?? 0) ?> wpisów</span>
        </header>

        <div class="card__body card__body--flush">
            <form class="inlineform" method="get" action="<?= e(base_path()) ?>/index.php">
                <input type="hidden" name="r" value="device">
                <input type="hidden" name="id" value="<?= e($device['IDdrukarki']) ?>">
                <input type="search" name="q" value="<?= e(qs('q')) ?>" placeholder="Szukaj w uwagach…">
                <select name="f[IDpracownik]" title="Pracownik">
                    <option value="">— każdy pracownik —</option>
                    <?php foreach ($pracownicy as $pId => $pName): ?>
                        <option value="<?= e($pId) ?>" <?= (string) (qs_str_array('f')['IDpracownik'] ?? '') === (string) $pId ? 'selected' : '' ?>>
                            <?= e($pName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--primary btn--sm">Filtruj</button>
                <?php if (qs('q') !== '' || qs_str_array('f') !== []): ?>
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('device', ['id' => $device['IDdrukarki']])) ?>">Wyczyść</a>
                <?php endif; ?>
            </form>

            <div class="tablewrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th><a class="th-sort <?= ($history['sort'] ?? '') === 'Data' ? 'is-active' : '' ?>"
                               href="<?= e(url_with(['sort' => 'Data', 'dir' => 'desc', 'page' => 1], 'device')) ?>">Data</a></th>
                        <th>Materiał</th>
                        <th>Model</th>
                        <th>Pracownik</th>
                        <th class="num">Licznik</th>
                        <th>Uwagi</th>
                        <th class="th--actions">Akcje</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($history['rows'])): ?>
                        <tr><td colspan="7" class="empty-row">Brak wpisów w historii tego urządzenia.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history['rows'] as $w): ?>
                            <tr>
                                <td class="nowrap"><?= e(fmt_date($w['Data'])) ?></td>
                                <td>
                                    <?php $mat = $materialOptions[(string) ($w['IDasortyment'] ?? '')] ?? null; ?>
                                    <?php if ($mat !== null): ?>
                                        <a href="<?= e(url('record', ['t' => 'asortyment', 'id' => $w['IDasortyment']])) ?>"><?= e($mat) ?></a>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="muted"><?= e(FkResolver::options('model')[(string) ($w['IDmodel'] ?? '')] ?? '—') ?></td>
                                <td>
                                    <?php $pid = (string) ($w['IDpracownik'] ?? ''); ?>
                                    <?php if ($pid !== ''): ?>
                                        <?= e($pracownicy[$pid] ?? ('#' . $pid)) ?>
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
                Wyświetlane <strong><?= (int) $history['from'] ?>–<?= (int) $history['to'] ?></strong>
                z <strong><?= (int) $history['total'] ?></strong> wymian
            </span>
            <?php if ((int) $history['pages'] > 1): ?>
                <nav class="pager__nav">
                    <?php $page = (int) $history['page']; $pages = (int) $history['pages']; ?>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => 1], 'device') ?>">«</a>
                    <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => max(1, $page - 1)], 'device') ?>">‹</a>
                    <?php for ($i = max(1, $page - 2); $i <= min($pages, max(1, $page - 2) + 4); $i++): ?>
                        <a class="pager__btn <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url_with(['page' => $i], 'device')) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>" href="<?= $page >= $pages ? '#' : url_with(['page' => min($pages, $page + 1)], 'device') ?>">›</a>
                </nav>
            <?php endif; ?>
        </footer>
    </section>

    <aside class="sidecol">
        <section class="card">
            <header class="card__head"><h2>Dane urządzenia</h2></header>
            <div class="card__body">
                <dl class="deflist">
                    <dt>Lokalizacja</dt><dd><?= e($device['Lokalizacja'] ?? '—') ?></dd>
                    <dt>Adres IP</dt>
                    <dd class="mono">
                        <?php if (($device['Adres_IP'] ?? '') === '' || $device['Adres_IP'] === '0'): ?>
                            <span class="muted">nie podano</span>
                        <?php else: ?><?= e($device['Adres_IP']) ?><?php endif; ?>
                    </dd>
                    <dt>Model</dt>
                    <dd>
                        <?php if (!empty($device['IDmodel'])): ?>
                            <a href="<?= e(url('record', ['t' => 'model', 'id' => $device['IDmodel']])) ?>"><?= e($modelName) ?></a>
                        <?php else: ?>
                            <span class="muted">nie przypisano</span>
                        <?php endif; ?>
                    </dd>
                    <dt>Uwagi</dt><dd><?= e($device['INFO'] ?? '') ?: '<span class="muted">—</span>' ?></dd>
                    <dt>ID w bazie</dt><dd><code>#<?= e($device['IDdrukarki']) ?></code></dd>
                </dl>
            </div>
        </section>

        <section class="card">
            <header class="card__head">
                <h2>Materiały dla tego modelu</h2>
            </header>
            <div class="card__body card__body--flush">
                <table class="table table--compact">
                    <thead><tr><th>Materiał</th><th class="num">Stan</th></tr></thead>
                    <tbody>
                    <?php if (empty($materials)): ?>
                        <tr><td colspan="2" class="empty-row">Brak materiałów przypisanych do modelu.</td></tr>
                    <?php else: ?>
                        <?php foreach ($materials as $m): ?>
                            <?php $qty = (int) ($m['Ilosc'] ?? 0); ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('record', ['t' => 'asortyment', 'id' => $m['IDasortyment']])) ?>"><?= e($m['Nazwa']) ?></a>
                                    <?php if ($m['Symbol']): ?><small class="muted"><?= e($m['Symbol']) ?></small><?php endif; ?>
                                </td>
                                <td class="num">
                                    <span class="badge <?= $qty < 0 ? 'badge--danger' : ($qty === 0 ? 'badge--warn' : 'badge--ok') ?>"><?= $qty ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!empty($stats['pracownicy'])): ?>
            <section class="card">
                <header class="card__head"><h2>Kto serwisował</h2></header>
                <div class="card__body">
                    <ul class="linklist">
                        <?php foreach ($stats['pracownicy'] as $p): ?>
                            <li>
                                <span><?= e(trim($p['Imie'] . ' ' . $p['Nazwisko'])) ?></span>
                                <span class="badge"><?= (int) $p['ile'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>
    </aside>
</div>

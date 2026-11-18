<?php
/**
 * Wyszukiwarka urządzeń (drukarek) - rejestr z filtrowaniem.
 *
 * @var array $result
 * @var array $modelOptions
 */
$pageTitle    = 'Urządzenia';
$pageSubtitle = 'Rejestr drukarek i innych urządzeń - kliknij, aby zobaczyć historię serwisową';

$rows    = $result['rows'];
$q       = qs('q');
$filters = qs_str_array('f');
?>
<div class="toolbar">
    <form class="toolbar__search" method="get" action="<?= e(base_path()) ?>/index.php">
        <input type="hidden" name="r" value="devices">
        <input type="hidden" name="sort" value="<?= e($result['sort']) ?>">
        <input type="hidden" name="dir" value="<?= e($result['dir']) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Szukaj po lokalizacji, adresie IP, uwagach…">
        <button type="submit" class="btn btn--primary">Szukaj</button>
        <?php if ($q !== '' || $filters !== []): ?>
            <a class="btn btn--ghost" href="<?= e(url('devices')) ?>">Wyczyść</a>
        <?php endif; ?>
    </form>

    <div class="toolbar__actions">
        <details class="dropdown">
            <summary class="btn btn--ghost">Na stronę: <?= (int) $result['per_page'] ?></summary>
            <div class="dropdown__menu">
                <?php foreach ([25, 50, 100, 200] as $size): ?>
                    <a class="dropdown__item <?= (int) $result['per_page'] === $size ? 'is-active' : '' ?>"
                       href="<?= e(url_with(['per_page' => $size, 'page' => 1], 'devices')) ?>"><?= $size ?> rekordów</a>
                <?php endforeach; ?>
            </div>
        </details>
        <?php if (can_write()): ?>
            <a class="btn btn--primary" href="<?= e(url('form', ['t' => 'drukarki'])) ?>">＋ Dodaj urządzenie</a>
        <?php endif; ?>
    </div>
</div>

<details class="filters" <?= $filters !== [] ? 'open' : '' ?>>
    <summary>Filtry</summary>
    <form method="get" action="<?= e(base_path()) ?>/index.php" class="filters__grid">
        <input type="hidden" name="r" value="devices">
        <input type="hidden" name="q" value="<?= e($q) ?>">

        <label class="field field--sm">
            <span class="field__label">Lokalizacja</span>
            <input type="text" name="f[Lokalizacja]" value="<?= e($filters['Lokalizacja'] ?? '') ?>" placeholder="np. KT-Ricoh">
        </label>

        <label class="field field--sm">
            <span class="field__label">Adres IP</span>
            <input type="text" name="f[Adres_IP]" value="<?= e($filters['Adres_IP'] ?? '') ?>" placeholder="192.168.">
        </label>

        <label class="field field--sm">
            <span class="field__label">Model</span>
            <select name="f[IDmodel]">
                <option value="">— wszystkie —</option>
                <?php foreach ($modelOptions as $mId => $mName): ?>
                    <option value="<?= e($mId) ?>" <?= (string) ($filters['IDmodel'] ?? '') === (string) $mId ? 'selected' : '' ?>>
                        <?= e($mName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="filters__buttons">
            <button type="submit" class="btn btn--primary btn--sm">Zastosuj</button>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('devices')) ?>">Wyczyść</a>
        </div>
    </form>
</details>

<section class="card">
    <div class="card__body card__body--flush">
        <div class="tablewrap">
            <table class="table">
                <thead>
                <tr>
                    <th>
                        <a class="th-sort <?= $result['sort'] === 'IDdrukarki' ? 'is-active' : '' ?>"
                           href="<?= e(url_with(['sort' => 'IDdrukarki', 'dir' => 'asc', 'page' => 1], 'devices')) ?>">ID</a>
                    </th>
                    <th>
                        <a class="th-sort <?= $result['sort'] === 'Lokalizacja' ? 'is-active' : '' ?>"
                           href="<?= e(url_with(['sort' => 'Lokalizacja', 'dir' => 'asc', 'page' => 1], 'devices')) ?>">Lokalizacja</a>
                    </th>
                    <th>
                        <a class="th-sort <?= $result['sort'] === 'Adres_IP' ? 'is-active' : '' ?>"
                           href="<?= e(url_with(['sort' => 'Adres_IP', 'dir' => 'asc', 'page' => 1], 'devices')) ?>">Adres IP</a>
                    </th>
                    <th>Model</th>
                    <th>Uwagi</th>
                    <th class="num">Wymian</th>
                    <th class="th--actions">Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="empty-row">Nie znaleziono urządzeń.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $d): ?>
                        <tr>
                            <td class="muted"><?= e($d['IDdrukarki']) ?></td>
                            <td>
                                <a href="<?= e(url('device', ['id' => $d['IDdrukarki']])) ?>"><strong><?= e($d['Lokalizacja']) ?></strong></a>
                            </td>
                            <td class="mono">
                                <?php if (($d['Adres_IP'] ?? '') === '' || $d['Adres_IP'] === '0'): ?>
                                    <span class="muted">brak</span>
                                <?php else: ?>
                                    <?= e($d['Adres_IP']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($modelOptions[(string) ($d['IDmodel'] ?? '')] ?? '—') ?></td>
                            <td class="muted"><?= e($d['INFO'] ?? '') ?: '—' ?></td>
                            <td class="num"><?= (int) ($d['__wymiany'] ?? 0) ?></td>
                            <td class="rowactions">
                                <a class="iconbtn" title="Karta urządzenia" href="<?= e(url('device', ['id' => $d['IDdrukarki']])) ?>">🖨️</a>
                                <a class="iconbtn" title="Edytuj" href="<?= e(url('form', ['t' => 'drukarki', 'id' => $d['IDdrukarki']])) ?>">✎</a>
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
            z <strong><?= (int) $result['total'] ?></strong> urządzeń
        </span>
        <?php if ((int) $result['pages'] > 1): ?>
            <nav class="pager__nav">
                <?php $page = (int) $result['page']; $pages = (int) $result['pages']; ?>
                <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => 1], 'devices') ?>">« pierwsza</a>
                <a class="pager__btn <?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= $page <= 1 ? '#' : url_with(['page' => max(1, $page - 1)], 'devices') ?>">‹ poprzednia</a>
                <?php
                $start = max(1, min($page - 2, $pages - 4));
                $end   = min($pages, $start + 4);
                $start = max(1, $end - 4);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a class="pager__btn <?= $i === $page ? 'is-active' : '' ?>" href="<?= e(url_with(['page' => $i], 'devices')) ?>"><?= $i ?></a>
                <?php endfor; ?>
                <a class="pager__btn <?= $page >= $pages ? 'is-disabled' : '' ?>" href="<?= $page >= $pages ? '#' : url_with(['page' => min($pages, $page + 1)], 'devices') ?>">następna ›</a>
            </nav>
        <?php endif; ?>
    </footer>
</section>

<p class="hint">Podpowiedź: kliknij lokalizację, aby otworzyć kartę urządzenia z pełną historią wymian.</p>

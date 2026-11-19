<?php
/**
 * Pulpit - podsumowanie bazy.
 *
 * @var array $stats
 */
$pageTitle    = 'Pulpit';
$pageSubtitle = 'Podsumowanie stanu magazynu i urządzeń';

$counts        = $stats['counts'] ?? [];
$totalWymiany  = (int) ($stats['wymiany_total'] ?? 0);
$ostatniaWymiana = $stats['ostatnia_wymiana'] ?? null;
?>
<div class="kpi-grid">
    <a class="kpi" href="<?= e(url('table', ['t' => 'drukarki'])) ?>">
        <span class="kpi__icon">🖨️</span>
        <span class="kpi__value"><?= (int) ($counts['drukarki'] ?? 0) ?></span>
        <span class="kpi__label">Urządzeń w rejestrze</span>
        <span class="kpi__meta"><?= (int) ($stats['bez_ip'] ?? 0) ?> bez adresu IP · <?= (int) ($stats['bez_modelu'] ?? 0) ?> bez modelu</span>
    </a>

    <a class="kpi" href="<?= e(url('table', ['t' => 'wymiany'])) ?>">
        <span class="kpi__icon">🔄</span>
        <span class="kpi__value"><?= $totalWymiany ?></span>
        <span class="kpi__label">Wymian w bazie</span>
        <span class="kpi__meta"><?= (int) ($stats['wymiany_30d'] ?? 0) ?> w ostatnich 30 dniach</span>
    </a>

    <a class="kpi" href="<?= e(url('table', ['t' => 'asortyment'])) ?>">
        <span class="kpi__icon">📦</span>
        <span class="kpi__value"><?= (int) ($counts['asortyment'] ?? 0) ?></span>
        <span class="kpi__label">Materiałów eksploatacyjnych</span>
        <span class="kpi__meta kpi__meta--warn"><?= (int) ($stats['do_zamowienia_ilosc'] ?? 0) ?> do zamówienia</span>
    </a>

    <a class="kpi" href="<?= e(url('table', ['t' => 'model'])) ?>">
        <span class="kpi__icon">⚙️</span>
        <span class="kpi__value"><?= (int) ($counts['model'] ?? 0) ?></span>
        <span class="kpi__label">Modeli urządzeń</span>
        <span class="kpi__meta"><?= (int) ($counts['pracownik'] ?? 0) ?> pracowników</span>
    </a>
</div>

<div class="grid grid--2">
    <!-- ================= Ostatnie wymiany ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Ostatnie wymiany</h2>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('table', ['t' => 'wymiany', 'sort' => 'Data', 'dir' => 'desc'])) ?>">Wszystkie →</a>
        </header>
        <div class="card__body card__body--flush">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Urządzenie</th>
                        <th>Materiał</th>
                        <th>Pracownik</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($stats['ostatnie'])): ?>
                    <tr><td colspan="4" class="empty-row">Brak zapisanych wymian.</td></tr>
                <?php else: ?>
                    <?php foreach ($stats['ostatnie'] as $w): ?>
                        <tr>
                            <td class="nowrap"><?= e(fmt_date($w['Data'])) ?></td>
                            <td>
                                <?php $devId = (string) ($w['IDdrukarki'] ?? ''); ?>
                                <?php if ($w['Lokalizacja']): ?>
                                    <?php if ($devId !== ''): ?>
                                        <a href="<?= e(url('device', ['id' => $devId])) ?>"><?= e($w['Lokalizacja']) ?></a>
                                    <?php else: ?>
                                        <?= e($w['Lokalizacja']) ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="muted">nie przypisano</span>
                                <?php endif; ?>
                                <small class="muted"><?= e($w['ModelNazwa'] ?? '') ?></small>
                            </td>
                            <td><?= e($w['MaterialNazwa'] ?? '—') ?></td>
                            <td><?= e(trim(($w['Imie'] ?? '') . ' ' . ($w['Nazwisko'] ?? ''))) ?: '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ================= Modele ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Najczęściej serwisowane modele</h2>
        </header>
        <div class="card__body">
            <?php $max = (int) ($stats['modele_max'] ?? 1); ?>
            <ul class="bars">
                <?php foreach (($stats['modele_stats'] ?? []) as $m): ?>
                    <li class="bars__row">
                        <span class="bars__label" title="<?= e($m['Nazwa']) ?>"><?= e($m['Nazwa']) ?></span>
                        <span class="bars__track">
                            <span class="bars__fill" style="width: <?= max(2, (int) round((int) $m['ile'] / $max * 100)) ?>%"></span>
                        </span>
                        <span class="bars__value"><?= (int) $m['ile'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (empty($stats['modele_stats'])): ?>
                <p class="muted">Brak danych.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid grid--2">
    <!-- ================= Do zamówienia ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Materiały do zamówienia</h2>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('table', ['t' => 'asortyment', 'f' => ['Ilosc' => '0']])) ?>">Otwórz →</a>
        </header>
        <div class="card__body card__body--flush">
            <table class="table table--compact">
                <thead>
                    <tr>
                        <th>Nazwa</th>
                        <th>Model</th>
                        <th class="num">Stan</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($stats['do_zamowienia'])): ?>
                    <tr><td colspan="3" class="empty-row">Wszystkie materiały są na stanie.</td></tr>
                <?php else: ?>
                    <?php foreach ($stats['do_zamowienia'] as $m): ?>
                        <tr>
                            <td><?= e($m['Nazwa']) ?><?php if ($m['Symbol']): ?><small class="muted"><?= e($m['Symbol']) ?></small><?php endif; ?></td>
                            <td class="muted"><?= e($m['ModelNazwa'] ?? '—') ?></td>
                            <td class="num">
                                <?php $qty = (int) ($m['Ilosc'] ?? 0); ?>
                                <span class="badge <?= $qty < 0 ? 'badge--danger' : 'badge--warn' ?>"><?= $qty ?></span>
                            </td>
                            <td class="right nowrap">
                                <?php if (can_write()): ?>
                                    <form method="post" action="<?= e(url('ignoruj-zakup')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $m['IDasortyment']) ?>">
                                        <input type="hidden" name="back" value="dashboard">
                                        <button type="submit" class="btn btn--ghost btn--sm"
                                                title="Pomiń ten materiał przy zamówieniu (np. wartość archiwalna)">
                                            ⛔ Pomiń
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($stats['pominiete_zakup'])): ?>
                <div class="card__foot">
                    <div class="pominiete">
                        <strong>Pominięte przy zamówieniu (<?= count($stats['pominiete_zakup']) ?>):</strong>
                        <?php foreach ($stats['pominiete_zakup'] as $pm): ?>
                            <span class="chip">
                                <?= e($pm['Nazwa']) ?>
                                <?php if (can_write()): ?>
                                    <form method="post" action="<?= e(url('ignoruj-zakup')) ?>" class="inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $pm['IDasortyment']) ?>">
                                        <input type="hidden" name="back" value="dashboard">
                                        <button type="submit" class="chip__x" title="Przywróć do zamówienia">×</button>
                                    </form>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= Pracownicy i lokalizacje ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Obciążenie pracowników</h2>
        </header>
        <div class="card__body">
            <ul class="bars">
                <?php foreach (($stats['pracownicy'] ?? []) as $p): ?>
                    <li class="bars__row">
                        <span class="bars__label" title="<?= e(trim($p['Imie'] . ' ' . $p['Nazwisko'])) ?>">
                            <?= e(trim($p['Imie'] . ' ' . $p['Nazwisko'])) ?>
                        </span>
                        <span class="bars__track">
                            <span class="bars__fill bars__fill--alt" style="width: <?= $totalWymiany > 0 ? max(2, (int) round((int) $p['ile'] / max(1, (int) $stats['pracownicy'][0]['ile']) * 100)) : 0 ?>%"></span>
                        </span>
                        <span class="bars__value"><?= (int) $p['ile'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h3 class="subtitle">Najczęściej serwisowane lokalizacje</h3>
            <ul class="bars">
                <?php foreach (($stats['top_lokalizacje'] ?? []) as $l): ?>
                    <li class="bars__row">
                        <span class="bars__label" title="<?= e($l['Lokalizacja']) ?>"><?= e($l['Lokalizacja']) ?></span>
                        <span class="bars__track">
                            <span class="bars__fill" style="width: <?= $totalWymiany > 0 ? max(2, (int) round((int) $l['ile'] / max(1, (int) $stats['top_lokalizacje'][0]['ile']) * 100)) : 0 ?>%"></span>
                        </span>
                        <span class="bars__value"><?= (int) $l['ile'] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>
</div>

<div class="grid grid--2">
    <!-- ================= Wymiany w miesiącach ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Wymiany w ostatnich 12 miesiącach</h2>
        </header>
        <div class="card__body">
            <?php
            $mies = $stats['miesiace'] ?? [];
            $maxM = 1;
            foreach ($mies as $m) {
                $maxM = max($maxM, (int) $m['ile']);
            }
            $sumaM = 0;
            foreach ($mies as $m) {
                $sumaM += (int) $m['ile'];
            }
            $sredniaM = $mies ? (int) round($sumaM / count($mies)) : 0;
            $maxIdx = 0;
            foreach ($mies as $i => $m) {
                if ((int) $m['ile'] === $maxM && $maxM > 0) {
                    $maxIdx = $i;
                }
            }
            $ostatni = $mies ? (int) end($mies)['ile'] : 0;
            $poprzedni = count($mies) > 1 ? (int) $mies[count($mies) - 2]['ile'] : null;
            $roznica = $poprzedni === null ? null : $ostatni - $poprzedni;
            ?>
            <?php if (empty($mies)): ?>
                <p class="muted">Brak danych w ostatnich 12 miesiącach.</p>
            <?php else: ?>
                <?php
                // Siatka pozioma: 0 %, 25 %, 50 %, 75 %, 100 % wartości maksymalnej.
                $gridValues = [1.0, 0.75, 0.5, 0.25];
                ?>
                <div class="chart2">
                    <div class="chart2__axiswrap">
                        <div class="chart2__axis">
                            <?php foreach ($gridValues as $g): ?>
                                <span class="chart2__axislabel" style="bottom: <?= (int) round($g * 100) ?>%">
                                    <?= (int) round($maxM * $g) ?>
                                </span>
                            <?php endforeach; ?>
                            <span class="chart2__axislabel chart2__axislabel--zero" style="bottom: 0">0</span>
                        </div>
                    </div>

                    <div class="chart2__right">
                        <div class="chart2__plot">
                            <?php foreach ($gridValues as $g): ?>
                                <span class="chart2__grid" style="bottom: <?= (int) round($g * 100) ?>%"></span>
                            <?php endforeach; ?>

                            <?php foreach ($mies as $i => $m): ?>
                                <?php
                                $rokM = substr((string) $m['mies'], 0, 4);
                                $mm   = substr((string) $m['mies'], 5, 2);
                                $od   = $rokM . '-' . $mm . '-01';
                                $do   = date('Y-m-t', strtotime($od));
                                $wys  = (int) $m['ile'];
                                ?>
                                <a class="chart2__col<?= $i === $maxIdx && $maxM > 0 ? ' is-max' : '' ?>"
                                   href="<?= e(url('wymiany', ['f' => ['Data' => ['from' => $od, 'to' => $do]]])) ?>"
                                   title="<?= e($mm . '/' . $rokM) ?> — <?= $wys ?> wymian (kliknij, aby zobaczyć)">
                                    <span class="chart2__value"><?= $wys ?></span>
                                    <span class="chart2__bar" style="height: <?= $maxM > 0 ? max(2, (int) round($wys / $maxM * 100)) : 0 ?>%"></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="chart2__labels">
                            <?php foreach ($mies as $m): ?>
                                <?php
                                $rokM = substr((string) $m['mies'], 0, 4);
                                $mm   = substr((string) $m['mies'], 5, 2);
                                ?>
                                <span class="chart2__label"><?= e($mm) ?><small>/<?= e(substr($rokM, 2)) ?></small></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="chart2__summary">
                    <span><strong><?= number_format($sumaM, 0, ',', ' ') ?></strong> łącznie</span>
                    <span><strong><?= number_format($sredniaM, 0, ',', ' ') ?></strong> średnio / miesiąc</span>
                    <span><strong><?= number_format($maxM, 0, ',', ' ') ?></strong> najwięcej (<?= e(substr((string) ($mies[$maxIdx]['mies'] ?? ''), 5)) ?>/<?= e(substr((string) ($mies[$maxIdx]['mies'] ?? ''), 0, 4)) ?>)</span>
                    <?php if ($roznica !== null): ?>
                        <span>
                            <strong><?= $roznica > 0 ? '+' : '' ?><?= number_format($roznica, 0, ',', ' ') ?></strong>
                            ostatni miesiąc vs poprzedni
                        </span>
                    <?php endif; ?>
                </div>

                <details class="chart2__table">
                    <summary>Podgląd miesięcy w liczbach</summary>
                    <table class="table table--detail">
                        <thead>
                            <tr><th>Miesiąc</th><th class="num">Wymiany</th><th class="num">Zmiana</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($mies as $i => $m): ?>
                            <?php
                            $rokM = substr((string) $m['mies'], 0, 4);
                            $mM   = substr((string) $m['mies'], 5, 2);
                            $odM  = $rokM . '-' . $mM . '-01';
                            $doM  = date('Y-m-t', strtotime($odM));
                            $pop  = $i > 0 ? (int) $mies[$i - 1]['ile'] : null;
                            $delta = $pop === null ? null : (int) $m['ile'] - $pop;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= e(url('wymiany', ['f' => ['Data' => ['from' => $odM, 'to' => $doM]]])) ?>">
                                        <?= e($mM . '/' . $rokM) ?>
                                    </a>
                                </td>
                                <td class="num"><?= (int) $m['ile'] ?></td>
                                <td class="num">
                                    <?php if ($delta === null): ?>
                                        <span class="muted">—</span>
                                    <?php elseif ($delta > 0): ?>
                                        <span class="badge badge--ok">+<?= $delta ?></span>
                                    <?php elseif ($delta < 0): ?>
                                        <span class="badge badge--danger"><?= $delta ?></span>
                                    <?php else: ?>
                                        <span class="muted">0</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </details>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= Szybkie akcje ================= -->
    <section class="card">
        <header class="card__head">
            <h2>Szybkie akcje</h2>
        </header>
        <div class="card__body">
            <div class="quick">
                <?php foreach (Schema::tables() as $tName => $tInfo): ?>
                    <?php if (can_write()): ?>
                        <a class="quick__item" href="<?= e(url('form', ['t' => $tName])) ?>">
                            <span class="quick__icon"><?= e($tInfo['icon'] ?? '🗂️') ?></span>
                            <span>
                                <strong>Dodaj: <?= e($tInfo['label_one'] ?? $tInfo['label']) ?></strong>
                                <small><?= (int) $tInfo['count'] ?> rekordów w bazie</small>
                            </span>
                        </a>
                    <?php else: ?>
                        <div class="quick__item quick__item--static">
                            <span class="quick__icon"><?= e($tInfo['icon'] ?? '🗂️') ?></span>
                            <span>
                                <strong><?= e($tInfo['label']) ?></strong>
                                <small><?= (int) $tInfo['count'] ?> rekordów w bazie</small>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>

<?php if ((int) ($stats['wymiany_bez_urz'] ?? 0) > 0): ?>
    <div class="alert alert--info">
        W bazie jest <strong><?= (int) $stats['wymiany_bez_urz'] ?></strong> wymian bez przypisanego urządzenia
        - warto je uzupełnić, aby historia serwisowa była kompletna.
    </div>
<?php endif; ?>

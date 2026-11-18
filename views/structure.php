<?php
/**
 * Struktura bazy - opis tabel i kolumn (słownik panelu + dane z INFORMATION_SCHEMA).
 *
 * @var array $tables
 */
$pageTitle    = 'Struktura bazy';
$pageSubtitle = 'Tabele i kolumny wykryte automatycznie w Twojej bazie danych';
?>
<div class="card">
    <div class="card__body">
        <p class="hint" style="margin:0">
            Panel sam odczytuje strukturę bazy z <code>INFORMATION_SCHEMA</code>.
            Opisy pól dla znanych tabel (etykiety, wyjaśnienia) znajdują się w pliku
            <code>app/Schema.php</code> — możesz je tam edytować lub dodać opisy dla nowych tabel.
            Każda nowa tabela w bazie pojawi się tutaj automatycznie, nawet bez opisu.
        </p>
    </div>
</div>

<?php foreach ($tables as $tName => $tData): ?>
    <section class="card">
        <header class="card__head">
            <h2><?= e($tData['info']['icon'] ?? '') ?> <?= e($tData['info']['label']) ?></h2>
            <span class="badge"><?= (int) $tData['info']['count'] ?> rekordów</span>
            <a class="btn btn--ghost btn--sm" href="<?= e(url('table', ['t' => $tName])) ?>">Otwórz →</a>
        </header>
        <div class="card__body">
            <p class="hint"><?= e($tData['info']['description']) ?></p>
            <div class="tablewrap">
                <table class="table table--compact">
                    <thead>
                    <tr>
                        <th>Kolumna</th>
                        <th>Opis pola</th>
                        <th>Typ w bazie</th>
                        <th>Właściwości</th>
                        <th>Powiązanie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tData['columns'] as $cName => $col): ?>
                        <tr>
                            <td>
                                <code><?= e($cName) ?></code>
                                <?php if ($col['pk']): ?><span class="badge badge--ok">PK</span><?php endif; ?>
                            </td>
                            <td>
                                <?= e($col['desc'] !== '' ? $col['desc'] : '<span class="muted">brak opisu</span>') ?>
                            </td>
                            <td class="mono muted"><?= e($col['db_type']) ?></td>
                            <td>
                                <?php if ($col['required']): ?><span class="badge badge--warn">wymagane</span><?php endif; ?>
                                <?php if ($col['nullable']): ?><span class="badge">NULL</span><?php endif; ?>
                                <?php if ($col['auto_increment']): ?><span class="badge badge--ok">AUTO_INCREMENT</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($col['ref'])): ?>
                                    <a href="<?= e(url('table', ['t' => $col['ref']])) ?>">→ <?= e(Schema::label($col['ref'])) ?></a>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endforeach; ?>

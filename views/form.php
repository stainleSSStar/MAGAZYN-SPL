<?php
/**
 * Formularz dodawania / edycji rekordu - generowany na podstawie schematu.
 *
 * @var string $table
 * @var array  $meta
 * @var array|null $row
 * @var array $values
 * @var array $errors
 * @var bool  $isEdit
 * @var int|null $suggestedId
 * @var array $options
 * @var array $propagate
 */
$pageTitle    = $isEdit ? 'Edycja: ' . $meta['label_one'] : 'Nowy: ' . $meta['label_one'];
$pageSubtitle = $meta['description'];
$activeTable  = $table;

$columns = $meta['columns'];
$pk      = $meta['pk'];

// Ręczna kolejność pól zdefiniowana dla tabeli (np. „Urządzenie” przed „Model”).
// Kolumny nie wymienione na liście zostają na końcu, w kolejności z bazy.
if (!empty($meta['form_order'])) {
    $ordered = [];
    foreach ($meta['form_order'] as $cName) {
        if (isset($columns[$cName])) {
            $ordered[$cName] = $columns[$cName];
        }
    }
    foreach ($columns as $cName => $col) {
        if (!isset($ordered[$cName])) {
            $ordered[$cName] = $col;
        }
    }
    $columns = $ordered;
}

/** Pobiera bieżącą wartość pola z formularza / rekordu. */
$val = static function (string $name) use ($values, $columns) {
    if (array_key_exists($name, $values)) {
        $v = $values[$name];
        return $v === null ? '' : (string) $v;
    }
    return '';
};
?>
<?php if (!empty($errors['_'])): ?>
    <div class="alert alert--danger"><?= e($errors['_']) ?></div>
<?php endif; ?>

<?php if ($isEdit && $row !== null): ?>
    <div class="alert alert--info">
        Edytujesz rekord <strong>#<?= e($row[$pk] ?? '') ?></strong> w tabeli „<?= e($meta['label']) ?>”.
        <a href="<?= e(url('record', ['t' => $table, 'id' => $row[$pk]])) ?>">Przejdź do podglądu →</a>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('form', ['t' => $table] + ($isEdit ? ['id' => $row[$pk] ?? ''] : []))) ?>" class="formcard">
    <?= csrf_field() ?>

    <?php if (!can_write()): ?>
        <div class="alert alert--info" style="margin:18px 22px 0">
            <span>Twoje konto ma uprawnienia <strong>tylko odczyt</strong> — formularz jest podglądany, nie możesz zapisać zmian.</span>
        </div>
    <?php endif; ?>

    <fieldset <?= can_write() ? '' : 'disabled' ?> class="formcard__fieldset">
    <div class="formcard__grid">
        <?php foreach ($columns as $cName => $col): ?>
            <?php
            // Klucz główny AUTO_INCREMENT ustawia baza - go nie pokazujemy.
            if ($col['auto_increment']) {
                continue;
            }
            $err  = $errors[$cName] ?? null;
            $type = $col['type'];
            $isPk = $col['pk'];
            ?>
            <div class="formfield <?= $err ? 'has-error' : '' ?>">
                <label class="field" for="fld_<?= e($cName) ?>">
                    <span class="field__label">
                        <?= e($col['label']) ?>
                        <?php if ($col['required']): ?><span class="req" title="Pole wymagane">*</span><?php endif; ?>
                        <?php if ($col['desc'] !== ''): ?>
                            <span class="field__info" title="<?= e($col['desc']) ?>">ⓘ</span>
                        <?php endif; ?>
                    </span>

                    <?php if (!empty($col['ref'])): ?>
                        <?php $opts = $options[$cName] ?? []; ?>
                        <select name="<?= e($cName) ?>" id="fld_<?= e($cName) ?>"
                                data-filterable="1"
                                <?= !empty($col['filter_by']) ? 'data-filter-by="' . e($col['filter_by']) . '"' : '' ?>
                                <?= !empty($col['set_model']) ? 'data-set-model="' . e($col['set_model']) . '"' : '' ?>
                                <?= $col['required'] ? 'required' : '' ?>>
                            <option value="">— wybierz —</option>
                            <?php foreach ($opts as $optVal => $optInfo): ?>
                                <option value="<?= e($optVal) ?>"
                                        <?= !empty($optInfo['has_model']) ? 'data-model="' . e((string) ($optInfo['model'] ?? '')) . '"' : '' ?>
                                        <?= (string) $val($cName) === (string) $optVal ? 'selected' : '' ?>>
                                    <?= e($optInfo['label']) ?> (#<?= e($optVal) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="search" class="selectfilter" placeholder="filtruj listę…" data-target="fld_<?= e($cName) ?>">
                        <?php if (!empty($col['filter_by'])): ?>
                            <small class="field__hint" data-model-hint="fld_<?= e($cName) ?>"></small>
                        <?php endif; ?>

                    <?php elseif ($type === 'textarea'): ?>
                        <textarea name="<?= e($cName) ?>" id="fld_<?= e($cName) ?>" rows="4"
                                  maxlength="<?= $col['max_length'] ?? 1000 ?>"
                                  <?= $col['required'] ? 'required' : '' ?>><?= e($val($cName)) ?></textarea>

                    <?php elseif ($type === 'date'): ?>
                        <input type="date" name="<?= e($cName) ?>" id="fld_<?= e($cName) ?>" class="datepick" data-calendar="1"
                               value="<?= e($val($cName)) ?>" <?= $col['required'] ? 'required' : '' ?>>
                        <small class="field__hint">Kliknij w pole, aby otworzyć kalendarz.</small>

                    <?php elseif ($type === 'int'): ?>
                        <?php
                        $hint = '';
                        if ($isPk && $suggestedId !== null && !$isEdit) {
                            $hint = 'Następny wolny numer: ' . $suggestedId;
                        }
                        ?>
                        <input type="number" step="1" name="<?= e($cName) ?>" id="fld_<?= e($cName) ?>"
                               value="<?= e($val($cName) !== '' ? $val($cName) : ($isPk && $suggestedId !== null ? $suggestedId : '')) ?>"
                               <?= $col['required'] ? 'required' : '' ?>>
                        <?php if ($hint !== ''): ?><small class="field__hint"><?= e($hint) ?></small><?php endif; ?>

                    <?php else: ?>
                        <input type="<?= $type === 'ip' ? 'text' : 'text' ?>" name="<?= e($cName) ?>" id="fld_<?= e($cName) ?>"
                               value="<?= e($val($cName)) ?>"
                               maxlength="<?= $col['max_length'] ?? 255 ?>"
                               <?= $col['required'] ? 'required' : '' ?>>
                    <?php endif; ?>

                    <?php if ($err): ?>
                        <span class="field__error">⚠ <?= e($err) ?></span>
                    <?php elseif ($col['desc'] !== ''): ?>
                        <small class="field__hint"><?= e($col['desc']) ?></small>
                    <?php endif; ?>
                </label>

                <?php if (!empty($propagate[$cName])):
                    $propChecked = !empty($_POST['_propagate_' . $cName]);
                    ?>
                    <label class="checkline">
                        <input type="checkbox" name="_propagate_<?= e($cName) ?>" value="1" <?= $propChecked ? 'checked' : '' ?>>
                        <span>
                            Przepisz „<?= e($propagate[$cName]['label']) ?>” także
                            w <?= (int) $propagate[$cName]['count'] ?> wymianach tego urządzenia
                            <small class="checkline__hint">
                                <?= (int) $propagate[$cName]['count'] > 0
                                    ? 'Bez tego checkboxa stary model zostanie w historii wymian.'
                                    : 'To urządzenie nie ma jeszcze żadnych wymian.' ?>
                            </small>
                        </span>
                    </label>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="formcard__foot">
        <?php if (can_write()): ?>
            <button type="submit" class="btn btn--primary"><?= $isEdit ? '💾 Zapisz zmiany' : '💾 Dodaj rekord' ?></button>
            <?php if (!$isEdit): ?>
                <button type="submit" name="_again" value="1" class="btn btn--ghost">Zapisz i dodaj kolejny</button>
            <?php endif; ?>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('table', ['t' => $table])) ?>">Anuluj</a>
        <a class="btn btn--link" href="<?= e(url('structure')) ?>">Opis struktury bazy</a>
    </div>
    </fieldset>
</form>

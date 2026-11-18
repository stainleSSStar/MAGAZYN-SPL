<?php
/**
 * Strona błędu.
 *
 * @var string $title
 * @var string $message
 */
$pageTitle = $title ?? 'Błąd';
?>
<div class="card">
    <div class="card__body empty-state">
        <div class="empty-state__icon">⚠️</div>
        <h2><?= e($pageTitle) ?></h2>
        <p><?= e($message) ?></p>
        <a class="btn btn--primary" href="<?= e(url('dashboard')) ?>">Wróć do pulpitu</a>
    </div>
</div>

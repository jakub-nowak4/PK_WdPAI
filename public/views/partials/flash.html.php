<?php
/**
 * Komunikat flash. $flash: null albo ['type' => 'success'|'info'|'error', 'message' => string]
 * (tekst juz przetlumaczony). Kazdy inny ksztalt nic nie wypisuje.
 */
$flashType = is_array($flash ?? null) ? ($flash['type'] ?? null) : null;
$flashMessage = is_array($flash ?? null) ? ($flash['message'] ?? null) : null;
$flashMessage = is_string($flashMessage) ? trim($flashMessage) : '';
?>
<?php if ($flashMessage !== '' && in_array($flashType, ['success', 'info'], true)): ?>
    <p class="alert alert--<?= $flashType ?>" role="status"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php elseif ($flashMessage !== '' && $flashType === 'error'): ?>
    <p class="alert alert--error" role="alert"><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<?php
/**
 * Alert formularza (komunikat ogolny, np. zly e-mail lub haslo, wygasla sesja).
 * @var callable $t
 * @var callable $e
 * @var string|null $formError komunikat bledu (role="alert"); pusty = brak
 * @var string|null $formInfo komunikat informacyjny (role="status"); pusty = brak
 */
$formError = (string) ($formError ?? '');
$formInfo = (string) ($formInfo ?? '');
?>
<?php if ($formError !== ''): ?>
    <p class="alert alert--error" role="alert"><?= $e($formError) ?></p>
<?php endif; ?>
<?php if ($formInfo !== ''): ?>
    <p class="alert alert--info" role="status"><?= $e($formInfo) ?></p>
<?php endif; ?>

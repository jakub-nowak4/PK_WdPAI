<?php
/**
 * Komunikaty walidacji dla public/js/auth-form.js (JSON z tlumaczen PL/EN) i sam skrypt.
 * @var callable $t
 */
$keys = [
    'validation.required' => 'To pole jest wymagane.',
    'validation.email' => 'Podaj poprawny adres e-mail.',
    'validation.min_length' => 'Minimalna liczba znaków: {min}.',
    'validation.max_length' => 'Maksymalna liczba znaków: {max}.',
    'validation.password_max_bytes' => 'Hasło może mieć najwyżej {max} bajty.',
    'validation.password_mismatch' => 'Hasła nie są takie same.',
    'validation.invalid_format' => 'Niepoprawny format.',
];
$messages = [];
foreach ($keys as $key => $default) {
    $messages[$key] = $t($key, $default);
}
?>
<script type="application/json" id="validation-messages"><?= json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script type="module" src="/js/auth-form.js"></script>

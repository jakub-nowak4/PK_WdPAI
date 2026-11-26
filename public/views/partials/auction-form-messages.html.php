<?php
/**
 * Komunikaty walidacji dla public/js/company/auction-form.js (JSON z tlumaczen PL/EN) i sam skrypt.
 * @var callable $t
 */
$keys = [
    'validation.required' => 'To pole jest wymagane.',
    'validation.min_length' => 'Minimalna liczba znaków: {min}.',
    'validation.max_length' => 'Maksymalna liczba znaków: {max}.',
    'validation.invalid_format' => 'Niepoprawny format.',
    'validation.number_range' => 'Podaj liczbę całkowitą od {min} do {max}.',
    'validation.ends_too_soon' => 'Aukcja musi trwać co najmniej 1 godzinę.',
    'validation.ends_too_late' => 'Aukcja może trwać najwyżej 30 dni.',
    'photo.required' => 'Dodaj co najmniej jedno zdjęcie.',
    'photo.too_many' => 'Możesz dodać najwyżej {max} zdjęć.',
    'photo.too_big' => 'Plik {name} jest za duży (limit {max} MB).',
    'photo.bad_type' => 'Plik {name} ma niedozwolony format. Dozwolone: JPG, PNG, WebP.',
    'photo.cover' => 'okładka',
];
$messages = [];
foreach ($keys as $key => $default) {
    $messages[$key] = $t($key, $default);
}
?>
<script type="application/json" id="validation-messages"><?= json_encode($messages, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script type="module" src="/js/company/auction-form.js"></script>

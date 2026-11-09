<?php
/**
 * Dodaj aukcje (ekran 15): GET /company/auctions/new, POST /company/auctions (multipart/form-data).
 * Opcjonalne zmienne od kontrolera: $errors (pole => komunikat; zdjecia pod kluczem 'photos'), $old (wartosci tekstowe),
 * $brands (slug => nazwa; domyslnie krotka lista przykladowa), $formError, $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errors = $errors ?? [];
$old = $old ?? [];
$csrfToken = (string) ($csrfToken ?? '');
$brands = $brands ?? ['audi' => 'Audi', 'bmw' => 'BMW', 'dodge' => 'Dodge', 'ford' => 'Ford', 'jeep' => 'Jeep', 'porsche' => 'Porsche'];

$fuels = [
    'petrol' => $t('fuel.petrol', 'Benzyna'), 'diesel' => $t('fuel.diesel', 'Diesel'), 'hybrid' => $t('fuel.hybrid', 'Hybryda'),
    'electric' => $t('fuel.electric', 'Elektryczny'), 'lpg' => $t('fuel.lpg', 'LPG'),
];
$transmissions = ['manual' => $t('transmission.manual', 'Manualna'), 'automatic' => $t('transmission.automatic', 'Automatyczna')];
$countries = [
    'US' => $t('country.US', 'USA'), 'CA' => $t('country.CA', 'Kanada'), 'DE' => $t('country.DE', 'Niemcy'),
    'FR' => $t('country.FR', 'Francja'), 'NL' => $t('country.NL', 'Holandia'), 'BE' => $t('country.BE', 'Belgia'),
    'IT' => $t('country.IT', 'Włochy'), 'ES' => $t('country.ES', 'Hiszpania'), 'SE' => $t('country.SE', 'Szwecja'),
    'CH' => $t('country.CH', 'Szwajcaria'), 'GB' => $t('country.GB', 'Wielka Brytania'), 'JP' => $t('country.JP', 'Japonia'),
    'KR' => $t('country.KR', 'Korea Południowa'),
];
$zl = $t('unit.zl', 'zł');
$increments = [];
foreach ([100, 200, 500, 1000, 2000] as $step) {
    $increments[(string) $step] = number_format($step, 0, ',', "\u{00A0}") . "\u{00A0}" . $zl;
}
$photoError = (string) ($errors['photos'] ?? '');
$descriptionError = (string) ($errors['description'] ?? '');
$descriptionDescribedBy = trim('field-description-hint' . ($descriptionError !== '' ? ' field-description-error' : ''));
?>
<main class="page page--form" id="main-content">
    <nav class="breadcrumbs" aria-label="<?= $e($t('nav.breadcrumbs', 'Okruszki')) ?>">
        <ol class="breadcrumbs__list">
            <li><a href="/company"><?= $e($t('company.eyebrow', 'Panel firmy')) ?></a></li>
            <li aria-current="page"><?= $e($t('create.title', 'Dodaj nową aukcję pojazdu')) ?></li>
        </ol>
    </nav>

    <header class="page__header">
        <div>
            <h1 class="page__title"><?= $e($t('create.title', 'Dodaj nową aukcję pojazdu')) ?></h1>
            <p class="page__lead"><?= $e($t('create.lead', 'Uzupełnij dane auta, dodaj zdjęcia i ustal warunki licytacji.')) ?></p>
        </div>
    </header>

    <form class="card auction-form" method="post" action="/company/auctions" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
        <?php require __DIR__ . '/../partials/form-errors.html.php'; ?>

        <fieldset class="auction-form__section">
            <legend class="auction-form__legend"><?= $e($t('create.section_vehicle', 'Dane pojazdu')) ?></legend>
            <?php
            $field = ['name' => 'title', 'label' => $t('field.auction_title', 'Tytuł ogłoszenia'), 'required' => true,
                'minlength' => 5, 'maxlength' => 120, 'hint' => $t('field.auction_title_hint', 'Od 5 do 120 znaków, np. 2018 Ford Mustang GT')];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'brand', 'label' => $t('field.brand', 'Marka'), 'required' => true, 'options' => $brands,
                'placeholder' => $t('field.brand_choose', 'Wybierz markę')];
            require __DIR__ . '/../partials/form-select.html.php';

            $field = ['name' => 'model', 'label' => $t('field.model', 'Model i wersja'), 'required' => true, 'minlength' => 1, 'maxlength' => 60];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'year', 'label' => $t('field.year', 'Rok produkcji'), 'type' => 'number', 'inputmode' => 'numeric',
                'required' => true, 'min' => 1950, 'max' => (int) date('Y') + 1, 'step' => 1];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'mileageKm', 'label' => $t('field.mileage', 'Przebieg (km)'), 'type' => 'number', 'inputmode' => 'numeric',
                'required' => true, 'min' => 0, 'max' => 2000000, 'step' => 1];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'vin', 'label' => $t('field.vin', 'Numer VIN (opcjonalnie)'), 'minlength' => 17, 'maxlength' => 17,
                'pattern' => '[A-HJ-NPR-Z0-9]{17}', 'placeholder' => '1FA6P8CF5J5100482',
                'hint' => $t('field.vin_hint', '17 znaków: wielkie litery i cyfry, bez I, O i Q')];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'fuel', 'label' => $t('field.fuel', 'Rodzaj paliwa'), 'required' => true, 'options' => $fuels,
                'placeholder' => $t('field.choose', 'Wybierz')];
            require __DIR__ . '/../partials/form-select.html.php';

            $field = ['name' => 'transmission', 'label' => $t('field.transmission', 'Skrzynia biegów'), 'required' => true,
                'options' => $transmissions, 'placeholder' => $t('field.choose', 'Wybierz')];
            require __DIR__ . '/../partials/form-select.html.php';

            $field = ['name' => 'originCountry', 'label' => $t('field.origin_country', 'Kraj pochodzenia'), 'required' => true,
                'options' => $countries, 'placeholder' => $t('field.choose', 'Wybierz')];
            require __DIR__ . '/../partials/form-select.html.php';
            ?>
        </fieldset>

        <fieldset class="auction-form__section">
            <legend class="auction-form__legend"><?= $e($t('create.section_description', 'Opis pojazdu')) ?></legend>
            <div class="form-field<?= $descriptionError !== '' ? ' form-field--error' : '' ?>">
                <label class="form-field__label" for="field-description"><?= $e($t('field.description', 'Opis')) ?></label>
                <textarea class="form-field__control" id="field-description" name="description" rows="6" maxlength="5000"
                          data-counter-target="description-counter"
                          <?php if ($descriptionError !== ''): ?>aria-invalid="true"<?php endif; ?>
                          aria-describedby="<?= $e($descriptionDescribedBy) ?>"><?= $e($old['description'] ?? '') ?></textarea>
                <p class="form-field__hint" id="field-description-hint">
                    <?= $e($t('field.description_hint', 'Do 5000 znaków.')) ?>
                    <span id="description-counter"><?= mb_strlen((string) ($old['description'] ?? '')) ?> / 5000</span>
                </p>
                <?php if ($descriptionError !== ''): ?>
                    <p class="form-field__error" id="field-description-error" role="alert"><?= $e($descriptionError) ?></p>
                <?php endif; ?>
            </div>
        </fieldset>

        <fieldset class="auction-form__section">
            <legend class="auction-form__legend"><?= $e($t('create.section_photos', 'Zdjęcia')) ?></legend>
            <div class="form-field<?= $photoError !== '' ? ' form-field--error' : '' ?>">
                <label class="form-field__label" for="field-photos">
                    <?= $e($t('field.photos', 'Zdjęcia pojazdu')) ?> <span aria-hidden="true">*</span>
                </label>
                <input class="form-field__control form-field__control--file" id="field-photos" name="photos[]" type="file" multiple required
                       accept="image/jpeg,image/png,image/webp"
                       <?php if ($photoError !== ''): ?>aria-invalid="true"<?php endif; ?>
                       aria-describedby="field-photos-hint<?= $photoError !== '' ? ' field-photos-error' : '' ?>">
                <p class="form-field__hint" id="field-photos-hint">
                    <?= $e($t('field.photos_hint', 'JPG, PNG lub WebP, do 5 MB na plik, od 1 do 8 zdjęć. Pierwsze zdjęcie będzie okładką. Po błędzie formularza wybierz pliki ponownie.')) ?>
                </p>
                <?php if ($photoError !== ''): ?>
                    <p class="form-field__error" id="field-photos-error" role="alert"><?= $e($photoError) ?></p>
                <?php endif; ?>
            </div>
        </fieldset>

        <fieldset class="auction-form__section">
            <legend class="auction-form__legend"><?= $e($t('create.section_terms', 'Parametry licytacji')) ?></legend>
            <?php
            $field = ['name' => 'startingPrice', 'label' => $t('field.starting_price', 'Cena wywoławcza (zł)'), 'type' => 'number',
                'inputmode' => 'numeric', 'required' => true, 'min' => 100, 'max' => 10000000, 'step' => 1];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'minIncrement', 'label' => $t('field.min_increment', 'Minimalny krok licytacji'), 'required' => true,
                'options' => $increments, 'placeholder' => $t('field.choose', 'Wybierz')];
            require __DIR__ . '/../partials/form-select.html.php';

            $field = ['name' => 'startsAt', 'label' => $t('field.starts_at', 'Początek aukcji'), 'type' => 'datetime-local',
                'hint' => $t('field.starts_at_hint', 'Puste pole oznacza start od razu.')];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'endsAt', 'label' => $t('field.ends_at', 'Data i godzina zakończenia'), 'type' => 'datetime-local',
                'required' => true, 'hint' => $t('field.ends_at_hint', 'Co najmniej 1 godzina i najwyżej 30 dni po starcie.')];
            require __DIR__ . '/../partials/form-field.html.php';
            ?>
            <p class="alert alert--info" role="note">
                <?= $e($t('create.anti_sniping', 'Oferta złożona w ostatnich 30 sekundach przedłuża aukcję o 30 sekund.')) ?>
            </p>
        </fieldset>

        <footer class="auction-form__actions">
            <a class="button button--secondary" href="/company"><?= $e($t('create.cancel', 'Anuluj')) ?></a>
            <button class="button button--accent" type="submit"><?= $e($t('create.submit', 'Opublikuj aukcję')) ?></button>
        </footer>
    </form>
</main>

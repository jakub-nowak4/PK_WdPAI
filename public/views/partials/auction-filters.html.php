<?php
/**
 * Formularz filtrow listy aukcji (GET /auctions, parametry z docs/api.md 2.2: brand, priceMax, sort).
 * @var callable $t
 * @var callable $e
 * @var array<string,string> $brands slug => nazwa
 * @var array{brand:string,priceMax:string,sort:string} $filters
 */
$sortOptions = [
    'ending' => $t('sort.ending', 'Kończące się najszybciej'),
    'newest' => $t('sort.newest', 'Najnowsze'),
    'price_asc' => $t('sort.price_asc', 'Cena rosnąco'),
    'price_desc' => $t('sort.price_desc', 'Cena malejąco'),
];
?>
<details class="auction-filters" open>
    <summary class="auction-filters__summary"><?= $e($t('filters.title', 'Filtry')) ?></summary>
    <form class="auction-filters__form" method="get" action="/auctions">
        <div class="form-field">
            <label class="form-field__label" for="filter-brand"><?= $e($t('filters.brand', 'Marka')) ?></label>
            <select class="form-field__control" id="filter-brand" name="brand">
                <option value=""><?= $e($t('filters.all_brands', 'Wszystkie marki')) ?></option>
                <?php foreach ($brands as $slug => $name): ?>
                    <option value="<?= $e($slug) ?>"<?= $filters['brand'] === $slug ? ' selected' : '' ?>><?= $e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-field">
            <label class="form-field__label" for="filter-price-max"><?= $e($t('filters.price_max', 'Cena do (zł)')) ?></label>
            <div class="input-group">
                <input class="form-field__control" id="filter-price-max" name="priceMax" type="number"
                       inputmode="numeric" min="1" max="100000000" step="1"
                       value="<?= $e($filters['priceMax']) ?>">
                <span class="input-group__suffix" aria-hidden="true">zł</span>
            </div>
        </div>

        <div class="form-field">
            <label class="form-field__label" for="filter-sort"><?= $e($t('filters.sort', 'Sortuj')) ?></label>
            <select class="form-field__control" id="filter-sort" name="sort">
                <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?= $e($value) ?>"<?= $filters['sort'] === $value ? ' selected' : '' ?>><?= $e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="button button--accent button--block" type="submit"><?= $e($t('filters.apply', 'Zastosuj filtry')) ?></button>
        <a class="auction-filters__clear" href="/auctions"><?= $e($t('filters.clear', 'Wyczyść filtry')) ?></a>
    </form>
</details>

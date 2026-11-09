<?php
/**
 * Panel firmy (ekran 14): GET /company.
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-company-auctions.php):
 * $auctions (id, title, image|null, status, price, minIncrement, bidCount, endsAtLabel, canDelete), $company (name, city);
 * opcjonalnie $flash (komunikat po dodaniu/usunieciu), $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$flash = (string) ($flash ?? '');

$isMock = false;
if (!isset($auctions)) {
    $mock = require __DIR__ . '/../partials/mock-company-auctions.php';
    $auctions = $mock['auctions'];
    $company = $mock['company'];
    $isMock = true;
}
$company = $company ?? [];
$money = static fn (int|float $amount): string => number_format((int) $amount, 0, ',', "\u{00A0}") . "\u{00A0}zł";
$statusLabels = [
    'active' => $t('status.active', 'Na żywo'),
    'scheduled' => $t('status.scheduled', 'Wkrótce'),
    'ended' => $t('status.ended', 'Zakończona'),
];
?>
<main class="page page--company" id="main-content">
    <header class="page__header">
        <div>
            <p class="page__eyebrow"><?= $e($t('company.eyebrow', 'Panel firmy')) ?><?php if (!empty($company['city'])): ?> · <?= $e($company['city']) ?><?php endif; ?></p>
            <h1 class="page__title"><?= $e($t('company.title', 'Pulpit aukcyjny firmy')) ?></h1>
            <?php if (!empty($company['name'])): ?><p class="page__lead"><?= $e($company['name']) ?></p><?php endif; ?>
        </div>
        <a class="button button--primary" href="/company/auctions/new"><span aria-hidden="true">+</span> <?= $e($t('company.add', 'Dodaj aukcję')) ?></a>
    </header>

    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
    <?php endif; ?>
    <?php if ($flash !== ''): ?>
        <p class="alert alert--success" role="status"><?= $e($flash) ?></p>
    <?php endif; ?>

    <section class="card company-auctions" aria-labelledby="company-auctions-title">
        <h2 class="card__title" id="company-auctions-title">
            <?= $e($t('company.my_auctions', 'Moje aukcje')) ?>
            <span class="company-auctions__count">(<?= count($auctions) ?>)</span>
        </h2>

        <?php if ($auctions === []): ?>
            <div class="empty-state">
                <p class="empty-state__title"><?= $e($t('company.empty_title', 'Nie masz jeszcze aukcji')) ?></p>
                <p class="empty-state__text"><?= $e($t('company.empty_text', 'Wystaw pierwsze auto i zacznij przyjmować oferty.')) ?></p>
                <a class="button button--primary" href="/company/auctions/new"><?= $e($t('company.add_first', 'Dodaj pierwszą aukcję')) ?></a>
            </div>
        <?php else: ?>
            <table class="data-table">
                <caption class="visually-hidden"><?= $e($t('company.table_caption', 'Lista aukcji firmy')) ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?= $e($t('company.col_vehicle', 'Pojazd')) ?></th>
                        <th scope="col"><?= $e($t('company.col_status', 'Status')) ?></th>
                        <th scope="col"><?= $e($t('company.col_price', 'Aktualna cena')) ?></th>
                        <th scope="col"><?= $e($t('company.col_bids', 'Oferty')) ?></th>
                        <th scope="col"><?= $e($t('company.col_end', 'Koniec')) ?></th>
                        <th scope="col"><?= $e($t('company.col_actions', 'Akcje')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($auctions as $row):
                        $rowStatus = in_array($row['status'], ['active', 'scheduled', 'ended'], true) ? $row['status'] : 'active';
                        $title = (string) $row['title']; ?>
                        <tr>
                            <td class="data-table__vehicle" data-label="<?= $e($t('company.col_vehicle', 'Pojazd')) ?>">
                                <?php if (!empty($row['image'])): ?>
                                    <img class="data-table__thumb" src="<?= $e($row['image']) ?>" alt="<?= $e($title) ?>" loading="lazy" width="96" height="64">
                                <?php else: ?>
                                    <span class="data-table__thumb data-table__thumb--empty" role="img" aria-label="<?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?>">🚗</span>
                                <?php endif; ?>
                                <a href="/auctions/<?= (int) $row['id'] ?>"><?= $e($title) ?></a>
                            </td>
                            <td data-label="<?= $e($t('company.col_status', 'Status')) ?>">
                                <span class="status-label status-label--<?= $e($rowStatus) ?>"><?= $e($statusLabels[$rowStatus]) ?></span>
                            </td>
                            <td class="data-table__price" data-label="<?= $e($t('company.col_price', 'Aktualna cena')) ?>">
                                <?= $e($money($row['price'])) ?>
                                <span class="data-table__note"><?= $e($t('company.min_step', 'krok')) ?> <?= $e($money($row['minIncrement'])) ?></span>
                            </td>
                            <td data-label="<?= $e($t('company.col_bids', 'Oferty')) ?>"><?= (int) $row['bidCount'] ?></td>
                            <td data-label="<?= $e($t('company.col_end', 'Koniec')) ?>"><?= $e($row['endsAtLabel']) ?></td>
                            <td class="data-table__actions" data-label="<?= $e($t('company.col_actions', 'Akcje')) ?>">
                                <a class="button button--secondary button--small" href="/auctions/<?= (int) $row['id'] ?>"
                                   aria-label="<?= $e($t('company.view', 'Zobacz') . ': ' . $title) ?>"><?= $e($t('company.view', 'Zobacz')) ?></a>
                                <?php if (!empty($row['canDelete'])): ?>
                                    <a class="button button--danger button--small" href="/company/auctions/<?= (int) $row['id'] ?>/delete"
                                       aria-label="<?= $e($t('company.delete', 'Usuń') . ': ' . $title) ?>"><?= $e($t('company.delete', 'Usuń')) ?></a>
                                <?php else: ?>
                                    <span class="data-table__note"><?= $e($t('company.cannot_delete', 'Nie można usunąć aukcji z ofertami')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

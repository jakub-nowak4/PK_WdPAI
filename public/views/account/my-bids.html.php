<?php
/**
 * Moje oferty (ekran 09): GET /my-bids?status=all|leading|outbid|won|lost. Dane z bazy (AccountController::myBids).
 * Zmienne od kontrolera: $myBids (list<App\Model\MyBid>, po filtrze statusu), $statusCounts (all, leading, outbid,
 * won, lost; z calej listy), $runningTotal (App\Model\Money), $serverTime (DateTimeImmutable), $status.
 * Filtrowanie i statusy liczy serwer (MyBidsService); widok tylko prezentuje.
 */
require_once __DIR__ . '/../partials/auction-card-data.php';

$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$status = (string) ($status ?? 'all');
$myBids = $myBids ?? [];
$counts = $statusCounts ?? ['all' => 0, 'leading' => 0, 'outbid' => 0, 'won' => 0, 'lost' => 0];
$now = $serverTime ?? new \DateTimeImmutable();

$tabs = [
    'all' => $t('bids.tab_all', 'Wszystkie'),
    'leading' => $t('bids.tab_leading', 'Prowadzę'),
    'outbid' => $t('bids.tab_outbid', 'Przebity'),
    'won' => $t('bids.tab_won', 'Wygrane'),
    'lost' => $t('bids.tab_lost', 'Przegrane'),
];
$statusLabels = [
    'leading' => $t('bids.leading_label', 'Prowadzisz'),
    'outbid' => $t('bids.status_outbid', 'Przebity'),
    'won' => $t('bids.status_won', 'Wygrana'),
    'lost' => $t('bids.status_lost', 'Przegrana'),
];
if (!isset($tabs[$status])) {
    $status = 'all';
}
$runningCount = (int) $counts['leading'] + (int) $counts['outbid'];
?>
<main class="page page--my-bids" id="main-content">
    <header class="page__header">
        <div>
            <p class="page__eyebrow"><?= $e($t('bids.eyebrow', 'Panel licytującego')) ?></p>
            <h1 class="page__title"><?= $e($t('bids.page_title', 'Moje oferty')) ?></h1>
            <p class="page__lead"><?= $e($t('bids.page_lead', 'Wszystkie Twoje licytacje i ich status w jednym miejscu.')) ?></p>
        </div>
    </header>

    <?php if ((int) $counts['outbid'] > 0): ?>
        <p class="alert alert--danger" role="status"><?= $e($t('bids.needs_reaction', 'Aukcje wymagające reakcji (przebite):')) ?> <?= (int) $counts['outbid'] ?></p>
    <?php endif; ?>

    <section class="stat-tiles" aria-label="<?= $e($t('bids.stats', 'Podsumowanie ofert')) ?>">
        <article class="stat-tile">
            <p class="stat-tile__label"><?= $e($t('bids.stat_running', 'Trwające aukcje')) ?></p>
            <p class="stat-tile__value"><?= $runningCount ?></p>
        </article>
        <article class="stat-tile">
            <p class="stat-tile__label"><?= $e($t('bids.stat_value', 'Wartość Twoich ofert')) ?></p>
            <p class="stat-tile__value"><?= $e(isset($runningTotal) ? $runningTotal->format() : '0' . "\u{00A0}zł") ?></p>
        </article>
        <article class="stat-tile stat-tile--highlight">
            <p class="stat-tile__label"><?= $e($t('bids.stat_won', 'Wygrane pojazdy')) ?></p>
            <p class="stat-tile__value"><?= (int) $counts['won'] ?></p>
        </article>
    </section>

    <nav class="tabs" aria-label="<?= $e($t('bids.tabs_label', 'Filtr statusu ofert')) ?>">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="tabs__item<?= $key === $status ? ' tabs__item--active' : '' ?>" href="/my-bids?status=<?= $e($key) ?>"<?= $key === $status ? ' aria-current="page"' : '' ?>><?= $e($label) ?> (<?= (int) $counts[$key] ?>)</a>
        <?php endforeach; ?>
    </nav>

    <section class="card my-bids" aria-labelledby="my-bids-title">
        <h2 class="card__title" id="my-bids-title"><?= $e($tabs[$status]) ?> <span class="my-bids__count">(<?= count($myBids) ?>)</span></h2>

        <?php if ((int) $counts['all'] === 0): ?>
            <div class="empty-state">
                <p class="empty-state__title"><?= $e($t('bids.empty_title', 'Nie licytujesz jeszcze żadnej aukcji')) ?></p>
                <a class="button button--primary" href="/auctions"><?= $e($t('bids.empty_cta', 'Przeglądaj aukcje')) ?></a>
            </div>
        <?php elseif ($myBids === []): ?>
            <div class="empty-state">
                <p class="empty-state__title"><?= $e($t('bids.empty_tab', 'Brak aukcji w tej kategorii')) ?></p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <caption class="visually-hidden"><?= $e($t('bids.table_caption', 'Lista Twoich ofert')) ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?= $e($t('bids.col_vehicle', 'Pojazd')) ?></th>
                        <th scope="col"><?= $e($t('bids.col_mine', 'Twoja oferta')) ?></th>
                        <th scope="col"><?= $e($t('bids.col_price', 'Aktualna cena')) ?></th>
                        <th scope="col"><?= $e($t('bids.col_time', 'Pozostały czas')) ?></th>
                        <th scope="col"><?= $e($t('bids.col_status', 'Status')) ?></th>
                        <th scope="col"><?= $e($t('bids.col_actions', 'Akcje')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myBids as $row):
                        $summary = $row->getSummary();
                        $auction = $summary->getAuction();
                        $card = auctionCardData($summary, $t, $now);
                        $rowStatus = $row->getStatus()->value;
                        $title = $auction->getTitle();
                        $href = $row->getLink();
                        $isRunning = $rowStatus === 'leading' || $rowStatus === 'outbid';
                        $timeLeft = $isRunning
                            ? auctionCardTimeLeft($auction->getEndsAt(), $now, $t)
                            : $t('auction.ended', 'Aukcja zakończona'); ?>
                        <tr>
                            <td class="data-table__vehicle" data-label="<?= $e($t('bids.col_vehicle', 'Pojazd')) ?>">
                                <?php if ($card['image'] !== null): ?>
                                    <img class="data-table__thumb" src="<?= $e($card['image']) ?>" alt="<?= $e($title) ?>" loading="lazy" width="96" height="64">
                                <?php else: ?>
                                    <span class="data-table__thumb data-table__thumb--empty" role="img" aria-label="<?= $e($t('auction.no_photo', 'Brak zdjęcia')) ?>"></span>
                                <?php endif; ?>
                                <span>
                                    <a href="/auctions/<?= (int) $auction->getId() ?>"><?= $e($title) ?></a>
                                    <span class="data-table__note"><?php if ($auction->getVin() !== null): ?><?= $e($t('auction.vin', 'VIN')) ?>: <?= $e($auction->getVin()) ?> · <?php endif; ?><?= $e($card['origin']) ?></span>
                                </span>
                            </td>
                            <td data-label="<?= $e($t('bids.col_mine', 'Twoja oferta')) ?>">
                                <?php if ($rowStatus === 'outbid'): ?><s><?= $e($row->getMyHighestBid()->format()) ?></s><?php else: ?><?= $e($row->getMyHighestBid()->format()) ?><?php endif; ?>
                            </td>
                            <td class="data-table__price" data-label="<?= $e($t('bids.col_price', 'Aktualna cena')) ?>">
                                <?= $e($summary->getCurrentPrice()->format()) ?>
                                <?php if ($rowStatus === 'outbid'): ?>
                                    <span class="data-table__note">+<?= $e($row->getGap()->format()) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= $e($t('bids.col_time', 'Pozostały czas')) ?>">
                                <?= $e($timeLeft) ?>
                                <span class="data-table__note"><?= $e(\App\Support\LocalTime::format($auction->getEndsAt(), 'd.m.Y H:i')) ?></span>
                            </td>
                            <td data-label="<?= $e($t('bids.col_status', 'Status')) ?>">
                                <span class="status-label status-label--<?= $e($rowStatus) ?>"><?= $e($statusLabels[$rowStatus]) ?></span>
                            </td>
                            <td class="data-table__actions" data-label="<?= $e($t('bids.col_actions', 'Akcje')) ?>">
                                <?php if ($rowStatus === 'outbid'): ?>
                                    <a class="button button--accent button--small" href="<?= $e($href) ?>"
                                       aria-label="<?= $e($t('bids.outbid_cta', 'Przebij ofertę') . ': ' . $title) ?>"><?= $e($t('bids.outbid_cta', 'Przebij ofertę')) ?></a>
                                <?php elseif ($rowStatus === 'won'): ?>
                                    <a class="button button--secondary button--small" href="<?= $e($href) ?>"
                                       aria-label="<?= $e($t('bids.see_win', 'Zobacz wygraną') . ': ' . $title) ?>"><?= $e($t('bids.see_win', 'Zobacz wygraną')) ?></a>
                                <?php else: ?>
                                    <a class="button button--secondary button--small" href="<?= $e($href) ?>"
                                       aria-label="<?= $e($t('bids.details', 'Szczegóły aukcji') . ': ' . $title) ?>"><?= $e($t('bids.details', 'Szczegóły aukcji')) ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

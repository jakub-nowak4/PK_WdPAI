<?php
/**
 * Powiadomienia (GET /notifications): lista kart, najnowsze pierwsze.
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-notifications.php):
 * $notifications (id, type outbid|won|auction_ended, auctionId, title, dateLabel, unread); opcjonalnie $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$csrfToken = (string) ($csrfToken ?? '');

$isMock = false;
if (!isset($notifications)) {
    $notifications = require __DIR__ . '/../partials/mock-notifications.php';
    $isMock = true;
}
$unread = count(array_filter($notifications, static fn (array $row): bool => $row['unread']));

$types = [
    'outbid' => ['icon' => '⚠', 'label' => $t('notifications.type_outbid', 'Przebity'), 'text' => $t('notifications.outbid', 'Ktoś przebił Twoją ofertę w aukcji'), 'cta' => $t('notifications.outbid_cta', 'Przebij ofertę')],
    'won' => ['icon' => '🏆', 'label' => $t('notifications.type_won', 'Wygrana'), 'text' => $t('notifications.won', 'Wygrałeś aukcję'), 'cta' => $t('notifications.won_cta', 'Zobacz wygraną')],
    'auction_ended' => ['icon' => '⏱', 'label' => $t('notifications.type_ended', 'Zakończona'), 'text' => $t('notifications.ended', 'Zakończyła się aukcja'), 'cta' => $t('notifications.ended_cta', 'Szczegóły aukcji')],
];
?>
<main class="page page--notifications" id="main-content">
    <header class="page__header">
        <div>
            <p class="page__eyebrow"><?= $e($t('notifications.eyebrow', 'Panel licytującego')) ?></p>
            <h1 class="page__title"><?= $e($t('notifications.page_title', 'Powiadomienia')) ?></h1>
            <p class="page__lead"><?= $e($t('notifications.page_lead', 'Przebicia ofert i wyniki aukcji, w których bierzesz udział.')) ?></p>
        </div>
        <?php if ($unread > 0): ?>
            <form method="post" action="/notifications/read">
                <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                <button class="button button--secondary" type="submit"><?= $e($t('notifications.mark_read', 'Oznacz wszystkie jako przeczytane')) ?></button>
            </form>
        <?php endif; ?>
    </header>

    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy. Przycisk „Oznacz wszystkie jako przeczytane" czeka na trasę POST i CSRF.</p>
    <?php endif; ?>

    <section aria-labelledby="notifications-title">
        <h2 class="visually-hidden" id="notifications-title"><?= $e($t('notifications.list_title', 'Lista powiadomień')) ?> (<?= count($notifications) ?>)</h2>

        <?php if ($notifications === []): ?>
            <div class="empty-state">
                <p class="empty-state__title"><?= $e($t('notifications.empty', 'Nie masz jeszcze powiadomień')) ?></p>
                <a class="button button--primary" href="/auctions"><?= $e($t('bids.empty_cta', 'Przeglądaj aukcje')) ?></a>
            </div>
        <?php else: ?>
            <ul class="notification-list">
                <?php foreach ($notifications as $row):
                    $type = array_key_exists($row['type'], $types) ? $row['type'] : 'auction_ended';
                    $meta = $types[$type];
                    $title = (string) $row['title'];
                    $href = '/auctions/' . (int) $row['auctionId'] . ($type === 'won' ? '/won' : ''); ?>
                    <li>
                        <article class="card notification<?= $row['unread'] ? ' notification--unread' : '' ?>">
                            <span class="notification__icon" aria-hidden="true"><?= $e($meta['icon']) ?></span>
                            <div class="notification__body">
                                <p class="notification__text"><?= $e($meta['text']) ?>: <a href="/auctions/<?= (int) $row['auctionId'] ?>"><?= $e($title) ?></a></p>
                                <p class="notification__meta">
                                    <time><?= $e($row['dateLabel']) ?></time>
                                    <?php if ($row['unread']): ?><span class="visually-hidden"> · <?= $e($t('notifications.unread', 'Nowe')) ?></span><?php endif; ?>
                                </p>
                            </div>
                            <?php $statusClass = $type === 'auction_ended' ? 'lost' : $type; ?>
                            <span class="status-label status-label--<?= $e($statusClass) ?>"><?= $e($meta['label']) ?></span>
                            <a class="button button--secondary button--small" href="<?= $e($href) ?>"
                               aria-label="<?= $e($meta['cta'] . ': ' . $title) ?>"><?= $e($meta['cta']) ?></a>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

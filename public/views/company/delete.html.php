<?php
/**
 * Usun aukcje (ekran 16): GET /company/auctions/{id}/delete, wysylka POST na ten sam adres.
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-delete-auction.php):
 * $auction (id, title, startPrice, bidCount), $csrfToken, opcjonalnie $formError, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$csrfToken = (string) ($csrfToken ?? '');
$formError = (string) ($formError ?? '');

$isMock = false;
if (!isset($auction)) {
    $mock = require __DIR__ . '/../partials/mock-delete-auction.php';
    $auction = $mock['auction'];
    $isMock = true;
}
$money = static fn (int|float $amount): string => number_format((int) $amount, 0, ',', "\u{00A0}") . "\u{00A0}zł";
$auctionId = (int) $auction['id'];
$canDelete = (int) ($auction['bidCount'] ?? 0) === 0;
?>
<main class="page page--confirm" id="main-content">
    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
    <?php endif; ?>

    <section class="confirm-card card" aria-labelledby="delete-title">
        <span class="confirm-card__icon confirm-card__icon--danger" aria-hidden="true">🗑</span>
        <h1 class="confirm-card__title" id="delete-title"><?= $e($t('company.delete_title', 'Usunąć aukcję?')) ?></h1>
        <p class="confirm-card__text">
            <strong><?= $e($auction['title']) ?></strong>
            <?= $e($t('company.delete_text', 'zostanie trwale usunięta z Twojego panelu. Tej operacji nie można cofnąć.')) ?>
        </p>

        <dl class="confirm-card__details">
            <div class="confirm-card__row">
                <dt><?= $e($t('company.delete_id', 'Aukcja')) ?></dt>
                <dd>#<?= $auctionId ?></dd>
            </div>
            <div class="confirm-card__row">
                <dt><?= $e($t('company.delete_start_price', 'Cena wywoławcza')) ?></dt>
                <dd><?= $e($money($auction['startPrice'])) ?></dd>
            </div>
        </dl>

        <?php if ($formError !== ''): ?>
            <p class="alert alert--error" role="alert"><?= $e($formError) ?></p>
        <?php endif; ?>

        <?php if ($canDelete): ?>
            <p class="alert alert--success" role="status"><?= $e($t('company.delete_zero_bids', 'Aukcja ma 0 ofert. Usunięcie nie wpłynie na licytujących.')) ?></p>

            <form class="confirm-card__actions" method="post" action="/company/auctions/<?= $auctionId ?>/delete">
                <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                <a class="button button--secondary" href="/company"><?= $e($t('common.cancel', 'Anuluj')) ?></a>
                <button class="button button--danger" type="submit"><?= $e($t('company.delete_confirm', 'Usuń aukcję')) ?></button>
            </form>
        <?php else: ?>
            <p class="alert alert--error" role="alert"><?= $e($t('company.cannot_delete_bids', 'Nie można usunąć aukcji, w której są oferty.')) ?></p>
            <div class="confirm-card__actions">
                <a class="button button--secondary" href="/company"><?= $e($t('common.back', 'Wróć do panelu')) ?></a>
            </div>
        <?php endif; ?>
    </section>
</main>

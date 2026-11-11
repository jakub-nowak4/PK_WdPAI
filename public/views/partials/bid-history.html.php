<?php
/**
 * Historia licytacji (maks. 10 ostatnich ofert, anonimowo: "Licytujacy N", wlasna oferta: "Ty").
 * @var callable $t
 * @var callable $e
 * @var callable $money
 * @var array<string,mixed> $auction bidCount, startPrice
 * @var list<array{bidder:string,isYou:bool,amount:int,diff:int,when:string,badge?:string}> badge: leader|outbid (opcjonalnie) $bids
 */
?>
<section class="bid-history" aria-labelledby="bid-history-title" data-bid-history>
    <header class="bid-history__header">
        <h2 id="bid-history-title"><?= $e($t('bids.history', 'Historia licytacji')) ?></h2>
        <p class="bid-history__count"><span data-bid-count><?= $e((string) $auction['bidCount']) ?></span> <?= $e($t('auction.bids', 'ofert')) ?></p>
    </header>

    <?php if ($bids === []): ?>
        <p class="bid-history__empty"><?= $e($t('bids.none', 'Brak ofert. Bądź pierwszy.')) ?></p>
    <?php else: ?>
        <ol class="bid-history__list">
            <?php foreach ($bids as $index => $bid): ?>
                <li class="bid-history__item<?= $index === 0 ? ' bid-history__item--leader' : '' ?><?= $bid['isYou'] ? ' bid-history__item--you' : '' ?>">
                    <span class="bid-history__bidder"><?= $e($bid['isYou'] ? $t('bids.you', 'Ty') : $bid['bidder']) ?></span>
                    <span class="bid-history__when"><?= $e($bid['when']) ?></span>
                    <strong class="bid-history__amount"><?= $e($money($bid['amount'])) ?></strong>
                    <span class="bid-history__diff">+<?= $e($money($bid['diff'])) ?></span>
                    <?php if (($bid['badge'] ?? '') === 'leader'): ?>
                        <span class="status-label status-label--leading"><?= $e($t('bids.leader', 'LIDER')) ?></span>
                    <?php elseif (($bid['badge'] ?? '') === 'outbid'): ?>
                        <span class="status-label status-label--outbid"><?= $e($t('bids.outbid', 'Przebita')) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
    <p class="bid-history__start"><?= $e($t('bids.start_price', 'Cena wywoławcza')) ?>: <?= $e($money($auction['startPrice'])) ?></p>
</section>

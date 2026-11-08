<?php
/**
 * Stopka serwisu.
 * @var callable $t
 * @var callable $e
 */
?>
<footer class="site-footer">
    <a class="site-footer__logo" href="/">
        <span class="site-header__logo-mark" aria-hidden="true">M</span>
        <span>MotoBid</span>
    </a>
    <nav aria-label="<?= $e($t('footer.nav', 'Stopka')) ?>">
        <ul class="site-footer__links">
            <li><a href="/auctions"><?= $e($t('nav.auctions', 'Aukcje')) ?></a></li>
            <li><?= $e($t('footer.terms', 'Regulamin')) ?></li>
            <li><?= $e($t('footer.privacy', 'Polityka prywatności')) ?></li>
            <li><?= $e($t('footer.contact', 'Kontakt')) ?></li>
        </ul>
    </nav>
    <p class="site-footer__copy">&copy; 2026 MotoBid</p>
</footer>

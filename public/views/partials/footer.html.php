<?php
/**
 * Stopka serwisu.
 * @var callable $t
 * @var callable $e
 * @var string $lang
 * @var string $csrfToken
 * @var string $currentPath
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
    <section class="site-footer__settings" aria-labelledby="footer-settings-title">
        <h2 class="site-footer__settings-title" id="footer-settings-title"><?= $e($t('footer.settings', 'Język i motyw')) ?></h2>
        <?php require __DIR__ . '/settings.html.php'; ?>
    </section>
    <p class="site-footer__copy">&copy; 2026 MotoBid</p>
</footer>

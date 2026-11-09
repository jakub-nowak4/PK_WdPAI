<?php
/**
 * Konto firmy czeka na zatwierdzenie (ekran 13): GET /account/pending.
 * Opcjonalne zmienne od kontrolera: $companyStatus ('pending' domyslnie | 'rejected'), $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$companyStatus = ($companyStatus ?? 'pending') === 'rejected' ? 'rejected' : 'pending';
$isRejected = $companyStatus === 'rejected';
?>
<main class="page page--auth" id="main-content">
    <section class="auth-card status-card" aria-labelledby="pending-title">
        <header class="auth-card__header">
            <p class="status-label status-label--<?= $e($companyStatus) ?>">
                <span class="status-label__dot" aria-hidden="true"></span>
                <?= $e($isRejected ? $t('company_status.rejected', 'Odrzucone') : $t('company_status.pending', 'Oczekuje')) ?>
            </p>
            <h1 class="auth-card__title" id="pending-title">
                <?= $e($isRejected
                    ? $t('pending.title_rejected', 'Zgłoszenie firmy zostało odrzucone')
                    : $t('pending.title', 'Konto firmy czeka na zatwierdzenie')) ?>
            </h1>
            <p class="auth-card__lead">
                <?= $e($isRejected
                    ? $t('pending.lead_rejected', 'Zgłoszenie firmy zostało odrzucone. Skontaktuj się z administratorem.')
                    : $t('pending.lead', 'Administrator sprawdzi dane Twojej firmy, zwykle w ciągu 24 godzin. Po zatwierdzeniu zobaczysz panel firmy po zalogowaniu.')) ?>
            </p>
        </header>

        <section aria-labelledby="pending-steps-title">
            <h2 class="status-card__subtitle" id="pending-steps-title"><?= $e($t('pending.steps', 'Postęp zgłoszenia')) ?></h2>
            <ol class="stepper">
                <li class="stepper__step stepper__step--done">
                    <span class="stepper__marker" aria-hidden="true">✓</span>
                    <span class="stepper__label"><?= $e($t('pending.step_sent', 'Zgłoszenie przesłane')) ?></span>
                    <span class="stepper__state"><?= $e($t('pending.state_done', 'Ukończono')) ?></span>
                </li>
                <li class="stepper__step <?= $isRejected ? 'stepper__step--error' : 'stepper__step--current' ?>"<?= $isRejected ? '' : ' aria-current="step"' ?>>
                    <span class="stepper__marker" aria-hidden="true"><?= $isRejected ? '✕' : '2' ?></span>
                    <span class="stepper__label"><?= $e($t('pending.step_review', 'Weryfikacja przez administratora')) ?></span>
                    <span class="stepper__state"><?= $e($isRejected ? $t('pending.state_rejected', 'Odrzucono') : $t('pending.state_current', 'W trakcie')) ?></span>
                </li>
                <?php if (!$isRejected): ?>
                    <li class="stepper__step">
                        <span class="stepper__marker" aria-hidden="true">3</span>
                        <span class="stepper__label"><?= $e($t('pending.step_active', 'Aktywacja konta')) ?></span>
                        <span class="stepper__state"><?= $e($t('pending.state_waiting', 'Oczekuje')) ?></span>
                    </li>
                <?php endif; ?>
            </ol>
        </section>

        <footer class="auth-card__footer">
            <a class="button button--secondary button--block" href="/auctions"><?= $e($t('pending.to_auctions', 'Przejdź do aukcji')) ?></a>
            <?php if (!$isRejected): ?>
                <p><a href="/account/pending"><?= $e($t('pending.refresh', 'Sprawdź status')) ?></a></p>
            <?php endif; ?>
        </footer>
    </section>
</main>

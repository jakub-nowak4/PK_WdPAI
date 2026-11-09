<?php
/**
 * Rejestracja kupujacego (ekran 11): GET/POST /register.
 * Opcjonalne zmienne od kontrolera: $errors (pole => komunikat), $old (name, email), $formError, $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errors = $errors ?? [];
$old = $old ?? [];
$csrfToken = (string) ($csrfToken ?? '');
$accountType = 'buyer';
?>
<main class="page page--auth" id="main-content">
    <section class="auth-card" aria-labelledby="register-title">
        <header class="auth-card__header">
            <p class="auth-card__badge"><?= $e($t('register.badge', 'Bezpieczna rejestracja')) ?></p>
            <h1 class="auth-card__title" id="register-title"><?= $e($t('register.title', 'Utwórz konto w MotoBid')) ?></h1>
            <p class="auth-card__lead"><?= $e($t('register.lead', 'Załóż konto kupującego i zacznij licytować pojazdy')) ?></p>
            <?php require __DIR__ . '/../partials/account-type-switch.html.php'; ?>
        </header>

        <?php require __DIR__ . '/../partials/form-errors.html.php'; ?>

        <form class="auth-card__form" method="post" action="/register" novalidate>
            <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">

            <?php
            $field = ['name' => 'name', 'label' => $t('field.name', 'Imię i nazwisko'), 'autocomplete' => 'name',
                'required' => true, 'minlength' => 2, 'maxlength' => 60];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'email', 'label' => $t('field.email', 'Adres e-mail'), 'type' => 'email',
                'autocomplete' => 'email', 'required' => true, 'maxlength' => 254];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'password', 'label' => $t('field.password', 'Hasło'), 'type' => 'password',
                'autocomplete' => 'new-password', 'required' => true, 'minlength' => 8, 'maxlength' => 72,
                'hint' => $t('field.password_hint', 'Minimum 8 znaków')];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'passwordRepeat', 'label' => $t('field.password_repeat', 'Powtórz hasło'),
                'type' => 'password', 'autocomplete' => 'new-password', 'required' => true,
                'minlength' => 8, 'maxlength' => 72];
            require __DIR__ . '/../partials/form-field.html.php';
            ?>

            <button class="button button--accent button--block" type="submit">
                <?= $e($t('register.submit', 'Załóż konto')) ?> <span aria-hidden="true">→</span>
            </button>
        </form>

        <footer class="auth-card__footer">
            <p><?= $e($t('register.have_account', 'Masz już konto?')) ?>
                <a href="/login"><?= $e($t('register.login', 'Zaloguj się')) ?></a></p>
        </footer>
    </section>
</main>

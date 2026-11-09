<?php
/**
 * Logowanie (ekran 10): GET/POST /login.
 * Opcjonalne zmienne od kontrolera: $errors (pole => komunikat), $old (email), $formError, $formInfo,
 * $next (adres po zalogowaniu), $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errors = $errors ?? [];
$old = $old ?? [];
$next = (string) ($next ?? '');
$csrfToken = (string) ($csrfToken ?? '');
?>
<main class="page page--auth" id="main-content">
    <section class="auth-card" aria-labelledby="login-title">
        <header class="auth-card__header">
            <p class="auth-card__badge"><?= $e($t('login.badge', 'Licytacje aut z USA i Europy')) ?></p>
            <h1 class="auth-card__title" id="login-title"><?= $e($t('login.title', 'Zaloguj się do MotoBid')) ?></h1>
            <p class="auth-card__lead"><?= $e($t('login.lead', 'Wprowadź swoje dane, aby licytować pojazdy')) ?></p>
        </header>

        <?php require __DIR__ . '/../partials/form-errors.html.php'; ?>

        <form class="auth-card__form" method="post" action="/login" novalidate>
            <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
            <input type="hidden" name="next" value="<?= $e($next) ?>">

            <?php
            $field = ['name' => 'email', 'label' => $t('field.email', 'Adres e-mail'), 'type' => 'email',
                'autocomplete' => 'email', 'required' => true, 'maxlength' => 254];
            require __DIR__ . '/../partials/form-field.html.php';

            $field = ['name' => 'password', 'label' => $t('field.password', 'Hasło'), 'type' => 'password',
                'autocomplete' => 'current-password', 'required' => true];
            require __DIR__ . '/../partials/form-field.html.php';
            ?>

            <button class="button button--accent button--block" type="submit">
                <?= $e($t('login.submit', 'Zaloguj się')) ?> <span aria-hidden="true">→</span>
            </button>
        </form>

        <footer class="auth-card__footer">
            <p><?= $e($t('login.no_account', 'Nie masz konta?')) ?>
                <a href="/register"><?= $e($t('login.register', 'Zarejestruj się')) ?></a></p>
        </footer>
    </section>
</main>

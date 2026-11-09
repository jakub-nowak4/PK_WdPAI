<?php
/**
 * Rejestracja firmy (ekran 12): GET/POST /register/company.
 * Opcjonalne zmienne od kontrolera: $errors (pole => komunikat), $old (name, email, companyName, city, phone),
 * $formError, $csrfToken, $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$errors = $errors ?? [];
$old = $old ?? [];
$csrfToken = (string) ($csrfToken ?? '');
$accountType = 'company';
?>
<main class="page page--auth" id="main-content">
    <section class="auth-card" aria-labelledby="register-company-title">
        <header class="auth-card__header">
            <p class="auth-card__badge"><?= $e($t('register_company.badge', 'Konto firmy')) ?></p>
            <h1 class="auth-card__title" id="register-company-title"><?= $e($t('register_company.title', 'Zarejestruj konto firmowe')) ?></h1>
            <p class="auth-card__lead"><?= $e($t('register_company.lead', 'Wystawiaj auta na licytacje. Konto firmy zatwierdza administrator.')) ?></p>
            <?php require __DIR__ . '/../partials/account-type-switch.html.php'; ?>
        </header>

        <?php require __DIR__ . '/../partials/form-errors.html.php'; ?>

        <form class="auth-card__form" method="post" action="/register/company" novalidate>
            <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">

            <fieldset class="auth-card__group">
                <legend class="auth-card__legend"><?= $e($t('register_company.section_account', 'Dane konta')) ?></legend>
                <?php
                $field = ['name' => 'name', 'label' => $t('field.contact_name', 'Imię i nazwisko osoby kontaktowej'),
                    'autocomplete' => 'name', 'required' => true, 'minlength' => 2, 'maxlength' => 60];
                require __DIR__ . '/../partials/form-field.html.php';

                $field = ['name' => 'email', 'label' => $t('field.email', 'Adres e-mail'), 'type' => 'email',
                    'autocomplete' => 'email', 'required' => true, 'maxlength' => 254,
                    'hint' => $t('field.email_login_hint', 'Służy jako login')];
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
            </fieldset>

            <fieldset class="auth-card__group">
                <legend class="auth-card__legend"><?= $e($t('register_company.section_company', 'Dane firmy')) ?></legend>
                <?php
                $field = ['name' => 'companyName', 'label' => $t('field.company_name', 'Nazwa firmy'),
                    'autocomplete' => 'organization', 'required' => true, 'minlength' => 2, 'maxlength' => 120];
                require __DIR__ . '/../partials/form-field.html.php';

                $field = ['name' => 'city', 'label' => $t('field.city', 'Miasto'),
                    'autocomplete' => 'address-level2', 'required' => true, 'minlength' => 2, 'maxlength' => 80];
                require __DIR__ . '/../partials/form-field.html.php';

                $field = ['name' => 'phone', 'label' => $t('field.phone', 'Telefon (opcjonalnie)'), 'type' => 'tel',
                    'autocomplete' => 'tel', 'inputmode' => 'tel', 'pattern' => '\+?[0-9 ]{9,15}', 'maxlength' => 16,
                    'hint' => $t('field.phone_hint', 'Np. +48 600 100 200')];
                require __DIR__ . '/../partials/form-field.html.php';
                ?>
            </fieldset>

            <button class="button button--primary button--block" type="submit">
                <?= $e($t('register_company.submit', 'Zarejestruj konto firmowe')) ?> <span aria-hidden="true">→</span>
            </button>
        </form>

        <footer class="auth-card__footer">
            <p><?= $e($t('register.have_account', 'Masz już konto?')) ?>
                <a href="/login"><?= $e($t('register_company.login', 'Przejdź do logowania')) ?></a></p>
        </footer>
    </section>
</main>

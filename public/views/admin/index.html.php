<?php
/**
 * Panel administratora (ekran 17): GET /admin?status=pending|approved|rejected|all.
 * Oczekiwane zmienne od kontrolera (na razie brak, uzywana jest MAKIETA z partials/mock-admin-companies.php):
 * $companies (id, name, city, email, registeredAtLabel, status), $status (aktywna zakladka), $csrfToken,
 * opcjonalnie $stats (pending, approved, auctions), $flash, $formError (np. 409), $t, $e.
 */
$e = $e ?? static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$t = $t ?? static fn (string $key, string $default = ''): string => $default;
$csrfToken = (string) ($csrfToken ?? '');
$flash = (string) ($flash ?? '');
$formError = (string) ($formError ?? '');

$isMock = false;
if (!isset($companies)) {
    $mock = require __DIR__ . '/../partials/mock-admin-companies.php';
    $companies = $mock['companies'];
    $stats = $mock['stats'];
    $isMock = true;
}
$stats = $stats ?? null;
$tabs = [
    'pending' => $t('admin.tab_pending', 'Oczekujące'),
    'approved' => $t('admin.tab_approved', 'Zatwierdzone'),
    'rejected' => $t('admin.tab_rejected', 'Odrzucone'),
    'all' => $t('admin.tab_all', 'Wszystkie'),
];
$status = in_array($status ?? 'pending', array_keys($tabs), true) ? $status : 'pending';
if ($isMock) {
    $companies = $status === 'all' ? $companies : array_values(array_filter($companies, static fn (array $c): bool => $c['status'] === $status));
}
$statusLabels = [
    'pending' => $t('admin.status_pending', 'Oczekuje'),
    'approved' => $t('admin.status_approved', 'Zatwierdzona'),
    'rejected' => $t('admin.status_rejected', 'Odrzucona'),
];
?>
<main class="page page--admin" id="main-content">
    <header class="page__header">
        <div>
            <p class="page__eyebrow"><?= $e($t('admin.eyebrow', 'Administrator')) ?></p>
            <h1 class="page__title"><?= $e($t('admin.title', 'Zatwierdzanie kont firm')) ?></h1>
            <p class="page__lead"><?= $e($t('admin.lead', 'Sprawdź zgłoszenia firm i zdecyduj, czy mogą wystawiać aukcje.')) ?></p>
        </div>
        <a class="button button--secondary" href="/admin"><?= $e($t('admin.refresh', 'Odśwież')) ?></a>
    </header>

    <?php if ($isMock): ?>
        <p class="alert alert--info" role="note">MAKIETA: dane przykładowe, nie pochodzą z bazy.</p>
    <?php endif; ?>
    <?php if ($flash !== ''): ?>
        <p class="alert alert--success" role="status"><?= $e($flash) ?></p>
    <?php endif; ?>
    <?php if ($formError !== ''): ?>
        <p class="alert alert--error" role="alert"><?= $e($formError) ?></p>
    <?php endif; ?>

    <?php if (is_array($stats)): ?>
        <section class="stat-tiles" aria-label="<?= $e($t('admin.stats', 'Statystyki')) ?>">
            <article class="stat-tile stat-tile--highlight">
                <p class="stat-tile__label"><?= $e($t('admin.stat_pending', 'Oczekujące firmy')) ?></p>
                <p class="stat-tile__value"><?= (int) $stats['pending'] ?></p>
            </article>
            <article class="stat-tile">
                <p class="stat-tile__label"><?= $e($t('admin.stat_approved', 'Aktywne firmy')) ?></p>
                <p class="stat-tile__value"><?= (int) $stats['approved'] ?></p>
            </article>
            <article class="stat-tile">
                <p class="stat-tile__label"><?= $e($t('admin.stat_auctions', 'Wszystkie aukcje')) ?></p>
                <p class="stat-tile__value"><?= (int) $stats['auctions'] ?></p>
            </article>
        </section>
    <?php endif; ?>

    <nav class="tabs" aria-label="<?= $e($t('admin.tabs_label', 'Filtr statusu firm')) ?>">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="tabs__item<?= $key === $status ? ' tabs__item--active' : '' ?>" href="/admin?status=<?= $e($key) ?>"<?= $key === $status ? ' aria-current="page"' : '' ?>><?= $e($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <section class="card admin-companies" aria-labelledby="admin-companies-title">
        <h2 class="card__title" id="admin-companies-title">
            <?= $e($tabs[$status]) ?>
            <span class="admin-companies__count">(<?= count($companies) ?>)</span>
        </h2>

        <?php if ($companies === []): ?>
            <div class="empty-state">
                <p class="empty-state__title"><?= $e($t('admin.empty_title', 'Brak firm do zatwierdzenia')) ?></p>
                <p class="empty-state__text"><?= $e($t('admin.empty_text', 'Nowe zgłoszenia pojawią się tutaj.')) ?></p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <caption class="visually-hidden"><?= $e($t('admin.table_caption', 'Lista firm')) ?></caption>
                <thead>
                    <tr>
                        <th scope="col"><?= $e($t('admin.col_name', 'Nazwa firmy')) ?></th>
                        <th scope="col"><?= $e($t('admin.col_city', 'Miasto')) ?></th>
                        <th scope="col"><?= $e($t('admin.col_email', 'E-mail konta')) ?></th>
                        <th scope="col"><?= $e($t('admin.col_date', 'Data rejestracji')) ?></th>
                        <th scope="col"><?= $e($t('admin.col_status', 'Status')) ?></th>
                        <th scope="col"><?= $e($t('admin.col_actions', 'Akcje')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $row):
                        $rowStatus = in_array($row['status'], ['pending', 'approved', 'rejected'], true) ? $row['status'] : 'pending';
                        $companyId = (int) $row['id'];
                        $name = (string) $row['name']; ?>
                        <tr>
                            <th class="data-table__vehicle" scope="row" data-label="<?= $e($t('admin.col_name', 'Nazwa firmy')) ?>"><?= $e($name) ?></th>
                            <td data-label="<?= $e($t('admin.col_city', 'Miasto')) ?>"><?= $e($row['city']) ?></td>
                            <td class="data-table__mono" data-label="<?= $e($t('admin.col_email', 'E-mail konta')) ?>"><?= $e($row['email']) ?></td>
                            <td data-label="<?= $e($t('admin.col_date', 'Data rejestracji')) ?>"><?= $e($row['registeredAtLabel']) ?></td>
                            <td data-label="<?= $e($t('admin.col_status', 'Status')) ?>">
                                <span class="status-label status-label--<?= $e($rowStatus) ?>"><?= $e($statusLabels[$rowStatus]) ?></span>
                            </td>
                            <td class="data-table__actions" data-label="<?= $e($t('admin.col_actions', 'Akcje')) ?>">
                                <?php if ($rowStatus === 'pending'): ?>
                                    <form class="data-table__form" method="post" action="/admin/companies/<?= $companyId ?>/approve">
                                        <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                                        <button class="button button--success button--small" type="submit"
                                                aria-label="<?= $e($t('admin.approve', 'Zatwierdź') . ': ' . $name) ?>"><?= $e($t('admin.approve', 'Zatwierdź')) ?></button>
                                    </form>
                                    <form class="data-table__form" method="post" action="/admin/companies/<?= $companyId ?>/reject">
                                        <input type="hidden" name="csrfToken" value="<?= $e($csrfToken) ?>">
                                        <button class="button button--danger-outline button--small" type="submit"
                                                aria-label="<?= $e($t('admin.reject', 'Odrzuć') . ': ' . $name) ?>"><?= $e($t('admin.reject', 'Odrzuć')) ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="data-table__note"><?= $e($t('admin.decided', 'Rozpatrzona')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

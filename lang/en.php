<?php

declare(strict_types=1);

/*
English translation catalog. Same keys as lang/pl.php; a missing key falls back to Polish.
Placeholders {name} are replaced by Translator::translate().
The full set of view texts is added by T-048; these are the starter keys.
*/
return [
    // Navigation and shared elements
    'nav.skip' => 'Skip to content',
    'nav.main' => 'Main navigation',
    'nav.mobile' => 'Mobile navigation',
    'nav.language' => 'Language',
    'nav.auctions' => 'Auctions',
    'nav.my_bids' => 'My bids',
    'nav.notifications' => 'Notifications',
    'nav.company_panel' => 'Company panel',
    'nav.add_auction' => 'Add auction',
    'nav.account_status' => 'Account status',
    'nav.admin_panel' => 'Administrator panel',
    'nav.login' => 'Log in',
    'nav.register' => 'Sign up',
    'nav.logout' => 'Log out',
    'theme.toggle' => 'Change theme',
    'theme.to_dark' => 'Switch to dark theme',
    'common.cancel' => 'Cancel',
    'common.close' => 'Close',
    'error.back_to_auctions' => 'Back to auctions',

    // HTTP errors and security
    'error.bad_request' => 'Invalid request.',
    'error.forbidden' => 'You do not have permission to view this page.',
    'error.not_found' => 'Page not found.',
    'error.csrf_invalid' => 'The form session has expired. Refresh the page and try again.',
    'error.language_invalid' => 'Unsupported language.',
    'error.400.label' => 'Request error • error code 400',
    'error.400.title' => 'Invalid request',
    'error.400.description' => 'The address contains an invalid parameter.',
    'error.403.label' => 'Authorization • error code 403',
    'error.403.title' => 'Access denied',
    'error.403.description' => 'You do not have permission to view this page.',
    'error.404.label' => 'HTTP error 404 • resource unavailable',
    'error.404.title' => 'Page not found',
    'error.404.description' => 'The auction may have ended or been removed.',

    // Form validation
    'validation.required' => 'This field is required.',
    'validation.email' => 'Enter a valid e-mail address.',
    'validation.min_length' => 'Enter at least {min} characters.',
    'validation.max_length' => 'Enter at most {max} characters.',
    'validation.password_mismatch' => 'Passwords do not match.',
    'validation.number' => 'Enter a valid number.',
    'validation.min_value' => 'The value cannot be lower than {min}.',
    'validation.max_value' => 'The value cannot be higher than {max}.',
    'validation.invalid_format' => 'Invalid format.',
];

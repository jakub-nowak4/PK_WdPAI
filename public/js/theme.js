const storageKey = 'theme';
// Przycisków motywu może być kilka (nagłówek i stopka); wszystkie są synchronizowane.
const themeToggles = document.querySelectorAll('[data-theme-toggle]');
const colorSchemeQuery = window.matchMedia('(prefers-color-scheme: dark)');

function getCurrentTheme() {
    return document.documentElement.dataset.theme === 'dark'
        ? 'dark'
        : 'light';
}

function getSavedTheme() {
    try {
        const theme = localStorage.getItem(storageKey);
        return theme === 'light' || theme === 'dark' ? theme : null;
    } catch (error) {
        return null;
    }
}

function saveTheme(theme) {
    try {
        localStorage.setItem(storageKey, theme);
    } catch (error) {
        // Motyw nadal działa w bieżącym dokumencie.
    }
}

function syncThemeToggle() {
    const isDark = getCurrentTheme() === 'dark';

    themeToggles.forEach((toggle) => {
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute(
            'aria-label',
            isDark ? 'Włącz jasny motyw' : 'Włącz ciemny motyw'
        );

        // Ikona pokazuje działanie: księżyc włącza dark, słońce włącza light.
        const icon = toggle.querySelector('.theme-toggle__icon');
        if (icon) {
            icon.textContent = isDark ? '☀️' : '🌙';
        }
    });
}

function setTheme(theme, remember = true) {
    document.documentElement.dataset.theme = theme;

    if (remember) {
        saveTheme(theme);
    }

    syncThemeToggle();
}

themeToggles.forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const nextTheme = getCurrentTheme() === 'dark' ? 'light' : 'dark';
        setTheme(nextTheme);
    });
});

colorSchemeQuery.addEventListener('change', (event) => {
    if (getSavedTheme() !== null) {
        return;
    }

    setTheme(event.matches ? 'dark' : 'light', false);
});

syncThemeToggle();

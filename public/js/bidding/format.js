// Formatowanie kwot (zl, kwoty w groszach), czasu do konca i dat bez zaleznosci od strefy przegladarki.

const NBSP = ' ';

export function money(grosze) {
    const zloty = Math.round(grosze / 100);
    return String(zloty).replace(/\B(?=(\d{3})+(?!\d))/g, NBSP) + NBSP + 'zł';
}

export function timeLeft(seconds, labels) {
    const total = Math.max(0, Math.floor(seconds));
    const parts = [];
    if (total >= 86400) {
        parts.push(Math.floor(total / 86400) + labels.day);
    }
    if (total >= 3600) {
        parts.push(Math.floor((total % 86400) / 3600) + labels.hour);
    }
    if (total >= 60) {
        parts.push(Math.floor((total % 3600) / 60) + labels.minute);
    }
    parts.push((total % 60) + labels.second);

    return parts.join(' ');
}

// Data i godzina tak, jak je zapisal serwer w ISO 8601 (bez przeliczania na strefe przegladarki).
export function serverDate(iso, separator = ' ') {
    const match = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(String(iso));
    if (!match) {
        return '';
    }
    const [, year, month, day, hour, minute] = match;

    return `${day}.${month}.${year}${separator}${hour}:${minute}`;
}

export function fillTemplate(template, values) {
    return template.replace(/\{(\w+)\}/g, (placeholder, key) => (key in values ? values[key] : placeholder));
}

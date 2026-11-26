// Odliczanie do konca aukcji. Zrodlem prawdy jest czas serwera: przy kazdej odpowiedzi zapamietujemy
// serverTime i endsAt, a uplyw czasu mierzymy zegarem monotonicznym (performance.now), wiec
// zle ustawiony zegar przegladarki nie ma wplywu na wynik.
import { timeLeft } from './format.js';

export function createCountdown(elements, labels, onZero) {
    let endsAtMs = null;
    let serverMsAtSync = 0;
    let perfAtSync = 0;
    let timer = null;
    let zeroNotifiedFor = null;
    let lastText = '';

    const remainingSeconds = () => {
        const serverNow = serverMsAtSync + (performance.now() - perfAtSync);

        return Math.max(0, Math.ceil((endsAtMs - serverNow) / 1000));
    };

    const render = () => {
        if (endsAtMs === null) {
            return;
        }
        const seconds = remainingSeconds();
        const text = timeLeft(seconds, labels);
        if (text !== lastText) {
            lastText = text;
            elements.forEach((element) => {
                element.textContent = text;
            });
        }
        if (seconds === 0 && zeroNotifiedFor !== endsAtMs) {
            zeroNotifiedFor = endsAtMs; // jednorazowo na dany koniec: pobierz autorytatywny stan z serwera
            onZero();
        }
    };

    return {
        sync(endsAtIso, serverTimeIso) {
            const ends = Date.parse(endsAtIso);
            const serverNow = Date.parse(serverTimeIso);
            if (Number.isNaN(ends) || Number.isNaN(serverNow)) {
                return;
            }
            endsAtMs = ends;
            serverMsAtSync = serverNow;
            perfAtSync = performance.now();
            render();
            if (timer === null) {
                timer = setInterval(render, 250);
            }
        },
        stop() {
            clearInterval(timer);
            timer = null;
        },
    };
}

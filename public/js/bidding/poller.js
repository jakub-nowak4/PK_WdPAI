// Polling stanu aukcji (GET): setTimeout z backoffem po bledach, pauza gdy karta jest ukryta (Page Visibility).
import { fetchState } from './api.js';

const BASE_DELAY_MS = 3000;
const MAX_DELAY_MS = 15000;

export function createPoller(url, { onState, onOnline, onOffline }) {
    let timer = null;
    let controller = null;
    let failures = 0;
    let stopped = false;
    let sequence = 0;
    let lastApplied = 0;

    const delay = () => Math.min(MAX_DELAY_MS, BASE_DELAY_MS * 2 ** failures);

    function schedule(ms) {
        clearTimeout(timer);
        if (!stopped && !document.hidden) {
            timer = setTimeout(tick, ms);
        }
    }

    async function tick() {
        const mine = ++sequence;
        controller = new AbortController();
        try {
            const state = await fetchState(url, controller.signal);
            failures = 0;
            onOnline();
            if (mine > lastApplied) { // odpowiedz starsza niz juz zastosowany stan (np. z POST) jest pomijana
                lastApplied = mine;
                onState(state);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return; // wstrzymanie (karta ukryta) lub stop; nie planujemy kolejnej proby
            }
            failures += 1;
            onOffline();
        }
        schedule(delay());
    }

    function pause() {
        clearTimeout(timer);
        controller?.abort();
    }

    document.addEventListener('visibilitychange', () => {
        if (stopped) {
            return;
        }
        if (document.hidden) {
            pause();
        } else {
            clearTimeout(timer);
            tick();
        }
    });

    return {
        start: () => schedule(BASE_DELAY_MS),
        pollNow() {
            if (!stopped && !document.hidden) {
                clearTimeout(timer);
                controller?.abort();
                tick();
            }
        },
        // Stan z odpowiedzi POST jest nowszy niz kazdy GET wyslany wczesniej.
        claimSequence() {
            sequence += 1;
            lastApplied = sequence;
        },
        stop() {
            stopped = true;
            pause();
        },
    };
}

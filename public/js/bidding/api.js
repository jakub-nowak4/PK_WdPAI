// Fetch API wg docs/api.md 3.1 (GET stan) i 3.2 (POST oferta). Zwraca {status, ok, data}; blad sieci to wyjatek NetworkError.

export class NetworkError extends Error {}

async function readJson(response) {
    try {
        return await response.json();
    } catch {
        return null; // np. 502 z HTML od serwera proxy
    }
}

export async function fetchState(url, signal) {
    let response;
    try {
        response = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
            signal,
        });
    } catch (error) {
        if (error.name === 'AbortError') {
            throw error;
        }
        throw new NetworkError('state request failed');
    }
    const data = await readJson(response);
    if (!response.ok || !data || !data.auction) {
        throw new NetworkError('state response invalid: ' + response.status);
    }

    return data;
}

export async function postBid(url, amountGrosze, csrfToken) {
    let response;
    try {
        response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ amount: amountGrosze }),
        });
    } catch {
        throw new NetworkError('bid request failed');
    }

    return { status: response.status, ok: response.ok, data: await readJson(response) };
}

const SW_VERSION = '2026-04-26-1';
const CACHE_PREFIX = 'chickencare-sw-';
const PRECACHE_NAME = `${CACHE_PREFIX}precache-v${SW_VERSION}`;
const ASSET_CACHE_NAME = `${CACHE_PREFIX}assets-v${SW_VERSION}`;
const HTML_CACHE_NAME = `${CACHE_PREFIX}html-v${SW_VERSION}`;
const HTML_NETWORK_TIMEOUT_MS = 2500;
const OFFLINE_HTML_PATH = '/offline';
const OFFLINE_QUEUE_DB_NAME = 'chickencare-offline-queue';
const OFFLINE_QUEUE_DB_VERSION = 1;
const OFFLINE_QUEUE_QUEUED_STORE = 'queued';
const OFFLINE_QUEUE_FAILED_STORE = 'failed';
const OFFLINE_QUEUE_LOCK_MS = 15_000;
const OFFLINE_QUEUE_SYNC_TAG = 'chickencare-offline-sync';
const PRECACHE_URLS = [
    OFFLINE_HTML_PATH,
    '/manifest.webmanifest',
    '/favicon.ico',
    '/images/pwa/apple-touch-icon.png',
    '/images/pwa/icon-192-maskable.png',
    '/images/pwa/icon-512-maskable.png',
    '/images/pwa/icon-512.png',
    '/fonts/fraunces-400.woff2',
    '/fonts/fraunces-500.woff2',
    '/fonts/fraunces-600.woff2',
    '/fonts/fraunces-700.woff2',
];
const CURRENT_CACHE_NAMES = [PRECACHE_NAME, ASSET_CACHE_NAME, HTML_CACHE_NAME];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(PRECACHE_NAME);

        await cache.addAll(PRECACHE_URLS.map((url) => new Request(url, { cache: 'reload' })));
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const cacheKeys = await caches.keys();

        await Promise.all(
            cacheKeys
                .filter((key) => key.startsWith(CACHE_PREFIX) && !CURRENT_CACHE_NAMES.includes(key))
                .map((key) => caches.delete(key)),
        );
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (isImmutableAssetRequest(url)) {
        event.respondWith(handleImmutableAssetRequest(request));
        return;
    }

    if (isJsonRequest(request)) {
        event.respondWith(fetch(request));
        return;
    }

    if (isHtmxFragmentRequest(request)) {
        event.respondWith(handleHtmxFragmentRequest(request));
        return;
    }

    if (isAppHtmlRequest(request, url)) {
        event.respondWith(handleAppHtmlRequest(request));
    }
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }

    if (event.data?.type === 'OFFLINE_QUEUE_REPLAY') {
        void replayQueuedItems('message');
        return;
    }

    if (event.data?.type === 'PWA_PING') {
        event.source?.postMessage({
            type: 'PWA_PONG',
            version: SW_VERSION,
        });
    }
});

self.addEventListener('sync', (event) => {
    if (event.tag === OFFLINE_QUEUE_SYNC_TAG) {
        event.waitUntil(replayQueuedItems('background-sync'));
    }
});

function isImmutableAssetRequest(url) {
    return url.pathname.startsWith('/build/assets/');
}

function isJsonRequest(request) {
    const accept = request.headers.get('Accept') || '';

    return accept.includes('application/json');
}

function isHtmxFragmentRequest(request) {
    return request.headers.get('HX-Request') === 'true' && request.headers.get('HX-Boosted') !== 'true';
}

function isAppHtmlRequest(request, url) {
    if (!url.pathname.startsWith('/app')) {
        return false;
    }

    if (request.mode === 'navigate') {
        return true;
    }

    if (request.headers.get('HX-Boosted') === 'true') {
        return true;
    }

    const accept = request.headers.get('Accept') || '';

    return accept.includes('text/html');
}

async function handleImmutableAssetRequest(request) {
    const cache = await caches.open(ASSET_CACHE_NAME);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    const networkResponse = await fetch(request);

    if (networkResponse.ok) {
        await cache.put(request, networkResponse.clone());
    }

    return networkResponse;
}

async function handleHtmxFragmentRequest(request) {
    try {
        return await fetch(request);
    } catch {
        return new Response(buildOfflineFragmentMarkup(), {
            headers: {
                'Content-Type': 'text/html; charset=utf-8',
                'X-ChickenCare-Offline': 'true',
            },
            status: 200,
        });
    }
}

async function handleAppHtmlRequest(request) {
    const cache = await caches.open(HTML_CACHE_NAME);
    const cacheKey = request.url;
    const cachedResponse = await cache.match(cacheKey);

    try {
        const networkResponse = await withTimeout(fetch(request), HTML_NETWORK_TIMEOUT_MS);

        if (shouldPersistHtmlFallback(request, networkResponse)) {
            await cache.put(cacheKey, networkResponse.clone());
        }

        return networkResponse;
    } catch {
        if (cachedResponse) {
            return cachedResponse;
        }

        return (await getPrecachedOfflinePage()) || Response.error();
    }
}

function shouldPersistHtmlFallback(request, response) {
    if (!response.ok) {
        return false;
    }

    if (response.redirected) {
        return false;
    }

    const requestUrl = new URL(request.url);
    const responseUrl = new URL(response.url || request.url, self.location.origin);

    if (requestUrl.pathname !== responseUrl.pathname) {
        return false;
    }

    const contentType = response.headers.get('Content-Type') || '';

    return contentType.includes('text/html');
}

async function getPrecachedOfflinePage() {
    const cache = await caches.open(PRECACHE_NAME);

    return cache.match(OFFLINE_HTML_PATH);
}

function withTimeout(promise, timeoutMs) {
    return new Promise((resolve, reject) => {
        const timeoutId = self.setTimeout(() => {
            reject(new Error('Timed out waiting for network response.'));
        }, timeoutMs);

        promise
            .then((value) => {
                self.clearTimeout(timeoutId);
                resolve(value);
            })
            .catch((error) => {
                self.clearTimeout(timeoutId);
                reject(error);
            });
    });
}

function buildOfflineFragmentMarkup() {
    const message = localizedQueueMessage('fragmentOffline');

    return `
        <div class="flash flash--warning" data-offline-fragment="true" role="status" aria-live="polite">
            ${message}
        </div>
    `;
}

function localizedQueueMessage(key) {
    const isSerbian = (self.navigator?.language || '').toLowerCase().startsWith('sr');
    const messages = {
        en: {
            fragmentOffline: 'You are offline right now. Try again once the connection returns.',
            reviewFailed: 'ChickenCare could not sync one offline item. Review it in Account Settings.',
            sessionExpired: 'Your session expired before the offline item could sync.',
            csrfRetryFailed: 'ChickenCare could not refresh your session to sync this item.',
            networkError: 'ChickenCare could not reach the network.',
            interactiveHtmlFailure: 'This offline item needs manual review before it can be completed.',
        },
        sr: {
            fragmentOffline: 'Trenutno ste van mreze. Pokusajte ponovo kada se veza vrati.',
            reviewFailed: 'ChickenCare nije mogao da sinhronizuje jedan offline unos. Pregledajte ga u podesavanjima naloga.',
            sessionExpired: 'Sesija je istekla pre nego sto je offline unos mogao da se sinhronizuje.',
            csrfRetryFailed: 'ChickenCare nije mogao da osvezi sesiju da bi sinhronizovao ovaj unos.',
            networkError: 'ChickenCare trenutno ne moze da pristupi mrezi.',
            interactiveHtmlFailure: 'Ovaj offline unos zahteva rucni pregled pre zavrsetka.',
        },
    };

    return messages[isSerbian ? 'sr' : 'en'][key];
}

async function replayQueuedItems(reason) {
    const ownerId = `sw-${reason}-${Date.now()}`;

    while (true) {
        const item = await claimQueuedItem(ownerId);

        if (!item) {
            return;
        }

        await replayQueuedItem(item);
    }
}

async function replayQueuedItem(item) {
    const freshToken = await fetchFreshCsrfToken();

    if (!freshToken?.token) {
        await failQueuedItem(item, freshToken?.reason || localizedQueueMessage('sessionExpired'));
        return;
    }

    let outcome = await sendQueuedRequest(item, freshToken.token);

    if (outcome.type === 'refresh') {
        const retryToken = await fetchFreshCsrfToken();

        if (!retryToken?.token) {
            outcome = {
                type: 'failed',
                reason: retryToken?.reason || localizedQueueMessage('csrfRetryFailed'),
            };
        } else {
            outcome = await sendQueuedRequest(item, retryToken.token);

            if (outcome.type === 'refresh') {
                outcome = {
                    type: 'failed',
                    reason: localizedQueueMessage('csrfRetryFailed'),
                };
            }
        }
    }

    if (outcome.type === 'success') {
        await completeQueuedItem(item.id);
        await notifyWindowClients({ type: 'OFFLINE_QUEUE_SYNCED' });
        return;
    }

    if (outcome.type === 'retry') {
        await releaseQueuedItem(item, outcome.reason);
        return;
    }

    await failQueuedItem(item, outcome.reason);
}

async function sendQueuedRequest(item, csrfToken) {
    try {
        const response = await fetch(item.url, {
            method: item.method,
            credentials: 'same-origin',
            headers: {
                ...item.headers,
                'X-CSRF-TOKEN': csrfToken,
            },
            body: buildQueuedRequestBody(item.fields, csrfToken),
        });

        return classifyQueuedReplayResponse(response);
    } catch (error) {
        return {
            type: 'retry',
            reason: error instanceof Error ? error.message : localizedQueueMessage('networkError'),
        };
    }
}

async function fetchFreshCsrfToken() {
    try {
        const response = await fetch('/csrf-token', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok || response.redirected) {
            return { reason: localizedQueueMessage('sessionExpired') };
        }

        const contentType = response.headers.get('Content-Type') || '';

        if (!contentType.includes('application/json')) {
            return { reason: localizedQueueMessage('sessionExpired') };
        }

        const payload = await response.json();

        if (typeof payload.token !== 'string' || payload.token.trim() === '') {
            return { reason: localizedQueueMessage('csrfRetryFailed') };
        }

        return { token: payload.token };
    } catch (error) {
        return {
            reason: error instanceof Error ? error.message : localizedQueueMessage('networkError'),
        };
    }
}

async function classifyQueuedReplayResponse(response) {
    if (response.status === 419) {
        return { type: 'refresh', reason: localizedQueueMessage('csrfRetryFailed') };
    }

    if (!response.ok) {
        if (response.status >= 500) {
            return { type: 'retry', reason: `HTTP ${response.status}` };
        }

        return { type: 'failed', reason: `HTTP ${response.status}` };
    }

    if (response.redirected) {
        return { type: 'failed', reason: localizedQueueMessage('sessionExpired') };
    }

    const contentType = response.headers.get('Content-Type') || '';

    if (!contentType.includes('text/html')) {
        return { type: 'success' };
    }

    const body = await response.clone().text();

    if (response.headers.get('HX-Retarget') || response.headers.get('HX-Reswap')) {
        return { type: 'failed', reason: localizedQueueMessage('interactiveHtmlFailure') };
    }

    if (/<html[\s>]/i.test(body)) {
        return { type: 'failed', reason: localizedQueueMessage('sessionExpired') };
    }

    return { type: 'success' };
}

function buildQueuedRequestBody(fields, csrfToken) {
    const params = new URLSearchParams();

    fields.forEach(([name, value]) => {
        params.append(name, value);
    });

    params.set('_token', csrfToken);

    return params;
}

async function notifyWindowClients(message) {
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

    clients.forEach((client) => {
        client.postMessage(message);
    });
}

function openOfflineQueueDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(OFFLINE_QUEUE_DB_NAME, OFFLINE_QUEUE_DB_VERSION);

        request.onupgradeneeded = () => {
            const database = request.result;

            if (!database.objectStoreNames.contains(OFFLINE_QUEUE_QUEUED_STORE)) {
                database.createObjectStore(OFFLINE_QUEUE_QUEUED_STORE, { keyPath: 'id' });
            }

            if (!database.objectStoreNames.contains(OFFLINE_QUEUE_FAILED_STORE)) {
                database.createObjectStore(OFFLINE_QUEUE_FAILED_STORE, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error('Unable to open the offline queue database.'));
    });
}

async function claimQueuedItem(ownerId) {
    const database = await openOfflineQueueDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(OFFLINE_QUEUE_QUEUED_STORE, 'readwrite');
        const store = transaction.objectStore(OFFLINE_QUEUE_QUEUED_STORE);
        const now = Date.now();
        let claimedItem = null;

        store.openCursor().onsuccess = (event) => {
            const cursor = event.target.result;

            if (!cursor || claimedItem) {
                return;
            }

            const value = cursor.value;

            if (!value.lockExpiresAt || value.lockExpiresAt <= now || value.lockedBy === ownerId) {
                claimedItem = {
                    ...value,
                    lockedBy: ownerId,
                    lockExpiresAt: now + OFFLINE_QUEUE_LOCK_MS,
                };
                cursor.update(claimedItem);
                return;
            }

            cursor.continue();
        };

        transaction.oncomplete = () => {
            database.close();
            resolve(claimedItem);
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error || new Error('Unable to claim a queued offline submission.'));
        };
    });
}

async function completeQueuedItem(id) {
    const database = await openOfflineQueueDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(OFFLINE_QUEUE_QUEUED_STORE, 'readwrite');
        transaction.objectStore(OFFLINE_QUEUE_QUEUED_STORE).delete(id);

        transaction.oncomplete = () => {
            database.close();
            resolve();
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error || new Error('Unable to remove a synced offline submission.'));
        };
    });
}

async function releaseQueuedItem(item, reason) {
    const database = await openOfflineQueueDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(OFFLINE_QUEUE_QUEUED_STORE, 'readwrite');
        transaction.objectStore(OFFLINE_QUEUE_QUEUED_STORE).put({
            ...item,
            attemptCount: item.attemptCount + 1,
            lastError: reason,
            lockedBy: null,
            lockExpiresAt: null,
        });

        transaction.oncomplete = () => {
            database.close();
            resolve();
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error || new Error('Unable to release a queued offline submission.'));
        };
    });
}

async function failQueuedItem(item, reason) {
    const database = await openOfflineQueueDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction([OFFLINE_QUEUE_QUEUED_STORE, OFFLINE_QUEUE_FAILED_STORE], 'readwrite');
        transaction.objectStore(OFFLINE_QUEUE_QUEUED_STORE).delete(item.id);
        transaction.objectStore(OFFLINE_QUEUE_FAILED_STORE).put({
            ...item,
            attemptCount: item.attemptCount + 1,
            lastError: reason,
            failedAt: new Date().toISOString(),
            lockedBy: null,
            lockExpiresAt: null,
        });

        transaction.oncomplete = async () => {
            database.close();
            await notifyWindowClients({
                type: 'OFFLINE_QUEUE_FAILED_CHANGED',
                message: localizedQueueMessage('reviewFailed'),
            });
            resolve();
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error || new Error('Unable to move an offline submission to failed review.'));
        };
    });
}
export const OFFLINE_QUEUE_SYNC_TAG = 'chickencare-offline-sync';

const DB_NAME = 'chickencare-offline-queue';
const DB_VERSION = 1;
const QUEUED_STORE = 'queued';
const FAILED_STORE = 'failed';
const LOCK_LEASE_MS = 15_000;

const QUEUE_SOURCE_DEFINITIONS = {
    eggs: {
        key: 'eggs',
        labels: {
            en: 'Egg entry',
            sr: 'Unos jaja',
        },
    },
    expenses: {
        key: 'expenses',
        labels: {
            en: 'Expense entry',
            sr: 'Unos troska',
        },
    },
    feed: {
        key: 'feed',
        labels: {
            en: 'Feed entry',
            sr: 'Unos hrane',
        },
    },
    'batch-deaths': {
        key: 'batch-deaths',
        labels: {
            en: 'Death record',
            sr: 'Evidencija uginuća',
        },
    },
};

const QUEUE_TRANSLATIONS = {
    en: {
        savedOffline: 'Saved offline - will sync when you are back online.',
        synced: 'Synced.',
        reviewFailed: 'ChickenCare could not sync one offline item. Review it in Account Settings.',
        onlineOnly: 'This action needs a live connection and cannot be queued offline.',
        networkError: 'ChickenCare could not reach the network.',
        sessionExpired: 'Your session expired before the offline item could sync.',
        csrfRetryFailed: 'ChickenCare could not refresh your session to sync this item.',
        interactiveHtmlFailure: 'This offline item needs manual review before it can be completed.',
    },
    sr: {
        savedOffline: 'Sacuvano van mreze - sinhronizovace se kada se veza vrati.',
        synced: 'Sinhronizovano.',
        reviewFailed: 'ChickenCare nije mogao da sinhronizuje jedan offline unos. Pregledajte ga u podesavanjima naloga.',
        onlineOnly: 'Ova radnja zahteva aktivnu vezu i ne moze da se cuva offline.',
        networkError: 'ChickenCare trenutno ne moze da pristupi mrezi.',
        sessionExpired: 'Sesija je istekla pre nego sto je offline unos mogao da se sinhronizuje.',
        csrfRetryFailed: 'ChickenCare nije mogao da osvezi sesiju da bi sinhronizovao ovaj unos.',
        interactiveHtmlFailure: 'Ovaj offline unos zahteva rucni pregled pre zavrsetka.',
    },
};

function resolveLanguageTag(locale) {
    return (locale || 'en').toLowerCase().startsWith('sr') ? 'sr' : 'en';
}

export function resolveQueueTranslations(locale = 'en') {
    return QUEUE_TRANSLATIONS[resolveLanguageTag(locale)];
}

function queueSourceLabel(sourceKey, locale = 'en') {
    const source = QUEUE_SOURCE_DEFINITIONS[sourceKey];

    if (!source) {
        return sourceKey;
    }

    return source.labels[resolveLanguageTag(locale)] || source.labels.en;
}

function parseHeaderJson(rawValue) {
    if (typeof rawValue !== 'string' || rawValue.trim() === '') {
        return {};
    }

    try {
        const parsed = JSON.parse(rawValue);

        return typeof parsed === 'object' && parsed !== null ? parsed : {};
    } catch {
        return {};
    }
}

function createQueueId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}

function isFormElement(element) {
    return typeof HTMLFormElement !== 'undefined' && element instanceof HTMLFormElement;
}

function resolveFormMethod(form) {
    const attributeMethod = ['hx-post', 'hx-put', 'hx-patch', 'hx-delete']
        .find((attribute) => form.hasAttribute(attribute));

    if (attributeMethod === 'hx-post') {
        return 'POST';
    }

    if (attributeMethod === 'hx-put') {
        return 'PUT';
    }

    if (attributeMethod === 'hx-patch') {
        return 'PATCH';
    }

    if (attributeMethod === 'hx-delete') {
        return 'DELETE';
    }

    return (form.getAttribute('method') || 'GET').toUpperCase();
}

function resolveFormUrl(form, locationObject = globalThis.location) {
    const rawUrl = form.getAttribute('hx-post')
        || form.getAttribute('hx-put')
        || form.getAttribute('hx-patch')
        || form.getAttribute('hx-delete')
        || form.getAttribute('action')
        || locationObject?.href
        || '/';

    return new URL(rawUrl, locationObject?.origin || 'http://localhost').toString();
}

function resolveRequestHeaders(form, documentObject = globalThis.document) {
    const bodyHeaders = parseHeaderJson(documentObject?.body?.getAttribute('hx-headers'));
    const formHeaders = parseHeaderJson(form.getAttribute('hx-headers'));

    return {
        ...bodyHeaders,
        ...formHeaders,
        'HX-Request': 'true',
        'X-Requested-With': 'XMLHttpRequest',
    };
}

export function detectQueueSource(form) {
    if (!isFormElement(form)) {
        return null;
    }

    const queueKey = form.dataset.offlineQueue;

    return queueKey && QUEUE_SOURCE_DEFINITIONS[queueKey] ? queueKey : null;
}

export function isMutatingHtmxForm(form) {
    if (!isFormElement(form)) {
        return false;
    }

    const method = resolveFormMethod(form);

    return method !== 'GET' && (
        form.hasAttribute('hx-post')
        || form.hasAttribute('hx-put')
        || form.hasAttribute('hx-patch')
        || form.hasAttribute('hx-delete')
        || form.hasAttribute('data-offline-queue')
    );
}

function hasFileInput(form) {
    return Array.from(form.querySelectorAll('input[type="file"]')).some((input) => !input.disabled);
}

function resolveCsrfToken(documentObject = globalThis.document) {
    return documentObject?.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function syncResetFields(form) {
    form.reset();

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    });
}

function updateDocumentCsrfToken(token, documentObject = globalThis.document) {
    const csrfMeta = documentObject?.querySelector('meta[name="csrf-token"]');

    if (csrfMeta) {
        csrfMeta.setAttribute('content', token);
    }

    const body = documentObject?.body;

    if (body?.hasAttribute('hx-headers')) {
        const headers = parseHeaderJson(body.getAttribute('hx-headers'));
        headers['X-CSRF-TOKEN'] = token;
        body.setAttribute('hx-headers', JSON.stringify(headers));
    }
}

export function createQueueItemFromForm(form, options = {}) {
    const formDataConstructor = options.FormDataConstructor || globalThis.FormData;
    const documentObject = options.documentObject || globalThis.document;
    const locationObject = options.locationObject || globalThis.location;
    const locale = options.locale || documentObject?.documentElement?.lang || 'en';
    const now = typeof options.now === 'function' ? options.now() : new Date().toISOString();
    const queueKey = options.queueKey || detectQueueSource(form);

    if (!queueKey || !formDataConstructor) {
        return null;
    }

    const fields = [];

    for (const [name, value] of new formDataConstructor(form).entries()) {
        if (typeof value === 'string') {
            fields.push([name, value]);
        }
    }

    if (!fields.some(([name]) => name === '_token')) {
        fields.push(['_token', resolveCsrfToken(documentObject)]);
    }

    return {
        id: createQueueId(),
        queueKey,
        sourceLabel: queueSourceLabel(queueKey, locale),
        method: resolveFormMethod(form),
        url: resolveFormUrl(form, locationObject),
        headers: resolveRequestHeaders(form, documentObject),
        fields,
        csrfToken: resolveCsrfToken(documentObject),
        createdAt: now,
        attemptCount: 0,
        lastError: null,
        failedAt: null,
        lockedBy: null,
        lockExpiresAt: null,
    };
}

function openQueueDatabase(indexedDb) {
    return new Promise((resolve, reject) => {
        const request = indexedDb.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const database = request.result;

            if (!database.objectStoreNames.contains(QUEUED_STORE)) {
                database.createObjectStore(QUEUED_STORE, { keyPath: 'id' });
            }

            if (!database.objectStoreNames.contains(FAILED_STORE)) {
                database.createObjectStore(FAILED_STORE, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error || new Error('Unable to open the offline queue database.'));
    });
}

function createIndexedDbQueueStore(indexedDb = globalThis.indexedDB) {
    if (!indexedDb) {
        throw new Error('IndexedDB is required for the offline queue.');
    }

    return {
        async queue(item) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(QUEUED_STORE, 'readwrite');
                transaction.objectStore(QUEUED_STORE).put(item);

                transaction.oncomplete = () => {
                    database.close();
                    resolve(item);
                };
                transaction.onerror = () => {
                    database.close();
                    reject(transaction.error || new Error('Unable to store the offline queue item.'));
                };
            });
        },

        async claim(ownerId, leaseMs = LOCK_LEASE_MS) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(QUEUED_STORE, 'readwrite');
                const store = transaction.objectStore(QUEUED_STORE);
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
                            lockExpiresAt: now + leaseMs,
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
                    reject(transaction.error || new Error('Unable to claim an offline queue item.'));
                };
            });
        },

        async complete(id) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(QUEUED_STORE, 'readwrite');
                transaction.objectStore(QUEUED_STORE).delete(id);

                transaction.oncomplete = () => {
                    database.close();
                    resolve();
                };
                transaction.onerror = () => {
                    database.close();
                    reject(transaction.error || new Error('Unable to remove a synced offline queue item.'));
                };
            });
        },

        async release(item, patch = {}) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(QUEUED_STORE, 'readwrite');
                transaction.objectStore(QUEUED_STORE).put({
                    ...item,
                    ...patch,
                    lockedBy: null,
                    lockExpiresAt: null,
                });

                transaction.oncomplete = () => {
                    database.close();
                    resolve();
                };
                transaction.onerror = () => {
                    database.close();
                    reject(transaction.error || new Error('Unable to release an offline queue item.'));
                };
            });
        },

        async fail(item, patch = {}) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction([QUEUED_STORE, FAILED_STORE], 'readwrite');
                transaction.objectStore(QUEUED_STORE).delete(item.id);
                transaction.objectStore(FAILED_STORE).put({
                    ...item,
                    ...patch,
                    lockedBy: null,
                    lockExpiresAt: null,
                });

                transaction.oncomplete = () => {
                    database.close();
                    resolve();
                };
                transaction.onerror = () => {
                    database.close();
                    reject(transaction.error || new Error('Unable to move an offline queue item to failed review.'));
                };
            });
        },

        async listFailed() {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(FAILED_STORE, 'readonly');
                const request = transaction.objectStore(FAILED_STORE).getAll();

                request.onsuccess = () => {
                    database.close();
                    resolve(request.result.sort((left, right) => String(right.failedAt || right.createdAt).localeCompare(String(left.failedAt || left.createdAt))));
                };
                request.onerror = () => {
                    database.close();
                    reject(request.error || new Error('Unable to read failed offline queue items.'));
                };
            });
        },

        async discardFailed(id) {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(FAILED_STORE, 'readwrite');
                transaction.objectStore(FAILED_STORE).delete(id);

                transaction.oncomplete = () => {
                    database.close();
                    resolve();
                };
                transaction.onerror = () => {
                    database.close();
                    reject(transaction.error || new Error('Unable to discard a failed offline queue item.'));
                };
            });
        },

        async listQueued() {
            const database = await openQueueDatabase(indexedDb);

            return new Promise((resolve, reject) => {
                const transaction = database.transaction(QUEUED_STORE, 'readonly');
                const request = transaction.objectStore(QUEUED_STORE).getAll();

                request.onsuccess = () => {
                    database.close();
                    resolve(request.result);
                };
                request.onerror = () => {
                    database.close();
                    reject(request.error || new Error('Unable to read queued offline items.'));
                };
            });
        },
    };
}

export function createMemoryQueueStore(initialQueued = [], initialFailed = []) {
    const queued = [...initialQueued];
    const failed = [...initialFailed];

    return {
        async queue(item) {
            queued.push(structuredClone(item));
            return item;
        },

        async claim(ownerId, leaseMs = LOCK_LEASE_MS) {
            const now = Date.now();
            const nextItem = queued.find((item) => !item.lockExpiresAt || item.lockExpiresAt <= now || item.lockedBy === ownerId);

            if (!nextItem) {
                return null;
            }

            nextItem.lockedBy = ownerId;
            nextItem.lockExpiresAt = now + leaseMs;

            return structuredClone(nextItem);
        },

        async complete(id) {
            const index = queued.findIndex((item) => item.id === id);

            if (index >= 0) {
                queued.splice(index, 1);
            }
        },

        async release(item, patch = {}) {
            const index = queued.findIndex((queuedItem) => queuedItem.id === item.id);

            if (index >= 0) {
                queued[index] = {
                    ...queued[index],
                    ...patch,
                    lockedBy: null,
                    lockExpiresAt: null,
                };
            }
        },

        async fail(item, patch = {}) {
            const index = queued.findIndex((queuedItem) => queuedItem.id === item.id);

            if (index >= 0) {
                queued.splice(index, 1);
            }

            failed.push({
                ...structuredClone(item),
                ...patch,
                lockedBy: null,
                lockExpiresAt: null,
            });
        },

        async listFailed() {
            return structuredClone(failed);
        },

        async discardFailed(id) {
            const index = failed.findIndex((item) => item.id === id);

            if (index >= 0) {
                failed.splice(index, 1);
            }
        },

        async listQueued() {
            return structuredClone(queued);
        },
    };
}

export async function classifyReplayResponse(response, translations = resolveQueueTranslations()) {
    if (response.status === 419) {
        return { type: 'refresh', reason: translations.csrfRetryFailed };
    }

    if (!response.ok) {
        if (response.status >= 500) {
            return { type: 'retry', reason: `HTTP ${response.status}` };
        }

        return { type: 'failed', reason: `HTTP ${response.status}` };
    }

    if (response.redirected) {
        return { type: 'failed', reason: translations.sessionExpired };
    }

    const contentType = response.headers.get('Content-Type') || '';

    if (!contentType.includes('text/html')) {
        return { type: 'success' };
    }

    const body = await response.clone().text();

    if (response.headers.get('HX-Retarget') || response.headers.get('HX-Reswap')) {
        return { type: 'failed', reason: translations.interactiveHtmlFailure };
    }

    if (/<html[\s>]/i.test(body)) {
        return { type: 'failed', reason: translations.sessionExpired };
    }

    return { type: 'success' };
}

function createRequestBody(fields, csrfToken) {
    const params = new URLSearchParams();

    fields.forEach(([name, value]) => {
        params.append(name, value);
    });

    params.set('_token', csrfToken);

    return params;
}

export function createQueueProcessor(options) {
    const store = options.store;
    const fetchImpl = options.fetchImpl;
    const refreshCsrfToken = options.refreshCsrfToken;
    const registerSync = options.registerSync || (async () => {});
    const notify = options.notify || (() => {});
    const dispatchFailedItemsChanged = options.dispatchFailedItemsChanged || (() => {});
    const translations = options.translations || resolveQueueTranslations();
    const ownerId = options.ownerId || createQueueId();

    let activeReplay = null;

    async function replayClaimedItem(item) {
        const freshToken = await refreshCsrfToken();

        if (!freshToken?.token) {
            await store.fail(item, {
                attemptCount: item.attemptCount + 1,
                lastError: freshToken?.reason || translations.sessionExpired,
                failedAt: new Date().toISOString(),
            });
            notify('error', translations.reviewFailed);
            dispatchFailedItemsChanged();
            return;
        }

        const send = async (csrfToken) => {
            try {
                const response = await fetchImpl(item.url, {
                    method: item.method,
                    credentials: 'same-origin',
                    headers: {
                        ...item.headers,
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: createRequestBody(item.fields, csrfToken),
                });

                return classifyReplayResponse(response, translations);
            } catch (error) {
                return {
                    type: 'retry',
                    reason: error instanceof Error ? error.message : translations.networkError,
                };
            }
        };

        let outcome = await send(freshToken.token);

        if (outcome.type === 'refresh') {
            const retryToken = await refreshCsrfToken();

            if (!retryToken?.token) {
                outcome = {
                    type: 'failed',
                    reason: retryToken?.reason || translations.csrfRetryFailed,
                };
            } else {
                outcome = await send(retryToken.token);

                if (outcome.type === 'refresh') {
                    outcome = {
                        type: 'failed',
                        reason: translations.csrfRetryFailed,
                    };
                }
            }
        }

        if (outcome.type === 'success') {
            await store.complete(item.id);
            notify('success', translations.synced);
            return;
        }

        if (outcome.type === 'retry') {
            await store.release(item, {
                attemptCount: item.attemptCount + 1,
                lastError: outcome.reason,
            });
            return;
        }

        await store.fail(item, {
            attemptCount: item.attemptCount + 1,
            lastError: outcome.reason,
            failedAt: new Date().toISOString(),
        });
        notify('error', translations.reviewFailed);
        dispatchFailedItemsChanged();
    }

    return {
        async enqueue(item) {
            await store.queue(item);
            await registerSync(OFFLINE_QUEUE_SYNC_TAG);
            return item;
        },

        async replayPending(reason = 'manual') {
            if (activeReplay) {
                return activeReplay;
            }

            activeReplay = (async () => {
                let processed = 0;

                while (true) {
                    const item = await store.claim(ownerId);

                    if (!item) {
                        break;
                    }

                    processed += 1;
                    await replayClaimedItem(item, reason);
                }

                return processed;
            })();

            try {
                return await activeReplay;
            } finally {
                activeReplay = null;
            }
        },

        async listFailedItems() {
            return store.listFailed();
        },

        async discardFailedItem(id) {
            await store.discardFailed(id);
            dispatchFailedItemsChanged();
        },
    };
}

export function createOfflineQueueManager(options = {}) {
    const windowObject = options.windowObject || globalThis.window;
    const documentObject = options.documentObject || globalThis.document;
    const navigatorObject = options.navigatorObject || globalThis.navigator;
    const fetchImpl = options.fetchImpl || globalThis.fetch?.bind(globalThis);
    const translations = options.translations || resolveQueueTranslations(documentObject?.documentElement?.lang || navigatorObject?.language || 'en');
    const supported = Boolean(windowObject && documentObject && navigatorObject && fetchImpl && globalThis.indexedDB);

    if (!supported) {
        return {
            initialize() {},
            failedItemsPanel() {
                return {
                    items: [],
                    loading: false,
                    init() {},
                    async discard() {},
                    formatTimestamp(value) {
                        return value;
                    },
                };
            },
        };
    }

    const store = createIndexedDbQueueStore(globalThis.indexedDB);
    const pendingSubmissions = new WeakMap();
    const failedItemsChangedEvent = 'offline-queue:failed-changed';

    const notify = (type, message) => {
        windowObject.dispatchEvent(new CustomEvent(`toast:${type}`, {
            detail: { message },
        }));
    };

    const dispatchFailedItemsChanged = () => {
        windowObject.dispatchEvent(new CustomEvent(failedItemsChangedEvent));
    };

    const registerSync = async (tag) => {
        if (!navigatorObject.serviceWorker?.ready) {
            return;
        }

        try {
            const registration = await navigatorObject.serviceWorker.ready;

            if ('sync' in registration) {
                await registration.sync.register(tag);
            }
        } catch {
            // Background Sync is best-effort only.
        }
    };

    const refreshCsrfToken = async () => {
        try {
            const response = await fetchImpl('/csrf-token', {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok || response.redirected) {
                return { reason: translations.sessionExpired };
            }

            const contentType = response.headers.get('Content-Type') || '';

            if (!contentType.includes('application/json')) {
                return { reason: translations.sessionExpired };
            }

            const payload = await response.json();

            if (typeof payload.token !== 'string' || payload.token.trim() === '') {
                return { reason: translations.csrfRetryFailed };
            }

            updateDocumentCsrfToken(payload.token, documentObject);

            return { token: payload.token };
        } catch (error) {
            return {
                reason: error instanceof Error ? error.message : translations.networkError,
            };
        }
    };

    const processor = createQueueProcessor({
        store,
        fetchImpl,
        refreshCsrfToken,
        registerSync,
        notify,
        dispatchFailedItemsChanged,
        translations,
        ownerId: `page-${createQueueId()}`,
    });

    const queueForm = async (form) => {
        const item = createQueueItemFromForm(form, {
            documentObject,
            locationObject: windowObject.location,
        });

        if (!item) {
            return;
        }

        await processor.enqueue(item);
        syncResetFields(form);
        pendingSubmissions.delete(form);
        notify('success', translations.savedOffline);
    };

    const resolveFormFromEvent = (event) => {
        const element = event.detail?.elt instanceof Element ? event.detail.elt : event.target;

        if (isFormElement(element)) {
            return element;
        }

        return typeof element?.closest === 'function' ? element.closest('form') : null;
    };

    const handleSubmitCapture = (event) => {
        const form = isFormElement(event.target) ? event.target : null;

        if (!form || hasFileInput(form)) {
            return;
        }

        const queueKey = detectQueueSource(form);

        if (!navigatorObject.onLine) {
            if (queueKey) {
                event.preventDefault();
                event.stopImmediatePropagation();
                void queueForm(form);
                return;
            }

            if (isMutatingHtmxForm(form)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                notify('error', translations.onlineOnly);
            }

            return;
        }

        if (queueKey) {
            const item = createQueueItemFromForm(form, {
                documentObject,
                locationObject: windowObject.location,
            });

            if (item) {
                pendingSubmissions.set(form, item);
            }
        }
    };

    const rememberAllowlistedRequest = (event) => {
        const form = resolveFormFromEvent(event);

        if (!form || !detectQueueSource(form)) {
            return;
        }

        const item = createQueueItemFromForm(form, {
            documentObject,
            locationObject: windowObject.location,
        });

        if (item) {
            pendingSubmissions.set(form, item);
        }
    };

    const handleSendError = (event) => {
        const form = resolveFormFromEvent(event);

        if (!form || !detectQueueSource(form)) {
            return;
        }

        const pendingItem = pendingSubmissions.get(form);

        if (!pendingItem) {
            return;
        }

        if (!navigatorObject.onLine || event.detail?.xhr?.status === 0) {
            void queueForm(form);
        }
    };

    const handleVisibilityReplay = () => {
        if (documentObject.visibilityState === 'visible' && navigatorObject.onLine) {
            void processor.replayPending('visibilitychange');
        }
    };

    const handleServiceWorkerMessage = (event) => {
        if (event.data?.type === 'OFFLINE_QUEUE_SYNCED') {
            notify('success', translations.synced);
            return;
        }

        if (event.data?.type === 'OFFLINE_QUEUE_FAILED_CHANGED') {
            dispatchFailedItemsChanged();

            if (event.data?.message) {
                notify('error', event.data.message);
            }
        }
    };

    return {
        initialize() {
            documentObject.addEventListener('submit', handleSubmitCapture, true);
            documentObject.addEventListener('htmx:beforeRequest', rememberAllowlistedRequest);
            documentObject.addEventListener('htmx:sendError', handleSendError);
            windowObject.addEventListener('online', () => {
                void processor.replayPending('online');
            });
            documentObject.addEventListener('visibilitychange', handleVisibilityReplay);
            navigatorObject.serviceWorker?.addEventListener('message', handleServiceWorkerMessage);

            if (navigatorObject.onLine) {
                void processor.replayPending('startup');
            }
        },

        failedItemsPanel() {
            return {
                items: [],
                loading: true,
                async refresh() {
                    this.loading = true;
                    this.items = await processor.listFailedItems();
                    this.loading = false;
                },
                init() {
                    void this.refresh();
                    windowObject.addEventListener(failedItemsChangedEvent, () => {
                        void this.refresh();
                    });
                },
                async discard(id) {
                    await processor.discardFailedItem(id);
                    await this.refresh();
                },
                formatTimestamp(value) {
                    if (!value) {
                        return '';
                    }

                    return new Date(value).toLocaleString(documentObject.documentElement.lang || navigatorObject.language || 'en');
                },
            };
        },
    };
}
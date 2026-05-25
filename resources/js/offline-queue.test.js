import assert from 'node:assert/strict';
import test from 'node:test';
import {
    OFFLINE_QUEUE_SYNC_TAG,
    createMemoryQueueStore,
    createQueueProcessor,
    resolveQueueTranslations,
} from './offline-queue.js';

const translations = resolveQueueTranslations('en');

function baseQueueItem(overrides = {}) {
    return {
        id: overrides.id || 'queue-item-1',
        queueKey: 'eggs',
        sourceLabel: 'Egg entry',
        method: 'POST',
        url: 'https://example.test/app/eggs',
        headers: {
            'HX-Request': 'true',
            'X-Requested-With': 'XMLHttpRequest',
        },
        fields: [
            ['_token', 'stale-token'],
            ['date', '2026-04-26'],
            ['count', '12'],
        ],
        csrfToken: 'stale-token',
        createdAt: '2026-04-26T12:00:00.000Z',
        attemptCount: 0,
        lastError: null,
        failedAt: null,
        lockedBy: null,
        lockExpiresAt: null,
        ...overrides,
    };
}

test('queue processor enqueues items and registers the sync tag', async () => {
    const store = createMemoryQueueStore();
    const syncTags = [];
    const processor = createQueueProcessor({
        store,
        fetchImpl: async () => new Response('<tr></tr>', {
            status: 200,
            headers: { 'Content-Type': 'text/html' },
        }),
        refreshCsrfToken: async () => ({ token: 'fresh-token' }),
        registerSync: async (tag) => {
            syncTags.push(tag);
        },
        translations,
    });

    await processor.enqueue(baseQueueItem());

    assert.deepEqual(syncTags, [OFFLINE_QUEUE_SYNC_TAG]);
    assert.equal((await store.listQueued()).length, 1);
});

test('queue processor retries once after a 419 response and then completes the item', async () => {
    const store = createMemoryQueueStore([baseQueueItem()]);
    let fetchCount = 0;
    const processor = createQueueProcessor({
        store,
        fetchImpl: async () => {
            fetchCount += 1;

            if (fetchCount === 1) {
                return new Response('expired', {
                    status: 419,
                    headers: { 'Content-Type': 'text/html' },
                });
            }

            return new Response('<tr id="egg-entry-1"></tr>', {
                status: 200,
                headers: { 'Content-Type': 'text/html' },
            });
        },
        refreshCsrfToken: async () => ({ token: `fresh-token-${fetchCount}` }),
        translations,
    });

    await processor.replayPending('node-test');

    assert.equal(fetchCount, 2);
    assert.equal((await store.listQueued()).length, 0);
    assert.equal((await store.listFailed()).length, 0);
});

test('queue processor deduplicates concurrent replay triggers', async () => {
    const store = createMemoryQueueStore([baseQueueItem()]);
    let fetchCount = 0;
    const processor = createQueueProcessor({
        store,
        fetchImpl: async () => {
            fetchCount += 1;

            return new Response('<tr id="egg-entry-1"></tr>', {
                status: 200,
                headers: { 'Content-Type': 'text/html' },
            });
        },
        refreshCsrfToken: async () => ({ token: 'fresh-token' }),
        translations,
    });

    await Promise.all([
        processor.replayPending('online'),
        processor.replayPending('visibilitychange'),
    ]);

    assert.equal(fetchCount, 1);
    assert.equal((await store.listQueued()).length, 0);
});

test('queue processor moves non-retryable responses into the failed bucket', async () => {
    const store = createMemoryQueueStore([baseQueueItem()]);
    const processor = createQueueProcessor({
        store,
        fetchImpl: async () => new Response('Forbidden', {
            status: 403,
            headers: { 'Content-Type': 'text/plain' },
        }),
        refreshCsrfToken: async () => ({ token: 'fresh-token' }),
        translations,
    });

    await processor.replayPending('node-test');

    const failedItems = await store.listFailed();

    assert.equal((await store.listQueued()).length, 0);
    assert.equal(failedItems.length, 1);
    assert.equal(failedItems[0].lastError, 'HTTP 403');
});
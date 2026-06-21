import htmx from 'htmx.org';
import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import Chart from 'chart.js/auto';
import { createOfflineQueueManager } from './offline-queue.js';
import './viability-calculator.js';

window.htmx = htmx;
// Crossfade/morph between pages on boosted navigation instead of a hard cut,
// using the browser's View Transitions API (no-op in browsers without support).
htmx.config.globalViewTransitions = true;
window.Alpine = Alpine;
window.Chart = Chart;
window.ChickenCare = window.ChickenCare || {};
window.ChickenCare.offlineQueue = createOfflineQueueManager();

// Select the whole value of numeric "value" inputs on focus and click so a
// pre-filled number (e.g. 0 or 0.30) can be overwritten by just typing.
// Delegated on document so htmx-swapped and Alpine-rendered inputs are covered.
(function registerValueInputSelectAll() {
    const isValueInput = (el) =>
        el instanceof HTMLInputElement &&
        (el.type === 'number' || el.inputMode === 'numeric' || el.inputMode === 'decimal');

    const selectAll = (event) => {
        if (isValueInput(event.target)) {
            event.target.select();
        }
    };

    document.addEventListener('focusin', selectAll);
    document.addEventListener('click', selectAll);
})();

window.ChickenCare.pwa = (() => {
    const installDismissedKey = 'chickencare:pwa-install-dismissed';
    const state = {
        visible: false,
        mode: null,
        title: '',
        message: '',
        actionLabel: '',
    };

    let deferredPrompt = null;
    let waitingWorker = null;
    let reloadPending = false;

    function bannerTranslations() {
        const banner = document.querySelector('.pwa-banner');

        return {
            installTitle: banner?.dataset.installTitle || 'Install ChickenCare',
            installMessage: banner?.dataset.installMessage || 'Add ChickenCare to your home screen for quicker access.',
            installAction: banner?.dataset.installAction || 'Install',
            updateTitle: banner?.dataset.updateTitle || 'Update available',
            updateMessage: banner?.dataset.updateMessage || 'Reload to use the latest ChickenCare app shell.',
            updateAction: banner?.dataset.updateAction || 'Reload',
        };
    }

    function syncBanner(nextState = {}) {
        Object.assign(state, nextState);

        window.dispatchEvent(new CustomEvent('pwa:state-changed', {
            detail: { ...state },
        }));
    }

    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function isInstallDismissed() {
        return window.sessionStorage.getItem(installDismissedKey) === '1';
    }

    function showInstallPrompt() {
        if (!deferredPrompt || isStandalone() || isInstallDismissed() || state.mode === 'update') {
            return;
        }

        const translations = bannerTranslations();

        syncBanner({
            visible: true,
            mode: 'install',
            title: translations.installTitle,
            message: translations.installMessage,
            actionLabel: translations.installAction,
        });
    }

    function setWaitingWorker(serviceWorker) {
        waitingWorker = serviceWorker;

        if (!waitingWorker) {
            return;
        }

        waitingWorker.addEventListener('statechange', () => {
            if (waitingWorker?.state === 'redundant') {
                waitingWorker = null;
            }
        });
    }

    function showUpdatePrompt(serviceWorker = waitingWorker) {
        setWaitingWorker(serviceWorker);

        if (!waitingWorker) {
            return;
        }

        const translations = bannerTranslations();

        syncBanner({
            visible: true,
            mode: 'update',
            title: translations.updateTitle,
            message: translations.updateMessage,
            actionLabel: translations.updateAction,
        });
    }

    function dismiss() {
        if (state.mode === 'install') {
            window.sessionStorage.setItem(installDismissedKey, '1');
        }

        syncBanner({
            visible: false,
            mode: null,
        });
    }

    async function promptInstall() {
        if (!deferredPrompt) {
            return;
        }

        try {
            await deferredPrompt.prompt();
            await deferredPrompt.userChoice;
        } catch (error) {
            console.warn('ChickenCare PWA install prompt failed.', error);
        } finally {
            deferredPrompt = null;
            dismiss();
        }
    }

    function reloadForUpdate() {
        if (!waitingWorker) {
            window.location.reload();
            return;
        }

        if (reloadPending) {
            return;
        }

        reloadPending = true;

        const reload = () => {
            window.location.reload();
        };

        navigator.serviceWorker.addEventListener('controllerchange', reload, { once: true });
        waitingWorker.postMessage({ type: 'SKIP_WAITING' });

        window.setTimeout(() => {
            if (reloadPending) {
                reload();
            }
        }, 2000);
    }

    function isRegistrationContextSupported() {
        const localHosts = ['localhost', '127.0.0.1', '[::1]'];

        return window.location.protocol === 'https:' || localHosts.includes(window.location.hostname);
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || !isRegistrationContextSupported()) {
            return;
        }

        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');

                if (registration.waiting) {
                    showUpdatePrompt(registration.waiting);
                }

                registration.addEventListener('updatefound', () => {
                    const installingWorker = registration.installing;

                    if (!installingWorker) {
                        return;
                    }

                    installingWorker.addEventListener('statechange', () => {
                        if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            showUpdatePrompt(registration.waiting || installingWorker);
                        }
                    });
                });
            } catch (error) {
                console.warn('ChickenCare service worker registration failed.', error);
            }
        }, { once: true });
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        showInstallPrompt();
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        dismiss();
    });

    return {
        state,
        initialize() {
            registerServiceWorker();
        },
        banner() {
            return {
                visible: state.visible,
                mode: state.mode,
                title: state.title,
                message: state.message,
                actionLabel: state.actionLabel,
                init() {
                    const sync = (detail = state) => {
                        this.visible = detail.visible;
                        this.mode = detail.mode;
                        this.title = detail.title;
                        this.message = detail.message;
                        this.actionLabel = detail.actionLabel;
                    };

                    sync();

                    window.addEventListener('pwa:state-changed', (event) => {
                        sync(event.detail);
                    });
                },
                dismiss() {
                    window.ChickenCare.pwa.dismiss();
                },
                act() {
                    if (this.mode === 'install') {
                        void window.ChickenCare.pwa.promptInstall();
                        return;
                    }

                    if (this.mode === 'update') {
                        window.ChickenCare.pwa.reloadForUpdate();
                    }
                },
            };
        },
        dismiss,
        promptInstall,
        reloadForUpdate,
    };
})();

window.ChickenCare.htmx = {
    extractErrors(xhr) {
        if (!xhr) {
            return ['An unexpected error occurred.'];
        }

        const contentType = xhr.getResponseHeader?.('Content-Type') ?? '';

        if (contentType.includes('application/json')) {
            try {
                const payload = JSON.parse(xhr.responseText || '{}');
                const validationErrors = Object.values(payload.errors ?? {}).flat().filter(Boolean);

                if (validationErrors.length > 0) {
                    return validationErrors;
                }

                if (typeof payload.message === 'string' && payload.message.trim() !== '') {
                    return [payload.message];
                }
            } catch {
                // Fall back to the generic message below.
            }
        }

        return ['An unexpected error occurred.'];
    },

    resetForm(event) {
        const form = event.detail?.elt instanceof HTMLFormElement
            ? event.detail.elt
            : event.target instanceof HTMLFormElement
                ? event.target
                : null;

        form?.reset();
    },
};

window.ChickenCare.skeletons = (() => {
    const SKELETON_ROUTES = {
        '/app': '/app/__skeleton',
        '/app/': '/app/__skeleton',
        '/app/eggs': '/app/eggs/__skeleton',
        '/app/account': '/app/account/__skeleton',
    };

    // Only show the loading skeleton if a boosted navigation is still pending after
    // this delay. Fast/warmed pages resolve first and swap with a clean view
    // transition (no placeholder flash); genuinely slow pages still get a skeleton.
    const SHOW_DELAY_MS = 200;
    let pendingTimer = null;

    function clearPending() {
        if (pendingTimer !== null) {
            window.clearTimeout(pendingTimer);
            pendingTimer = null;
        }
    }

    function templateFor(variant) {
        return document.getElementById(`skeleton-template-${variant}`);
    }

    function resolveTarget(event) {
        if (event.detail?.requestConfig?.boosted) {
            return document.querySelector('.app-layout__content[data-loading-skeleton]');
        }

        return event.detail?.target instanceof HTMLElement ? event.detail.target : null;
    }

    function destinationPath(event) {
        const path = event.detail?.requestConfig?.path || event.detail?.pathInfo?.requestPath;

        if (!path) {
            return null;
        }

        try {
            return new URL(path, window.location.origin).pathname;
        } catch {
            return null;
        }
    }

    function buildHostFromHtml(html) {
        const host = document.createElement('div');
        host.className = 'skeleton-loader-host';
        host.dataset.skeletonHost = 'true';
        host.setAttribute('aria-hidden', 'true');

        const doc = new DOMParser().parseFromString(html, 'text/html');
        const content = doc.querySelector('.app-layout__content') || doc.body;

        host.append(...Array.from(content.childNodes));

        return host;
    }

    function buildHostFromTemplate(variant) {
        const template = templateFor(variant);

        if (!(template instanceof HTMLTemplateElement)) {
            return null;
        }

        const host = document.createElement('div');
        host.className = 'skeleton-loader-host';
        host.dataset.skeletonHost = 'true';
        host.setAttribute('aria-hidden', 'true');
        host.append(template.content.cloneNode(true));

        return host;
    }

    async function fetchSkeleton(skeletonUrl) {
        const cacheKey = `chickencare:skel:${skeletonUrl}`;
        const cached = sessionStorage.getItem(cacheKey);

        if (cached) {
            return cached;
        }

        const response = await fetch(skeletonUrl, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            return null;
        }

        const html = await response.text();
        try { sessionStorage.setItem(cacheKey, html); } catch {}
        return html;
    }

    function mount(target, host) {
        target.classList.add('is-loading-skeleton');
        target.setAttribute('aria-busy', 'true');
        target.append(host);
    }

    function render(event) {
        const target = resolveTarget(event);
        const variant = target?.dataset.loadingSkeleton;

        if (!target || !variant || target.querySelector('[data-skeleton-host="true"]')) {
            return;
        }

        const skeletonUrl = event.detail?.requestConfig?.boosted
            ? SKELETON_ROUTES[destinationPath(event)]
            : null;

        if (skeletonUrl) {
            const cached = sessionStorage.getItem(`chickencare:skel:${skeletonUrl}`);

            if (cached) {
                mount(target, buildHostFromHtml(cached));
                return;
            }

            const fallback = buildHostFromTemplate(variant);
            if (fallback) mount(target, fallback);

            fetchSkeleton(skeletonUrl).catch(() => null);
            return;
        }

        const fallback = buildHostFromTemplate(variant);
        if (fallback) mount(target, fallback);
    }

    function show(event) {
        // Inline partial requests (forms, pagination) keep showing their skeleton
        // immediately. Boosted page navigations defer it past SHOW_DELAY_MS so fast
        // swaps stay flash-free and only slow ones reveal a skeleton.
        if (!event.detail?.requestConfig?.boosted) {
            render(event);
            return;
        }

        clearPending();
        pendingTimer = window.setTimeout(() => {
            pendingTimer = null;
            render(event);
        }, SHOW_DELAY_MS);
    }

    function hide(target) {
        if (!(target instanceof HTMLElement)) {
            return;
        }

        target.classList.remove('is-loading-skeleton');
        target.removeAttribute('aria-busy');
        target.querySelector('[data-skeleton-host="true"]')?.remove();
    }

    function hideAll() {
        clearPending();
        document.querySelectorAll('[data-loading-skeleton].is-loading-skeleton').forEach((element) => {
            hide(element);
        });
    }

    function hideFromEvent(event) {
        clearPending();

        if (event.detail?.target instanceof HTMLElement) {
            hide(event.detail.target);
        }

        if (event.detail?.requestConfig?.boosted) {
            hide(document.querySelector('.app-layout__content[data-loading-skeleton]'));
        }
    }

    return {
        show,
        hideAll,
        hideFromEvent,
    };
})();

window.ChickenCare.routeWarmup = (() => {
    const warmedRoutesKey = 'chickencare:warmed-routes';
    let queueStarted = false;

    function canWarmRoutes() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

        if (connection?.saveData) {
            return false;
        }

        return !['slow-2g', '2g'].includes(connection?.effectiveType || '');
    }

    function warmedRoutes() {
        try {
            return new Set(JSON.parse(sessionStorage.getItem(warmedRoutesKey) || '[]'));
        } catch {
            return new Set();
        }
    }

    function markRouteWarmed(pathname) {
        const warmed = warmedRoutes();
        warmed.add(pathname);
        sessionStorage.setItem(warmedRoutesKey, JSON.stringify(Array.from(warmed)));
    }

    function normalizePath(route) {
        try {
            return new URL(route, window.location.origin).pathname;
        } catch {
            return null;
        }
    }

    function warmRoutesFromBody() {
        if (queueStarted || !canWarmRoutes()) {
            return;
        }

        const routesJson = document.body?.dataset?.warmRoutes;
        if (!routesJson) {
            return;
        }

        let routes;

        try {
            routes = JSON.parse(routesJson);
        } catch {
            return;
        }

        const currentPath = window.location.pathname;
        const warmed = warmedRoutes();
        const queue = routes
            .map(normalizePath)
            .filter((pathname) => pathname && pathname !== currentPath && !warmed.has(pathname));

        if (queue.length === 0) {
            return;
        }

        queueStarted = true;

        const run = async () => {
            for (const pathname of queue) {
                try {
                    await fetch(pathname, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'HX-Request': 'true',
                            'HX-Boosted': 'true',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    markRouteWarmed(pathname);
                } catch {
                    // Best-effort warmup only.
                }
            }

            queueStarted = false;
        };

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(() => {
                void run();
            }, { timeout: 1500 });
        } else {
            window.setTimeout(() => {
                void run();
            }, 600);
        }
    }

    return {
        warmRoutesFromBody,
    };
})();

// Intent-based prefetch: on hover / touch-start / focus of a boosted nav link,
// fetch its page in the background so the boosted GET response lands in the
// browser cache (boosted nav responses carry a short private max-age — see
// SetDynamicResponseCacheHeaders). The actual click is then served from cache
// with no server round-trip, which is what makes navigation feel instant.
window.ChickenCare.prefetch = (() => {
    const prefetched = new Map(); // path -> timestamp
    const TTL_MS = 4000; // just under the server max-age=5 so an expired entry re-warms on the next intent

    function canPrefetch() {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;

        if (connection?.saveData) {
            return false;
        }

        return !['slow-2g', '2g'].includes(connection?.effectiveType || '');
    }

    function eligiblePath(eventTarget) {
        const link = eventTarget?.closest?.('a[href]');

        if (!link || link.target === '_blank' || link.hasAttribute('download')) {
            return null;
        }

        if (link.getAttribute('hx-boost') === 'false' || link.closest('[hx-boost="false"]')) {
            return null;
        }

        let url;

        try {
            url = new URL(link.href, window.location.origin);
        } catch {
            return null;
        }

        if (url.origin !== window.location.origin || !url.pathname.startsWith('/app')) {
            return null;
        }

        if (url.pathname === window.location.pathname) {
            return null;
        }

        return url.pathname + url.search;
    }

    function prefetch(path) {
        if (!canPrefetch()) {
            return;
        }

        const now = Date.now();
        const last = prefetched.get(path);

        if (last && now - last < TTL_MS) {
            return;
        }

        prefetched.set(path, now);

        fetch(path, {
            credentials: 'same-origin',
            headers: {
                'HX-Request': 'true',
                'HX-Boosted': 'true',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.arrayBuffer()) // drain so the browser finishes caching
            .catch(() => {
                prefetched.delete(path); // allow a retry on the next intent
            });
    }

    function onIntent(event) {
        const path = eligiblePath(event.target);

        if (path) {
            prefetch(path);
        }
    }

    function initialize() {
        // pointerover bubbles (covers mouse hover); touchstart fires on tap-start
        // before the click; focusin covers keyboard navigation. Delegated on the
        // document so links added by boosted swaps are covered automatically.
        document.addEventListener('pointerover', onIntent, { passive: true });
        document.addEventListener('touchstart', onIntent, { passive: true });
        document.addEventListener('focusin', onIntent);
    }

    return { initialize, prefetch };
})();

Alpine.plugin(intersect);

window.ChickenCare.pwa.initialize();
window.ChickenCare.offlineQueue.initialize();

window.ChickenCare.routeWarmup.warmRoutesFromBody();
window.ChickenCare.prefetch.initialize();

document.getElementById('fp-skeleton')?.remove();

if ('requestIdleCallback' in window) {
    const prewarmSkeletons = ['/app/__skeleton', '/app/eggs/__skeleton', '/app/account/__skeleton'];

    window.requestIdleCallback(() => {
        prewarmSkeletons.forEach((url) => {
            const cacheKey = `chickencare:skel:${url}`;
            if (sessionStorage.getItem(cacheKey)) return;
            fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.ok ? r.text() : null)
                .then(html => { if (html) try { sessionStorage.setItem(cacheKey, html); } catch {} })
                .catch(() => null);
        });
    }, { timeout: 2000 });
}

const htmxEventTarget = document;

// Handle 422 validation errors in HTMX
htmxEventTarget.addEventListener('htmx:beforeSwap', function(evt) {
    if (evt.detail.xhr.status === 422) {
        evt.detail.shouldSwap = true;
        evt.detail.isError = false;
    }

    // Destroy Chart.js instances inside content about to be removed, so their
    // ResizeObservers don't fire on detached canvases (TypeError: ...ownerDocument).
    const target = evt.detail.target;
    if (target && window.Chart) {
        target.querySelectorAll('canvas').forEach(function(canvas) {
            window.Chart.getChart(canvas)?.destroy();
        });
    }
});

htmxEventTarget.addEventListener('htmx:beforeRequest', function(evt) {
    window.ChickenCare.skeletons.show(evt);
});

// Close modal when server sends closeModal trigger
htmxEventTarget.addEventListener('closeModal', function() {
    document.body.style.overflow = '';
    document.getElementById('modal-container').innerHTML = '';
});

// Handle session expiry
htmxEventTarget.addEventListener('htmx:responseError', function(evt) {
    window.ChickenCare.skeletons.hideAll();

    if (evt.detail.xhr.status === 419) {
        window.location.href = '/login';
    }
});

htmxEventTarget.addEventListener('htmx:afterSwap', function(evt) {
    window.ChickenCare.skeletons.hideFromEvent(evt);
});

htmxEventTarget.addEventListener('htmx:sendError', function() {
    window.ChickenCare.skeletons.hideAll();
});

htmxEventTarget.addEventListener('htmx:afterSettle', function(evt) {
    if (evt.detail.requestConfig?.boosted) {
        window.ChickenCare.routeWarmup.warmRoutesFromBody();
    }

    const target = evt.detail.target;
    if (target) {
        target.querySelectorAll('script').forEach(function(oldScript) {
            const newScript = document.createElement('script');
            newScript.textContent = oldScript.textContent;
            oldScript.replaceWith(newScript);
        });
    }
});

Alpine.store('viewport', {
    isMobile: window.matchMedia('(max-width: 767px)').matches,
    init() {
        const mql = window.matchMedia('(max-width: 767px)');
        const handler = (e) => { this.isMobile = e.matches; };
        if (mql.addEventListener) {
            mql.addEventListener('change', handler);
        } else {
            mql.addListener(handler);
        }
    },
});

Alpine.data('featureCarousel', (totalImages = 1) => ({
    currentIndex: 0,
    slideDirection: 1,
    touchStartX: 0,
    touchEndX: 0,
    touchStartTime: 0,
    isDragging: false,
    total: Number(totalImages) || 1,

    get counterText() {
        return `${this.currentIndex + 1}/${this.total}`;
    },

    next() {
        this.slideDirection = 1;
        this.currentIndex = (this.currentIndex + 1) % this.total;
    },

    prev() {
        this.slideDirection = -1;
        this.currentIndex = (this.currentIndex - 1 + this.total) % this.total;
    },

    goTo(index) {
        this.slideDirection = index > this.currentIndex ? 1 : -1;
        this.currentIndex = index;
    },

    handleTouchStart(event) {
        this.touchStartX = event.touches[0].clientX;
        this.touchEndX = this.touchStartX;
        this.touchStartTime = performance.now();
        this.isDragging = true;
    },

    handleTouchMove(event) {
        this.touchEndX = event.touches[0].clientX;
    },

    handleTouchEnd(event) {
        if (!this.isDragging) {
            return;
        }

        this.touchEndX = event.changedTouches[0].clientX;
        const horizontalDistance = this.touchStartX - this.touchEndX;
        const elapsedTime = Math.max((performance.now() - this.touchStartTime) / 1000, 0.001);
        const velocity = Math.abs(horizontalDistance / elapsedTime);

        if (Math.abs(horizontalDistance) > 50 || velocity > 300) {
            if (horizontalDistance > 0) {
                this.next();
            } else {
                this.prev();
            }
        }

        window.setTimeout(() => {
            this.isDragging = false;
        }, 100);
    },

    // Backward-compatible aliases for existing templates.
    touchStart(event) {
        this.handleTouchStart(event);
    },

    touchEnd(event) {
        this.handleTouchEnd(event);
    },

    openFullscreen(detail) {
        if (this.isDragging) {
            return;
        }

        this.$dispatch('open-fullscreen', detail);
    },
}));

Alpine.data('fullscreenModal', () => ({
    open: false,
    src: '',
    alt: '',
    title: '',

    openModal(detail) {
        this.src = detail.src;
        this.alt = detail.alt || '';
        this.title = detail.title || detail.alt || '';
        this.open = true;

        this.$nextTick(() => {
            this.$refs.closeButton?.focus();
        });

        document.body.style.overflow = 'hidden';
    },

    trapFocus(event) {
        if (!this.open) {
            return;
        }

        event.preventDefault();
        this.$refs.closeButton?.focus();
    },

    close() {
        this.open = false;
        document.body.style.overflow = '';
    },
}));

Alpine.start();

window.flockModal = function flockModal() {
    return {
        firstFocusable: null,
        lastFocusable:  null,
        open() {
            this.$nextTick(() => {
                const focusable = this.$refs.panel.querySelectorAll(
                    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                this.firstFocusable = focusable[0];
                this.lastFocusable  = focusable[focusable.length - 1];
                this.firstFocusable?.focus();
                document.body.style.overflow = 'hidden';
            });
        },
        trapFocus(event) {
            if (event.key !== 'Tab') { return; }
            if (event.shiftKey) {
                if (document.activeElement === this.firstFocusable) {
                    event.preventDefault();
                    this.lastFocusable?.focus();
                }
            } else {
                if (document.activeElement === this.lastFocusable) {
                    event.preventDefault();
                    this.firstFocusable?.focus();
                }
            }
        },
        close() {
            document.body.style.overflow = '';
            document.getElementById('modal-container').innerHTML = '';
        },
    };
};

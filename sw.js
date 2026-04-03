/**
 * OpenShelf Service Worker
 * Provides offline support and smart caching for the PWA
 * 
 * Strategy:
 * - Pre-cache: Core app shell (CSS, JS, logos, fonts)
 * - Pages: Network-First with offline fallback
 * - Static Assets: Cache-First with versioned cache
 * - Images: Cache-First with size limit
 * - API/Data: Network-Only (never cache dynamic data)
 */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE = `openshelf-static-${CACHE_VERSION}`;
const PAGES_CACHE = `openshelf-pages-${CACHE_VERSION}`;
const IMAGES_CACHE = `openshelf-images-${CACHE_VERSION}`;

// Core assets to pre-cache during install
const PRE_CACHE_ASSETS = [
    '/',
    '/offline.php',
    '/assets/css/style.css',
    '/assets/images/logo-icon.svg',
    '/assets/images/logo-full.svg',
    '/assets/images/default-book-cover.jpg',
    '/manifest.json'
];

// External resources to pre-cache
const PRE_CACHE_EXTERNAL = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
    'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap'
];

// Max entries in the images cache
const MAX_IMAGE_CACHE_ENTRIES = 100;

// ==========================================
// INSTALL EVENT
// Pre-cache core assets
// ==========================================
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Pre-caching core assets');
                // Cache local assets (fail gracefully if any are missing)
                const localPromise = Promise.allSettled(
                    PRE_CACHE_ASSETS.map(url => 
                        cache.add(url).catch(err => {
                            console.warn(`[SW] Failed to pre-cache: ${url}`, err);
                        })
                    )
                );
                // Cache external assets (fail gracefully)
                const externalPromise = Promise.allSettled(
                    PRE_CACHE_EXTERNAL.map(url =>
                        cache.add(url).catch(err => {
                            console.warn(`[SW] Failed to pre-cache external: ${url}`, err);
                        })
                    )
                );
                return Promise.all([localPromise, externalPromise]);
            })
            .then(() => {
                console.log('[SW] Pre-caching complete');
                return self.skipWaiting(); // Activate immediately
            })
    );
});

// ==========================================
// ACTIVATE EVENT
// Clean up old caches
// ==========================================
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            // Delete caches that don't match current version
                            return name.startsWith('openshelf-') && 
                                   name !== STATIC_CACHE && 
                                   name !== PAGES_CACHE && 
                                   name !== IMAGES_CACHE;
                        })
                        .map((name) => {
                            console.log(`[SW] Deleting old cache: ${name}`);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Activation complete');
                return self.clients.claim(); // Take control of all pages
            })
    );
});

// ==========================================
// FETCH EVENT
// Smart caching strategies per resource type
// ==========================================
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests (POST forms, etc.)
    if (request.method !== 'GET') return;
    
    // Skip admin API and data endpoints — never cache
    if (url.pathname.startsWith('/api/') || 
        url.pathname.startsWith('/data/') ||
        url.pathname.startsWith('/admin/')) {
        return;
    }
    
    // Skip chrome-extension and other non-http(s) schemes
    if (!url.protocol.startsWith('http')) return;

    // Determine strategy based on resource type
    if (isStaticAsset(url)) {
        // Static assets: Cache-First
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    } else if (isImage(url)) {
        // Images: Cache-First with limit
        event.respondWith(cacheFirstWithLimit(request, IMAGES_CACHE, MAX_IMAGE_CACHE_ENTRIES));
    } else if (isExternalResource(url)) {
        // External CDN resources: Cache-First
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    } else if (isPage(request)) {
        // HTML pages: Network-First with offline fallback
        event.respondWith(networkFirstWithFallback(request));
    }
});

// ==========================================
// CACHING STRATEGIES
// ==========================================

/**
 * Cache-First: Try cache, fall back to network
 * Best for: Static assets that rarely change
 */
async function cacheFirst(request, cacheName) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.warn(`[SW] Cache-first failed for: ${request.url}`, error);
        return new Response('Resource not available offline', { 
            status: 503, 
            statusText: 'Service Unavailable' 
        });
    }
}

/**
 * Cache-First with entry limit: Prevents image cache bloat
 * Best for: User-uploaded images, book covers
 */
async function cacheFirstWithLimit(request, cacheName, maxEntries) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            
            // Trim cache if over limit
            const keys = await cache.keys();
            if (keys.length >= maxEntries) {
                // Delete oldest 20% of entries
                const deleteCount = Math.ceil(maxEntries * 0.2);
                for (let i = 0; i < deleteCount && i < keys.length; i++) {
                    await cache.delete(keys[i]);
                }
            }
            
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.warn(`[SW] Image cache-first failed for: ${request.url}`, error);
        return new Response('', { status: 404 });
    }
}

/**
 * Network-First with offline fallback
 * Best for: HTML pages — always try fresh content first
 */
async function networkFirstWithFallback(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            // Cache successful page responses
            const cache = await caches.open(PAGES_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        // Network failed — try cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // No cache either — serve offline page
        const offlinePage = await caches.match('/offline.php');
        if (offlinePage) {
            return offlinePage;
        }
        
        // Last resort: basic offline response
        return new Response(
            '<html><body><h1>You are offline</h1><p>Please check your internet connection and try again.</p></body></html>',
            { headers: { 'Content-Type': 'text/html' }, status: 503 }
        );
    }
}

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.svg', '.woff', '.woff2', '.ttf', '.eot'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

function isImage(url) {
    const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.ico'];
    return imageExtensions.some(ext => url.pathname.endsWith(ext));
}

function isExternalResource(url) {
    const cdnDomains = [
        'cdnjs.cloudflare.com',
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'cdn.jsdelivr.net'
    ];
    return cdnDomains.some(domain => url.hostname === domain);
}

function isPage(request) {
    return request.headers.get('Accept')?.includes('text/html') || false;
}

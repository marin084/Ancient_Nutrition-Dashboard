const CACHE_NAME = 'ancient-dashboard-v1';
const ASSETS_TO_CACHE = [
    './css/style.css',
    './manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// Install Event: Cache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

// Activate Event: Clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch Event: Network First for PHP/HTML, Cache First for CSS/Images
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Strategy: Cache First for static assets (CSS, Images, Fonts)
    if (url.pathname.endsWith('.css') || 
        url.pathname.endsWith('.png') || 
        url.pathname.endsWith('.jpg') || 
        url.pathname.endsWith('.ico') || 
        url.hostname.includes('fonts') ||
        url.hostname.includes('cdnjs')) {
        
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                return cachedResponse || fetch(event.request);
            })
        );
        return;
    }

    // Strategy: Network Only for mostly everything else (PHP pages, API)
    // We want fresh data always for the dashboard.
    // If we wanted offline fallback for the dashboard structure, we'd use Network First, 
    // but for now, simple Network Only ensures no stale order data.
    event.respondWith(fetch(event.request));
});

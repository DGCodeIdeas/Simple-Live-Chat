const CACHE_NAME = 'chatreactor-v1';
const ASSETS = [
    '/',
    '/index.php',
    '/assets/css/style.css',
    '/assets/js/app.js',
    'https://code.jquery.com/jquery-3.7.0.min.js'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});

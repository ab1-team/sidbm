const CACHE_NAME = 'my-pwa-cache-v1';

self.addEventListener('install', function (event) {
    event.waitUntil(
        fetch('/cache-files')
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Gagal mengambil cache-files');
            }
            return response.json();
        })
        .then(function (files) {
            var validUrls = (Array.isArray(files) ? files : []).filter(function (f) {
                return typeof f === 'string' &&
                       (f.startsWith('/') || f.startsWith('http://') || f.startsWith('https://')) &&
                       !f.startsWith('data:');
            });
            return caches.open(CACHE_NAME).then(function (cache) {
                return cache.addAll(validUrls);
            });
        })
        .catch(function (error) {
            console.warn('Service worker cache.addAll dilewati atau gagal:', error);
        })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

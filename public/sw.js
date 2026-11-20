const CACHE_NAME = 'clockverse-v3';
const STATIC_ASSETS = [
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  // Never intercept HTML page navigation requests (e.g. /dashboard, /admin/employees)
  // This guarantees authentication, session, Livewire state, and CSRF tokens remain fresh
  if (event.request.mode === 'navigate') return;

  const url = new URL(event.request.url);

  // Only handle same-origin static requests
  if (url.origin !== self.location.origin) return;

  // Never intercept dynamic endpoints, API, Livewire, or uploaded storage assets
  if (url.pathname.includes('/livewire/') ||
      url.pathname.startsWith('/storage/') ||
      url.pathname.startsWith('/admin/') ||
      url.pathname.startsWith('/reports/') ||
      url.pathname === '/login' ||
      url.pathname === '/logout') {
    return;
  }

  // Cache-first for known static icons and manifest; network-only for everything else
  if (url.pathname.startsWith('/icons/') || url.pathname === '/manifest.json') {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            const clone = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return networkResponse;
        });
      }).catch(() => {
        return new Response('', { status: 404, statusText: 'Not Found' });
      })
    );
  }
});

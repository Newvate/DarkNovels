const CACHE_NAME = 'darknovels-v7';
const BASE_PATH = new URL(self.registration.scope).pathname.replace(/\/$/, '');
const ASSETS = [
  `${BASE_PATH}/assets/img/darknovels-hero.png`,
  `${BASE_PATH}/assets/css/style.css`,
  `${BASE_PATH}/assets/js/app.js`
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const isDynamicPage = event.request.mode === 'navigate' || url.pathname.endsWith('.php');

  if (isDynamicPage) {
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
    return;
  }

  event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request)));
});

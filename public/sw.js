/**
 * TEXTUM — Service Worker
 * Estrategia: Cache-First para assets estáticos, Network-First para páginas PHP
 */
'use strict';

const STATIC_CACHE  = 'textum-static-v3';
const PAGES_CACHE   = 'textum-pages-v3';

const STATIC_ASSETS = [
  './css/app.css',
  './js/app.js',
  './offline.php',
];

// ── Install: pre-cachear assets estáticos ──────────────────────
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// ── Activate: limpiar caches viejos ───────────────────────────
self.addEventListener('activate', event => {
  const allowed = [STATIC_CACHE, PAGES_CACHE];
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(k => !allowed.includes(k)).map(k => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

// ── Fetch ──────────────────────────────────────────────────────
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Solo manejar peticiones del mismo origen
  if (url.origin !== location.origin) return;

  // Assets estáticos → Cache-First
  if (/\.(css|js|png|jpg|jpeg|svg|woff2?|ico)$/.test(url.pathname)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Páginas PHP / navegación → Network-First con fallback offline
  if (request.mode === 'navigate' || request.headers.get('Accept')?.includes('text/html')) {
    event.respondWith(networkFirstWithOffline(request));
    return;
  }
});

// ── Helpers ────────────────────────────────────────────────────

async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 503 });
  }
}

async function networkFirstWithOffline(request) {
  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(PAGES_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    return caches.match('./offline.php');
  }
}

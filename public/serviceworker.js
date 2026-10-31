// Naikkan versi (v2, v3, ...) setiap kali file ini diubah
const CACHE = 'koombaah-v1';
const OFFLINE_URL = '/offline';

const PRECACHE = [
    OFFLINE_URL,
    '/images/icons/icon-192x192.png',
    '/images/icons/icon-512x512.png',
];

// Simpan halaman offline + ikon saat service worker dipasang
self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(PRECACHE)));
    self.skipWaiting();
});

// Hapus cache versi lama
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);

    // Jangan sentuh: POST (form/Livewire), domain lain (Vite dev, font), request Livewire
    if (req.method !== 'GET') return;
    if (url.origin !== self.location.origin) return;
    if (url.pathname.startsWith('/livewire')) return;

    // Halaman: selalu ambil dari server, kalau gagal -> halaman offline
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Aset hasil build (nama file ber-hash) & ikon: boleh dari cache
    if (url.pathname.startsWith('/build/') || url.pathname.startsWith('/images/')) {
        event.respondWith(
            caches.match(req).then((cached) =>
                cached ||
                fetch(req).then((res) => {
                    if (res.ok) {
                        const copy = res.clone();
                        caches.open(CACHE).then((cache) => cache.put(req, copy));
                    }
                    return res;
                })
            )
        );
    }
});
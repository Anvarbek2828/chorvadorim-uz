/*
 * Chorvadorim service worker (qo'lda yozilgan, kutubxonasiz).
 *
 * MUHIM: sw.js, pwa.js, /offline sahifasi yoki ikonkalar o'zgarsa, CACHE_VERSION ni oshiring
 * (v1 -> v2 ...). Brauzer yangi sw.js ni ko'radi, foydalanuvchiga "Yangi versiya tayyor" chiqadi,
 * eski keshlar activate bosqichida o'chiriladi.
 *
 * Strategiya:
 *  - Statik fayllar (/build/*, /icons/*, /pwa.js, shriftlar): avval kesh, keyin tarmoq.
 *  - Sahifalar (navigate, GET): avval tarmoq; tarmoq bo'lmasa keshdagi oxirgi nusxa, u ham
 *    bo'lmasa /offline. Keshdan berilgan sahifaga <html data-chv-offline="1"> belgisi qo'shiladi.
 *  - Faqat o'z domeni. GET bo'lmagan so'rovlar va BYPASS ro'yxatidagi yo'llarga aralashilmaydi.
 *  - Sahifa javobidagi <meta name="chv-user"> o'zgarsa (chiqish/boshqa foydalanuvchi), sahifa keshi
 *    butunlay tozalanadi. Kesh nomlarida foydalanuvchi ID yo'q.
 */
'use strict';

const CACHE_VERSION = 'v3';
const STATIC_CACHE = 'chv-static-' + CACHE_VERSION;
const PAGES_CACHE = 'chv-pages-' + CACHE_VERSION;
const META_CACHE = 'chv-meta';
const USER_KEY = '/__chv/user';
const OFFLINE_URL = '/offline';
const MAX_PAGES = 40;

const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/icon.svg'];

// SW umuman aralashmaydigan yo'llar: yo'lning o'zi yoki shu yo'l bilan boshlanadigan bo'lim.
// Kirish/chiqish/parol, admin, cron, Telegram webhook, til almashtirish, fayl yuklash (import),
// yuklangan fayllar, vaqtinchalik parollar ko'rinadigan xodimlar sahifasi.
const BYPASS = [
    '/admin',
    '/login',
    '/logout',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/verify-email',
    '/email',
    '/confirm-password',
    '/password',
    '/parol-almashtirish',
    '/cron',
    '/telegram',
    '/lang',
    '/animals/import',
    '/xodimlar',
    '/storage',
    '/sw.js',
    '/up',
];

function isBypassed(path) {
    return BYPASS.some((p) => path === p || path.startsWith(p + '/'));
}

function isStatic(path) {
    return path.startsWith('/build/')
        || path.startsWith('/icons/')
        || path === '/pwa.js'
        || /\.(woff2?|ttf|otf)$/.test(path);
}

// Sahifa kaliti: ?source=pwa va #... olib tashlanadi
function pageKey(url) {
    const u = new URL(url);
    u.hash = '';
    u.searchParams.delete('source');
    return u.toString();
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) =>
            cache.addAll(PRECACHE.map((url) => new Request(url, { cache: 'reload', credentials: 'same-origin' })))
        )
    );
    // skipWaiting bu yerda chaqirilmaydi: yangi versiya foydalanuvchi "Yangilash" ni bosgandagina ishga tushadi
});

self.addEventListener('activate', (event) => {
    const keep = [STATIC_CACHE, PAGES_CACHE, META_CACHE];
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('chv-') && !keep.includes(k)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    const data = event.data || {};
    if (data.type === 'SKIP_WAITING') {
        self.skipWaiting();
        return;
    }
    if (data.type === 'CLEAR_PAGES') {
        // Navbatga qo'yiladi: oldin boshlangan yozuvlar tugagach tozalanadi
        queue = queue.then(clearPages).catch(() => {});
        event.waitUntil(queue.then(() => {
            if (event.ports && event.ports[0]) {
                event.ports[0].postMessage({ ok: true });
            }
        }));
    }
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Faqat GET; POST/PUT/PATCH/DELETE ga tegilmaydi
    if (req.method !== 'GET') {
        return;
    }

    const url = new URL(req.url);

    // Tashqi domenlar (shriftlar, Telegram va h.k.) — brauzerning o'zi
    if (url.origin !== self.location.origin) {
        return;
    }
    if (isBypassed(url.pathname)) {
        return;
    }

    // JSON / AJAX so'rovlar keshlanmaydi
    const accept = req.headers.get('Accept') || '';
    if (req.headers.get('X-Requested-With') || (accept.includes('application/json') && !accept.includes('text/html'))) {
        return;
    }

    if (req.mode === 'navigate') {
        event.respondWith(networkFirstPage(event));
        return;
    }

    if (isStatic(url.pathname)) {
        event.respondWith(cacheFirst(req));
    }
});

async function cacheFirst(req) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(req);
    if (cached) {
        return cached;
    }
    const response = await fetch(req);
    if (response.ok && response.type === 'basic') {
        cache.put(req, response.clone());
    }
    return response;
}

async function networkFirstPage(event) {
    const req = event.request;
    try {
        const response = await fetch(req);
        const type = response.headers.get('Content-Type') || '';
        const cacheControl = response.headers.get('Cache-Control') || '';
        if (response.status === 200 && response.type === 'basic' && type.includes('text/html') && !cacheControl.includes('no-store')) {
            event.waitUntil(storePage(req.url, response.clone()));
        }
        return response;
    } catch (err) {
        const cached = await matchPage(req.url);
        if (cached) {
            return markOffline(cached);
        }
        const offline = await caches.match(OFFLINE_URL);
        return offline || new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
    }
}

// Sahifa keshiga yozish navbat bilan (foydalanuvchi almashganda poyga bo'lmasin)
let queue = Promise.resolve();

function storePage(url, response) {
    queue = queue.then(() => doStorePage(url, response)).catch(() => {});
    return queue;
}

async function doStorePage(url, response) {
    const html = await response.text();
    const match = html.match(/<meta name="chv-user" content="(\d*)">/);

    // Bizning layoutlardan bo'lmagan sahifalar keshlanmaydi
    if (!match) {
        return;
    }

    await checkUser(match[1]);

    const cache = await caches.open(PAGES_CACHE);
    await cache.put(pageKey(url), new Response(html, {
        status: 200,
        headers: { 'Content-Type': response.headers.get('Content-Type') || 'text/html; charset=UTF-8' },
    }));

    const keys = await cache.keys();
    for (let i = 0; i < keys.length - MAX_PAGES; i++) {
        await cache.delete(keys[i]);
    }
}

async function checkUser(userId) {
    const meta = await caches.open(META_CACHE);
    const saved = await meta.match(USER_KEY);
    const previous = saved ? await saved.text() : null;

    if (previous !== userId) {
        await caches.delete(PAGES_CACHE);
        await meta.put(USER_KEY, new Response(userId));
    }
}

async function clearPages() {
    await caches.delete(PAGES_CACHE);
    const meta = await caches.open(META_CACHE);
    await meta.delete(USER_KEY);
}

async function matchPage(url) {
    const cache = await caches.open(PAGES_CACHE);
    return cache.match(pageKey(url));
}

async function markOffline(response) {
    const html = await response.text();
    return new Response(html.replace('<html', '<html data-chv-offline="1"'), {
        status: 200,
        headers: { 'Content-Type': response.headers.get('Content-Type') || 'text/html; charset=UTF-8' },
    });
}

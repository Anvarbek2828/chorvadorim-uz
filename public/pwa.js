/*
 * Chorvadorim PWA: service worker ro'yxati, yangi versiya xabari, oflayn qator, oflaynda formalarni
 * to'xtatish, chiqishda keshni tozalash, "Ilovani o'rnating" paneli.
 * Matnlar sahifadagi <script id="chv-pwa-config"> dan olinadi (3 til). Eski brauzerlar uchun ES5.
 * O'zgartirilsa sw.js dagi CACHE_VERSION ni oshiring.
 */
(function () {
    'use strict';

    var cfg = {};
    try { cfg = JSON.parse(document.getElementById('chv-pwa-config').textContent) || {}; } catch (e) {}
    var t = cfg.t || {};
    var root = document.documentElement;
    var hasSW = 'serviceWorker' in navigator;
    var PAGE_CACHE_PREFIX = 'chv-pages-';
    var DISMISS_KEY = 'chvInstallDismissedAt';
    var DISMISS_DAYS = 14;

    function read(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
    function write(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }
    function remove(key) { try { localStorage.removeItem(key); } catch (e) {} }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text) { node.textContent = text; }
        return node;
    }

    // Keshdan berilgan sahifa (sw.js qo'yadigan belgi). Undagi CSRF token eskirgan bo'lishi mumkin,
    // shuning uchun bunday sahifadan POST hech qachon yuborilmaydi (419 bo'lmasin).
    var fromCache = root.hasAttribute('data-chv-offline');

    // Foydalanuvchi sahifada biror narsa yozdimi (internet qaytganda qayta yuklash uchun)
    var typed = false;
    document.addEventListener('input', function () { typed = true; }, true);

    // /offline sahifasi shu tilda ko'rsatilsin
    if (!fromCache && cfg.locale) { write('chvLocale', cfg.locale); }

    /* ---------- Sahifa keshini tozalash ---------- */

    function clearPageCaches() {
        var jobs = [];

        if (window.caches && caches.keys) {
            jobs.push(caches.keys().then(function (keys) {
                return Promise.all(keys.filter(function (k) { return k.indexOf(PAGE_CACHE_PREFIX) === 0; })
                    .map(function (k) { return caches.delete(k); }));
            }).catch(function () {}));
        }

        if (hasSW && navigator.serviceWorker.controller && window.MessageChannel) {
            jobs.push(new Promise(function (resolve) {
                var channel = new MessageChannel();
                channel.port1.onmessage = function () { resolve(); };
                navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_PAGES' }, [channel.port2]);
            }));
        }

        remove('chvUser');

        // Eng ko'pi 1.5 soniya kutiladi, keyin baribir davom etadi
        return Promise.race([
            Promise.all(jobs),
            new Promise(function (resolve) { setTimeout(resolve, 1500); }),
        ]);
    }

    // Boshqa foydalanuvchi kirgan bo'lsa (umumiy telefon), eski sahifalar o'chiriladi
    if (!fromCache) {
        var meta = document.querySelector('meta[name="chv-user"]');
        var currentUser = meta ? meta.getAttribute('content') : '';
        var previousUser = read('chvUser');
        if (previousUser !== null && previousUser !== currentUser) { clearPageCaches(); }
        write('chvUser', currentUser);
    }

    /* ---------- Oflayn qator va formalar ---------- */

    var offlineBar = null;
    var toastTimer = null;

    function showOfflineBar() {
        if (offlineBar || !document.body) { return; }
        offlineBar = el('div', 'chv-offline-bar', t.offlineBar);
        offlineBar.setAttribute('role', 'status');
        document.body.insertBefore(offlineBar, document.body.firstChild);
    }

    function hideOfflineBar() {
        if (offlineBar && offlineBar.parentNode) { offlineBar.parentNode.removeChild(offlineBar); }
        offlineBar = null;
    }

    function isOffline() {
        return navigator.onLine === false;
    }

    function toast(text) {
        var node = document.querySelector('.chv-toast');
        if (!node) {
            node = el('div', 'chv-toast');
            node.setAttribute('role', 'alert');
            document.body.appendChild(node);
        }
        node.textContent = text;
        node.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { node.hidden = true; }, 6000);
    }

    var reloadingFresh = false;

    function backOnline() {
        hideOfflineBar();
        // Keshdagi sahifa yangisi bilan almashtiriladi (yangi CSRF token, yangi ma'lumot).
        // Foydalanuvchi biror narsa yozgan bo'lsa, matni yo'qolmasin: sahifa qoladi, formalar to'xtatilgan holda.
        if (fromCache && !typed && !reloadingFresh) {
            reloadingFresh = true;
            location.reload();
        }
    }

    if (fromCache || isOffline()) { showOfflineBar(); }
    window.addEventListener('offline', showOfflineBar);
    window.addEventListener('online', backOnline);

    // Keshdan ochilgan sahifa: internet qaytganini tekshirib turish ('online' hodisasi har doim ham kelmaydi).
    // Statik fayl va cookie'siz: Laravel sessiyasiga tegmaydi (fondagi so'rov sessiya cookie'sini qayta yozmasin)
    if (fromCache && window.fetch) {
        var probe = setInterval(function () {
            fetch('/manifest.json?online=' + Date.now(), { method: 'HEAD', cache: 'no-store', credentials: 'omit' }).then(function (res) {
                if (res.ok) { clearInterval(probe); backOnline(); }
            }).catch(function () {});
        }, 15000);
    }

    /* ---------- Chiqish (logout): avval kesh tozalanadi ---------- */

    function isLogoutForm(form) {
        if (!form || form.tagName !== 'FORM') { return false; }
        try { return /\/logout\/?$/.test(new URL(form.action, location.href).pathname); } catch (e) { return false; }
    }

    function logout(form) {
        form.setAttribute('data-chv-cleared', '1');
        clearPageCaches().then(function () {
            if (navigator.onLine === false) {
                form.removeAttribute('data-chv-cleared');
                toast(t.offlineForm);
                return;
            }
            // Keshdagi sahifadan ham yuboriladi: token eskirgan bo'lsa server (bootstrap/app.php) baribir chiqaradi
            HTMLFormElement.prototype.submit.call(form);
        });
    }

    // Menyudagi "Chiqish" havolasi onclick'da form.submit() chaqiradi (submit hodisasi chiqmaydi),
    // shuning uchun bosish ushlanib, avval kesh tozalanadi
    document.addEventListener('click', function (e) {
        var link = e.target && e.target.closest ? e.target.closest('a') : null;
        var form = link ? link.closest('form') : null;
        if (isLogoutForm(form) && !form.hasAttribute('data-chv-cleared')) {
            e.preventDefault();
            e.stopPropagation();
            logout(form);
        }
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (isLogoutForm(form)) {
            if (!form.hasAttribute('data-chv-cleared')) {
                e.preventDefault();
                logout(form);
            }
            return;
        }

        // Ma'lumot yuboradigan formalar to'xtatiladi (kiritilgan matn sahifada qoladi):
        //  - internet yo'q bo'lsa;
        //  - sahifa keshdan ochilgan bo'lsa (internet qaytgan bo'lsa ham — undagi token eskirgan)
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'get' && (isOffline() || fromCache)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            toast(isOffline() ? t.offlineForm : t.cachedForm);
        }
    }, true);

    /* ---------- Service worker va yangi versiya ---------- */

    function showUpdate(reg) {
        if (document.querySelector('.chv-update')) { return; }
        var box = el('div', 'chv-update');
        box.setAttribute('role', 'status');
        box.appendChild(el('span', '', t.update));
        var btn = el('button', 'chv-btn chv-btn--light', t.updateBtn);
        btn.type = 'button';
        btn.addEventListener('click', function () {
            btn.disabled = true;
            if (reg.waiting) { reg.waiting.postMessage({ type: 'SKIP_WAITING' }); }
        });
        box.appendChild(btn);
        document.body.appendChild(box);
    }

    if (hasSW && cfg.sw) {
        var reloading = false;
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (reloading) { return; }
            reloading = true;
            location.reload();
        });

        window.addEventListener('load', function () {
            navigator.serviceWorker.register(cfg.sw, { scope: '/' }).then(function (reg) {
                if (reg.waiting && navigator.serviceWorker.controller) { showUpdate(reg); }
                reg.addEventListener('updatefound', function () {
                    var worker = reg.installing;
                    if (!worker) { return; }
                    worker.addEventListener('statechange', function () {
                        if (worker.state === 'installed' && navigator.serviceWorker.controller) { showUpdate(reg); }
                    });
                });
            }).catch(function () {});
        });
    }

    /* ---------- "Ilovani o'rnating" ---------- */

    var ua = navigator.userAgent || '';
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
    // iPhone, iPod, iPad va iPadOS (o'zini Macintosh deb ko'rsatadi, lekin sensorli ekran)
    var isIPad = /iPad/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
    var isIOS = isIPad || /iPhone|iPod/.test(ua);

    // Ilova ichki brauzerlari: Telegram, Instagram, Facebook va h.k. iOS'da oddiy brauzerlar
    // (Safari, CriOS, EdgiOS, FxiOS) UA'sida "Safari/" bor; ichki WebView'larda odatda yo'q.
    var inApp = /Telegram|FBAN|FBAV|FB_IAB|FBIOS|Instagram|Line\/|MicroMessenger|Snapchat|TikTok|musical_ly|BytedanceWebview|LinkedInApp|Twitter|; wv\)/i.test(ua)
        || typeof window.TelegramWebviewProxy !== 'undefined'
        || typeof window.TelegramWebview !== 'undefined'
        || (isIOS && !/Safari\//.test(ua));

    // iOS brauzeri: Chrome (CriOS), Edge (EdgiOS), Firefox (FxiOS) — "Ulashish" manzil qatorida;
    // Safari — iPhone'da pastda, iPad'da tepada
    var iosThirdParty = /CriOS|EdgiOS|FxiOS/.test(ua);
    var iosOffer = isIOS && !inApp;

    // Android brauzeri (UA bo'yicha). Noma'lum Android brauzer — Chrome ko'rsatmasi.
    // WebView ("; wv)") va ilova ichki brauzerlari yuqoridagi inApp orqali chiqarib tashlanadi.
    var isAndroid = /Android/i.test(ua);
    var androidBrowser = !isAndroid ? null
        : /YaBrowser/.test(ua) ? 'yandex'
        : /SamsungBrowser/.test(ua) ? 'samsung'
        : /MiuiBrowser|XiaoMi/.test(ua) ? 'xiaomi'
        : /OPR\/|Opera/.test(ua) ? 'opera'
        : /Firefox\//.test(ua) ? 'firefox'
        : 'chrome';
    var androidOffer = isAndroid && !inApp;
    var ANDROID_PROMPT_WAIT = 4000;

    var canOffer = !standalone && !inApp;
    var deferredPrompt = null;
    var panel = null;
    var panelKind = null;

    function dismissedRecently() {
        var at = parseInt(read(DISMISS_KEY) || '0', 10);
        return at > 0 && (Date.now() - at) < DISMISS_DAYS * 24 * 60 * 60 * 1000;
    }

    function installButtons() {
        return document.querySelectorAll('[data-chv-install]');
    }

    function setInstallButtons(visible) {
        var buttons = installButtons();
        for (var i = 0; i < buttons.length; i++) { buttons[i].hidden = !visible; }
    }

    function hidePanel() {
        if (panel && panel.parentNode) { panel.parentNode.removeChild(panel); }
        panel = null;
        panelKind = null;
        document.body.classList.remove('chv-has-install');
        document.body.style.paddingBottom = '';
    }

    // Matn ichidagi belgilar (telefondagi ko'rinishiga o'xshash):
    //  share — "Ulashish": kvadrat va yuqoriga strelka; add — "Добавить на экран «Домой»": kvadrat ichida "+"
    //  menu — vertikal uch nuqta; burger — uch gorizontal chiziq (Android brauzerlari menyusi)
    var ICONS = {
        share: { cls: 'chv-share-icon', paths: ['M12 3v12', 'M8 7l4-4 4 4', 'M7 10H6a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-1'] },
        add: { cls: 'chv-add-icon', rect: true, paths: ['M12 8v8', 'M8 12h8'] },
        menu: { cls: 'chv-menu-icon', dots: true, paths: [] },
        burger: { cls: 'chv-burger-icon', paths: ['M5 7h14', 'M5 12h14', 'M5 17h14'] }
    };

    function inlineIcon(name) {
        var ns = 'http://www.w3.org/2000/svg';
        var def = ICONS[name];
        var svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('class', 'chv-step-icon ' + def.cls);
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        if (def.rect) {
            var rect = document.createElementNS(ns, 'rect');
            rect.setAttribute('x', '3.5');
            rect.setAttribute('y', '3.5');
            rect.setAttribute('width', '17');
            rect.setAttribute('height', '17');
            rect.setAttribute('rx', '4.5');
            svg.appendChild(rect);
        }
        if (def.dots) {
            for (var d = 0; d < 3; d++) {
                var dot = document.createElementNS(ns, 'circle');
                dot.setAttribute('cx', '12');
                dot.setAttribute('cy', String(5.5 + d * 6.5));
                dot.setAttribute('r', '1.9');
                dot.setAttribute('class', 'chv-icon-dot');
                svg.appendChild(dot);
            }
        }
        for (var i = 0; i < def.paths.length; i++) {
            var p = document.createElementNS(ns, 'path');
            p.setAttribute('d', def.paths[i]);
            svg.appendChild(p);
        }
        return svg;
    }

    // Matndagi ":share", ":add", ":menu", ":burger" o'rniga belgi qo'yiladi (innerHTML ishlatilmaydi)
    function richText(node, text) {
        var parts = String(text || '').split(/(:share|:add|:menu|:burger)/);
        for (var i = 0; i < parts.length; i++) {
            var token = parts[i].charAt(0) === ':' ? parts[i].substring(1) : null;
            if (token && ICONS[token]) { node.appendChild(inlineIcon(token)); }
            else if (parts[i]) { node.appendChild(document.createTextNode(parts[i])); }
        }
        return node;
    }

    // iPhone/iPad: brauzerga mos 3 ta raqamlangan qadam
    function iosSteps() {
        return stepList(iosThirdParty
            ? [t.iosStep1Other, t.iosStep2, t.iosStep3Other]
            : [isIPad ? t.iosStep1SafariPad : t.iosStep1SafariPhone, t.iosStep2, t.iosStep3Safari]);
    }

    // Android: brauzerga mos 3 ta qadam (t.android.chrome, .yandex, .samsung, .xiaomi, .opera, .firefox)
    function androidSteps() {
        var all = t.android || {};
        return stepList(all[androidBrowser] || all.chrome || []);
    }

    function stepList(steps) {
        var list = el('ol', 'chv-install__steps');
        for (var i = 0; i < steps.length; i++) {
            var item = el('li', 'chv-install__step');
            item.appendChild(el('span', 'chv-install__num', String(i + 1)));
            item.appendChild(richText(el('span', 'chv-install__step-text'), steps[i]));
            list.appendChild(item);
        }
        return list;
    }

    // kind: 'prompt' — "O'rnatish" tugmali panel (Android/kompyuter, beforeinstallprompt);
    //       'ios' — iPhone/iPad ko'rsatmasi; 'android' — Android ko'rsatmasi (prompt bo'lmaganda)
    function showPanel(kind) {
        if (panel || !document.body) { return; }
        var steps = kind === 'ios' || kind === 'android';
        panelKind = kind;
        panel = el('div', 'chv-install' + (steps ? ' chv-install--' + kind : ''));
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-label', t.installTitle);

        var icon = el('img', 'chv-install__icon');
        icon.src = cfg.icon;
        icon.alt = '';
        icon.width = 44;
        icon.height = 44;
        panel.appendChild(icon);

        var body = el('div', 'chv-install__body');
        body.appendChild(el('p', 'chv-install__text', t.installText));
        var actions = el('div', 'chv-install__actions');

        if (!steps) {
            var install = el('button', 'chv-btn chv-btn--light', t.installBtn);
            install.type = 'button';
            install.addEventListener('click', promptInstall);
            actions.appendChild(install);
        }

        var later = el('button', 'chv-btn chv-btn--ghost', t.later);
        later.type = 'button';
        later.addEventListener('click', function () {
            write(DISMISS_KEY, String(Date.now()));
            hidePanel();
        });
        actions.appendChild(later);

        if (steps) {
            // Ko'rsatma: ikonka va sarlavha bir qatorda, qadamlar va tugma ostida to'liq kenglikda
            panel.appendChild(body);
            panel.appendChild(kind === 'ios' ? iosSteps() : androidSteps());
            if (kind === 'android') { panel.appendChild(el('p', 'chv-install__note', t.androidNote)); }
            panel.appendChild(actions);
        } else {
            body.appendChild(actions);
            panel.appendChild(body);
        }
        document.body.appendChild(panel);
        document.body.classList.add('chv-has-install');
        // Ko'rsatma paneli balandroq: sahifa oxiri uning ostida qolmasin ("O'rnatish" paneli o'zgarmaydi)
        if (steps) { document.body.style.paddingBottom = (panel.offsetHeight + 24) + 'px'; }
    }

    function promptInstall() {
        if (!deferredPrompt) {
            // Tugma bosilganda ko'rsatma "Keyinroq" dan qat'i nazar ochiladi
            if (iosOffer) { showPanel('ios'); }
            else if (androidOffer) { showPanel('android'); }
            return;
        }
        var promptEvent = deferredPrompt;
        deferredPrompt = null;
        promptEvent.prompt();
        if (promptEvent.userChoice) {
            promptEvent.userChoice.then(function (choice) {
                hidePanel();
                // Android'da rad etilsa tugma qoladi (keyingi bosishda ko'rsatma ochiladi)
                if (!androidOffer || (choice && choice.outcome === 'accepted')) { setInstallButtons(false); }
            });
        }
    }

    function offerAndroidSteps() {
        if (deferredPrompt || panel || !cfg.installPanel || dismissedRecently()) { return; }
        showPanel('android');
    }

    if (canOffer) {
        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            setInstallButtons(true);
            // Avval Android ko'rsatmasi chiqqan bo'lsa, "O'rnatish" tugmali panel bilan almashtiriladi
            if (panelKind === 'android') { hidePanel(); }
            if (cfg.installPanel && !dismissedRecently()) { showPanel('prompt'); }
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            hidePanel();
            setInstallButtons(false);
        });

        var buttons = installButtons();
        for (var i = 0; i < buttons.length; i++) { buttons[i].addEventListener('click', promptInstall); }

        // iPhone/iPad (Safari, Chrome, Edge, Firefox): beforeinstallprompt yo'q — qo'lda qo'shish ko'rsatmasi
        if (iosOffer) {
            setInstallButtons(true);
            if (cfg.installPanel && !dismissedRecently()) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () { showPanel('ios'); });
                } else {
                    showPanel('ios');
                }
            }
        }

        // Android: tugmalar doim ko'rinadi. Yandex, Samsung, Xiaomi, Opera, Firefox'da prompt odatda
        // kelmaydi — ko'rsatma darhol; Chrome va noma'lum brauzerda ~4 soniya prompt kutiladi
        if (androidOffer) {
            setInstallButtons(true);
            if (androidBrowser === 'chrome') {
                setTimeout(offerAndroidSteps, ANDROID_PROMPT_WAIT);
            } else if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', offerAndroidSteps);
            } else {
                offerAndroidSteps();
            }
        }
    }
})();

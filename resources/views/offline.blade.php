{{--
    Oflayn sahifa: service worker uni o'rnatilganda keshlaydi va internet yo'q bo'lganda ko'rsatadi.
    Tashqi fayl (CSS, shrift, Vite) yo'q. Til: oxirgi ochilgan sahifa tili (localStorage chvLocale),
    bo'lmasa sessiyadagi til.
--}}
@php
    $locale = app()->getLocale();
    $htmlLangs = ['uz' => 'uz', 'uz_cyrl' => 'uz-Cyrl', 'ru' => 'ru'];
    $texts = [];
    foreach (array_keys($htmlLangs) as $code) {
        $texts[$code] = [
            'lang' => $htmlLangs[$code],
            'title' => __("Internet yo'q", [], $code),
            'text' => __('Ulanish tiklangach sahifa ochiladi.', [], $code),
            'retry' => __('Qayta urinish', [], $code),
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ $htmlLangs[$locale] ?? 'uz' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __("Internet yo'q") }} — Chorvadorim</title>
    @include('layouts.partials.pwa-head')
    <style>
        :root { --bg: #FAF6EE; --card: #FFFFFF; --ink: #201C16; --muted: #5C5447; --line: #E2D9C6; --btn: #2F4B33; --btn-ink: #FAF6EE; color-scheme: light; }
        html.dark { --bg: #14130F; --card: #221F1A; --ink: #EEE8DA; --muted: #B4AA97; --line: #37322A; --btn: #9BC893; --btn-ink: #12200F; color-scheme: dark; }
        @media (prefers-color-scheme: dark) {
            html:not(.light) { --bg: #14130F; --card: #221F1A; --ink: #EEE8DA; --muted: #B4AA97; --line: #37322A; --btn: #9BC893; --btn-ink: #12200F; color-scheme: dark; }
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem 1rem; background: var(--bg); color: var(--ink); font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; font-size: 1.0625rem; line-height: 1.55; }
        .box { width: 100%; max-width: 24rem; padding: 2rem 1.5rem; border: 1px solid var(--line); border-radius: 1.25rem; background: var(--card); text-align: center; }
        .box img { display: block; width: 72px; height: 72px; margin: 0 auto 1.25rem; border-radius: 18px; }
        h1 { margin: 0 0 .5rem; font-size: 1.5rem; line-height: 1.25; }
        p { margin: 0 0 1.5rem; color: var(--muted); }
        button { width: 100%; min-height: 3rem; border: 0; border-radius: .75rem; background: var(--btn); color: var(--btn-ink); font: inherit; font-weight: 700; cursor: pointer; }
    </style>
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('chorvadorimTheme');
                if (theme === 'dark') { document.documentElement.classList.add('dark'); }
                if (theme === 'light') { document.documentElement.classList.add('light'); }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <main class="box">
        <img src="{{ asset('icons/icon-192.png') }}" alt="" width="72" height="72">
        <h1 id="offline-title">{{ __("Internet yo'q") }}</h1>
        <p id="offline-text">{{ __('Ulanish tiklangach sahifa ochiladi.') }}</p>
        <button type="button" id="offline-retry" onclick="location.reload()">{{ __('Qayta urinish') }}</button>
    </main>

    <script type="application/json" id="offline-texts">@json($texts)</script>
    <script>
        (function () {
            var locale = null;
            try { locale = localStorage.getItem('chvLocale'); } catch (e) {}
            var texts = {};
            try { texts = JSON.parse(document.getElementById('offline-texts').textContent); } catch (e) {}
            var t = texts[locale];
            if (t) {
                document.documentElement.lang = t.lang;
                document.title = t.title + ' — Chorvadorim';
                document.getElementById('offline-title').textContent = t.title;
                document.getElementById('offline-text').textContent = t.text;
                document.getElementById('offline-retry').textContent = t.retry;
            }
            // Internet qaytsa sahifa o'zi qayta ochiladi
            window.addEventListener('online', function () { location.reload(); });
        })();
    </script>
</body>
</html>

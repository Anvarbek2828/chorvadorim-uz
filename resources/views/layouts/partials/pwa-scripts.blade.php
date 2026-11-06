{{--
    PWA skripti va uning uslublari (</body> oldidan). Mantiq: public/pwa.js, service worker: public/sw.js.
    Admin sahifalarida "ilovani o'rnating" paneli chiqmaydi.
--}}
@php
    $pwaScript = public_path('pwa.js');
    $pwaConfig = [
        'sw' => '/sw.js',
        'icon' => asset('icons/icon-192.png'),
        'locale' => app()->getLocale(),
        'installPanel' => ! request()->is('admin', 'admin/*'),
        't' => [
            'offlineBar' => __("Internet yo'q — oxirgi saqlangan ma'lumot ko'rsatilmoqda"),
            'offlineForm' => __("Internet yo'q. Ma'lumot hozircha saqlanmaydi, internet tiklangach qayta yuboring."),
            'update' => __('Yangi versiya tayyor'),
            'updateBtn' => __('Yangilash'),
            'installTitle' => __("Ilovani o'rnatish"),
            'installText' => __("Chorvadorim'ni ilova sifatida o'rnating — tezroq ochiladi"),
            'installBtn' => __("O'rnatish"),
            'later' => __('Keyinroq'),
            // iPhone/iPad ko'rsatmalari; ":icon" o'rniga pwa.js "Ulashish" belgisini qo'yadi
            'iosSafariPhone' => __("Pastdagi «Ulashish» :icon tugmasini bosing, so'ng «Bosh ekranga qo'shish» ni tanlang."),
            'iosSafariPad' => __("Tepadagi «Ulashish» :icon tugmasini bosing, so'ng «Bosh ekranga qo'shish» ni tanlang."),
            'iosOther' => __("Manzil qatoridagi «Ulashish» :icon tugmasini bosing, so'ng «Bosh ekranga qo'shish» ni tanlang."),
        ],
    ];
@endphp
<style>
    [data-chv-install][hidden], .chv-toast[hidden] { display: none !important; }
    .chv-offline-bar { position: sticky; top: 0; z-index: 9990; padding: .375rem 1rem; background: #FDE68A; color: #422006 !important; font-size: .875rem; line-height: 1.4; text-align: center; font-weight: 600; }
    .chv-toast { position: fixed; left: 50%; bottom: 1rem; z-index: 9995; transform: translateX(-50%); width: calc(100% - 2rem); max-width: 26rem; padding: .875rem 1rem; border-radius: .75rem; background: #7C2D12; color: #fff !important; font-size: .9375rem; line-height: 1.45; box-shadow: 0 8px 24px rgba(0, 0, 0, .25); }
    .chv-update { position: fixed; left: 50%; top: .75rem; z-index: 9996; transform: translateX(-50%); display: flex; align-items: center; gap: .75rem; width: calc(100% - 2rem); max-width: 26rem; padding: .5rem .5rem .5rem 1rem; border-radius: .75rem; background: #1F2937; color: #fff; font-size: .9375rem; box-shadow: 0 8px 24px rgba(0, 0, 0, .25); }
    .chv-update span { flex: 1; color: #fff !important; }
    .chv-install { position: fixed; left: .75rem; right: .75rem; bottom: .75rem; z-index: 9980; display: flex; gap: .75rem; align-items: flex-start; max-width: 30rem; margin: 0 auto; padding: .875rem; border-radius: 1rem; background: #2F4B33; color: #fff; box-shadow: 0 10px 30px rgba(0, 0, 0, .3); }
    .chv-install__icon { flex-shrink: 0; width: 44px; height: 44px; border-radius: 10px; box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .25); }
    .chv-install__body { flex: 1; min-width: 0; }
    .chv-install__text { margin: 0; color: #fff !important; font-size: .9375rem; line-height: 1.4; font-weight: 600; }
    .chv-install__hint { margin: .375rem 0 0; color: #E3EBDC !important; font-size: .9375rem; line-height: 1.5; }
    .chv-share-icon { display: inline-block; width: 1.25em; height: 1.25em; margin: 0 .2em; vertical-align: -.25em; padding: .1em; border-radius: .3em; background: rgba(255, 255, 255, .16); fill: none; stroke: #fff; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .chv-install__actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .625rem; }
    .chv-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 2.75rem; padding: .5rem 1rem; border-radius: .625rem; font: inherit; font-size: .9375rem; font-weight: 600; cursor: pointer; border: 2px solid transparent; }
    .chv-btn--light { background: #FAF6EE; color: #2F4B33 !important; }
    .chv-btn--ghost { background: transparent; color: #fff !important; border-color: rgba(255, 255, 255, .45); }
    .chv-btn:disabled { opacity: .6; cursor: default; }
    body.chv-has-install { padding-bottom: 8.5rem; }
</style>
<script type="application/json" id="chv-pwa-config">@json($pwaConfig)</script>
<script src="{{ asset('pwa.js') }}?v={{ is_file($pwaScript) ? filemtime($pwaScript) : 1 }}" defer></script>
